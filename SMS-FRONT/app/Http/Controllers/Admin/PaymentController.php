<?php

namespace App\Http\Controllers\Admin;

use App\Events\BalanceUpdated;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyCreditTransaction;
use App\Models\StripePaymentEvent;
use App\Services\CompanyCreditService;
use App\Services\PayPalService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\CardException;
use Stripe\Exception\SignatureVerificationException;
use Yajra\DataTables\Facades\DataTables;

class PaymentController extends Controller
{
    public function __construct(
        private StripeService $stripe,
        private PayPalService $paypal,
        private CompanyCreditService $credits,
    ) {}

    // ─── Vista principal ───────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $company     = $this->resolveCompany($request);
        $cardSummary = $this->stripe->getCardSummary($company);

        return view('admin.payments.index', [
            'company'        => $company,
            'cardSummary'    => $cardSummary,
            'publishableKey' => config('stripe.publishable'),
            'autoRecharge'   => [
                'enabled'   => $company->auto_recharge_enabled,
                'amount'    => $company->auto_recharge_amount,
                'threshold' => $company->auto_recharge_threshold,
            ],
        ]);
    }

    // ─── Guardar tarjeta ───────────────────────────────────────────────────────

    public function setupIntent(Request $request)
    {
        $company = $this->resolveCompany($request);
        $intent  = $this->stripe->createSetupIntent($company);

        return response()->json(['client_secret' => $intent->client_secret]);
    }

    public function saveCard(Request $request)
    {
        $request->validate(['payment_method_id' => 'required|string|starts_with:pm_']);

        $company = $this->resolveCompany($request);
        $this->stripe->savePaymentMethod($company, $request->payment_method_id);

        return response()->json(['ok' => true, 'message' => 'Tarjeta guardada correctamente.']);
    }

    public function removeCard(Request $request)
    {
        $company = $this->resolveCompany($request);
        $this->stripe->detachPaymentMethod($company);

        return response()->json(['ok' => true, 'message' => 'Tarjeta eliminada.']);
    }

    // ─── Recarga manual ────────────────────────────────────────────────────────

    public function manualRecharge(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:50|max:999999',
        ]);

        $company = $this->resolveCompany($request);

        if (!$company->hasStripeCard()) {
            return response()->json(['error' => 'No tienes tarjeta guardada.'], 422);
        }

        try {
            $intent = $this->stripe->createManualRecharge($company, (float) $request->amount);
            return response()->json(['client_secret' => $intent->client_secret]);
        } catch (\Exception $e) {
            Log::error('Stripe manual recharge failed', [
                'company_id' => $company->id,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => 'No se pudo procesar el pago.'], 500);
        }
    }

    // ─── Configuración auto-recharge ───────────────────────────────────────────

    public function updateAutoRecharge(Request $request)
    {
        $request->validate([
            'enabled'   => 'required|boolean',
            'amount'    => 'required_if:enabled,true|nullable|numeric|min:100',
            'threshold' => 'nullable|numeric|min:0',
        ]);

        $company = $this->resolveCompany($request);

        if ($request->enabled && !$company->hasStripeCard()) {
            return response()->json([
                'error' => 'Debes guardar una tarjeta antes de activar la recarga automática.',
            ], 422);
        }

        $company->update([
            'auto_recharge_enabled'   => $request->boolean('enabled'),
            'auto_recharge_amount'    => $request->amount,
            'auto_recharge_threshold' => $request->threshold,
        ]);

        return response()->json(['ok' => true, 'message' => 'Configuración guardada.']);
    }

    // ─── Historial de transacciones (DataTables) ──────────────────────────────

    public function transactions(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $company = $this->resolveCompany($request);

        $query = CompanyCreditTransaction::where('company_id', $company->id)
            ->select(['id', 'type', 'amount', 'balance_before', 'balance_after', 'concept', 'created_at']);

        $query->when($request->filled('filter_type'), fn ($q) =>
            $q->where('type', $request->integer('filter_type'))
        );
        $query->when($request->filled('filter_from'), fn ($q) =>
            $q->whereDate('created_at', '>=', $request->input('filter_from'))
        );
        $query->when($request->filled('filter_to'), fn ($q) =>
            $q->whereDate('created_at', '<=', $request->input('filter_to'))
        );

        return DataTables::of($query)
            ->addColumn('type_label', fn ($tx) => $tx->type === 1 ? 'Recarga' : 'Cargo')
            ->addColumn('amount_fmt', fn ($tx) => number_format($tx->amount, 2))
            ->addColumn('balance_after_fmt', fn ($tx) => number_format($tx->balance_after, 2))
            ->make(true);
    }

    // ─── Webhook ───────────────────────────────────────────────────────────────

    public function webhookStripe(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        // 1. Verificar firma HMAC
        try {
            $event = $this->stripe->constructWebhookEvent($payload, $sigHeader);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook: firma inválida', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // 2. Deduplicar por event ID
        if (StripePaymentEvent::alreadyProcessed($event->id)) {
            return response()->json(['ok' => true, 'status' => 'duplicate']);
        }

        // 3. Procesar según tipo
        try {
            $this->handleStripeEvent($event);

            StripePaymentEvent::create([
                'stripe_event_id' => $event->id,
                'event_type'      => $event->type,
                'status'          => 'processed',
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe webhook handler error', [
                'event_id'   => $event->id,
                'event_type' => $event->type,
                'error'      => $e->getMessage(),
            ]);

            StripePaymentEvent::create([
                'stripe_event_id' => $event->id,
                'event_type'      => $event->type,
                'status'          => 'failed',
                'notes'           => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Handler failed'], 500);
        }

        return response()->json(['ok' => true]);
    }

    // ─── Helpers privados ──────────────────────────────────────────────────────

    private function handleStripeEvent(\Stripe\Event $event): void
    {
        $object = $event->data->object;

        match ($event->type) {
            'payment_intent.succeeded'      => $this->onPaymentSucceeded($object),
            'payment_intent.payment_failed' => $this->onPaymentFailed($object),
            'charge.refunded'               => $this->onChargeRefunded($object),
            default                         => null,
        };
    }

    private function onPaymentSucceeded(\Stripe\PaymentIntent $intent): void
    {
        $companyId = $intent->metadata['company_id'] ?? null;
        if (!$companyId) return;

        $company = Company::find($companyId);
        if (!$company) return;

        $amountMxn = $intent->amount_received / 100;
        $type      = $intent->metadata['type'] ?? 'stripe_payment';

        $this->credits->credit(
            $company,
            $amountMxn,
            "Recarga vía Stripe ({$type})",
            "PaymentIntent: {$intent->id}",
            null
        );

        $company->refresh();

        broadcast(new BalanceUpdated(
            companyId: $company->id,
            balance:   (float) $company->balance,
            amount:    $amountMxn,
            concept:   "Recarga vía Stripe ({$type})",
        ));

        Log::info('Stripe: créditos acreditados', [
            'company_id'        => $company->id,
            'amount'            => $amountMxn,
            'payment_intent_id' => $intent->id,
        ]);
    }

    private function onPaymentFailed(\Stripe\PaymentIntent $intent): void
    {
        $companyId = $intent->metadata['company_id'] ?? null;
        if (!$companyId) return;

        Log::warning('Stripe: pago fallido', [
            'company_id'        => $companyId,
            'payment_intent_id' => $intent->id,
            'last_error'        => $intent->last_payment_error?->message,
        ]);
    }

    private function onChargeRefunded(\Stripe\Charge $charge): void
    {
        Log::info('Stripe: reembolso registrado', ['charge_id' => $charge->id]);
    }

    // ─── PayPal ────────────────────────────────────────────────────────────────

    public function paypalCreateOrder(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:50|max:999999']);

        $company = $this->resolveCompany($request);

        try {
            $order = $this->paypal->createOrder($company, (float) $request->amount);
            return response()->json(['id' => $order['id']]);
        } catch (\Exception $e) {
            Log::error('PayPal createOrder error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'No se pudo iniciar el pago con PayPal.'], 500);
        }
    }

    public function paypalCaptureOrder(Request $request)
    {
        $request->validate(['order_id' => 'required|string']);

        $company = $this->resolveCompany($request);

        try {
            $capture = $this->paypal->captureOrder($request->order_id);
            $status  = $capture['status'] ?? '';

            if ($status !== 'COMPLETED') {
                return response()->json(['error' => "Pago no completado (status: {$status})"], 422);
            }

            $unit      = $capture['purchase_units'][0] ?? [];
            $paid      = (float) ($unit['payments']['captures'][0]['amount']['value'] ?? 0);
            $captureId = $unit['payments']['captures'][0]['id'] ?? 'unknown';

            $this->credits->credit(
                $company,
                $paid,
                'Recarga vía PayPal',
                "Order ID: {$request->order_id} / Capture: {$captureId}",
                null
            );

            return response()->json(['ok' => true, 'message' => "Recarga de \${$paid} MXN aplicada."]);

        } catch (\Exception $e) {
            Log::error('PayPal captureOrder error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al confirmar el pago de PayPal.'], 500);
        }
    }

    public function webhookPaypal(Request $request)
    {
        $payload   = $request->getContent();
        $headerMap = [];
        foreach ($request->headers->all() as $key => $val) {
            $headerMap[strtoupper($key)] = is_array($val) ? $val[0] : $val;
        }

        if (!$this->paypal->verifyWebhookSignature($headerMap, $payload)) {
            Log::warning('PayPal webhook: firma inválida');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event     = $request->json()->all();
        $eventType = $event['event_type'] ?? 'unknown';
        $eventId   = $event['id'] ?? 'unknown';

        Log::info('PayPal webhook recibido', ['event_type' => $eventType, 'event_id' => $eventId]);

        return response()->json(['ok' => true]);
    }

    // ─── Helpers privados ──────────────────────────────────────────────────────

    private function resolveCompany(Request $request): Company
    {
        $user = $request->user();

        if (!$user->company_id && $request->has('company_id')) {
            return Company::findOrFail($request->company_id);
        }

        if ($user->company_id) {
            return Company::findOrFail($user->company_id);
        }

        abort(403, 'No se puede determinar la empresa.');
    }
}
