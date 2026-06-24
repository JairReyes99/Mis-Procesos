<?php

namespace App\Services;

use App\Models\Company;
use App\Models\StripePaymentEvent;
use Stripe\Exception\CardException;
use Stripe\StripeClient;

class StripeService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient([
            'api_key'        => config('stripe.secret'),
            'stripe_version' => config('stripe.api_version'),
        ]);
    }

    // ─── Cliente ──────────────────────────────────────────────────────────────

    /**
     * Retorna el Customer ID de Stripe para la empresa.
     * Lo crea si no existe (idempotente).
     */
    public function getOrCreateCustomer(Company $company): string
    {
        if ($company->stripe_customer_id) {
            return $company->stripe_customer_id;
        }

        $customer = $this->stripe->customers->create([
            'name'     => $company->name,
            'email'    => $company->email,
            'metadata' => ['company_id' => $company->id],
        ]);

        $company->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }

    // ─── Guardar tarjeta ──────────────────────────────────────────────────────

    /**
     * Crea un SetupIntent para que el frontend capture la tarjeta.
     * usage: off_session permite cobros futuros sin que el usuario esté presente.
     */
    public function createSetupIntent(Company $company): \Stripe\SetupIntent
    {
        $customerId = $this->getOrCreateCustomer($company);

        return $this->stripe->setupIntents->create([
            'customer'             => $customerId,
            'usage'                => 'off_session',
            'payment_method_types' => ['card'],
        ]);
    }

    /**
     * Guarda el PaymentMethod confirmado en la empresa.
     * Llamar después de que el SetupIntent esté en estado 'succeeded'.
     */
    public function savePaymentMethod(Company $company, string $paymentMethodId): void
    {
        $this->stripe->paymentMethods->attach($paymentMethodId, [
            'customer' => $this->getOrCreateCustomer($company),
        ]);

        $this->stripe->customers->update($company->stripe_customer_id, [
            'invoice_settings' => ['default_payment_method' => $paymentMethodId],
        ]);

        $company->update(['stripe_pm_id' => $paymentMethodId]);
    }

    /**
     * Devuelve brand, last4 y expiración de la tarjeta guardada, o null.
     */
    public function getCardSummary(Company $company): ?array
    {
        if (!$company->stripe_pm_id) {
            return null;
        }

        try {
            $pm = $this->stripe->paymentMethods->retrieve($company->stripe_pm_id);
            return [
                'brand' => $pm->card->brand,
                'last4' => $pm->card->last4,
                'exp'   => $pm->card->exp_month . '/' . $pm->card->exp_year,
            ];
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Desvincula la tarjeta guardada de la empresa.
     */
    public function detachPaymentMethod(Company $company): void
    {
        if ($company->stripe_pm_id) {
            try {
                $this->stripe->paymentMethods->detach($company->stripe_pm_id);
            } catch (\Exception) {
                // Ya desvinculada en Stripe — limpiar igual
            }
            $company->update(['stripe_pm_id' => null]);
        }
    }

    // ─── Recargas ─────────────────────────────────────────────────────────────

    /**
     * Recarga manual: crea el PaymentIntent para que el frontend confirme.
     */
    public function createManualRecharge(Company $company, float $amountMxn): \Stripe\PaymentIntent
    {
        if (!$company->stripe_pm_id) {
            throw new \RuntimeException('La empresa no tiene tarjeta guardada.');
        }

        return $this->stripe->paymentIntents->create([
            'amount'               => (int) round($amountMxn * 100),
            'currency'             => config('stripe.currency', 'mxn'),
            'customer'             => $this->getOrCreateCustomer($company),
            'payment_method'       => $company->stripe_pm_id,
            'payment_method_types' => ['card'],
            'confirm'              => false,
            'metadata'             => [
                'company_id' => $company->id,
                'type'       => 'manual_recharge',
            ],
        ]);
    }

    /**
     * Auto-recharge off-session: se dispara cuando el saldo llega al umbral.
     * Lanza CardException si la tarjeta es declinada.
     */
    public function chargeOffSession(Company $company, float $amountMxn): \Stripe\PaymentIntent
    {
        if (!$company->stripe_pm_id || !$company->auto_recharge_enabled) {
            throw new \RuntimeException('Auto-recharge no configurado para esta empresa.');
        }

        try {
            return $this->stripe->paymentIntents->create([
                'amount'               => (int) round($amountMxn * 100),
                'currency'             => config('stripe.currency', 'mxn'),
                'customer'             => $this->getOrCreateCustomer($company),
                'payment_method'       => $company->stripe_pm_id,
                'payment_method_types' => ['card'],
                'off_session'          => true,
                'confirm'              => true,
                'metadata'             => [
                    'company_id' => $company->id,
                    'type'       => 'auto_recharge',
                ],
            ]);
        } catch (CardException $e) {
            throw $e;
        }
    }

    // ─── Webhook ──────────────────────────────────────────────────────────────

    /**
     * Verifica la firma del webhook y retorna el evento.
     * Lanza SignatureVerificationException si la firma no coincide.
     */
    public function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
    {
        return \Stripe\Webhook::constructEvent(
            $payload,
            $sigHeader,
            config('stripe.webhook_secret')
        );
    }
}
