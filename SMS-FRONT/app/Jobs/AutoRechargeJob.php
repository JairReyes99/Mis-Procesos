<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\CompanyCreditService;
use App\Services\StripeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\CardException;

class AutoRechargeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private int $companyId) {}

    public function handle(StripeService $stripe, CompanyCreditService $credits): void
    {
        // Lock para evitar doble cobro en race condition (TTL 60 segundos)
        $lock = Cache::lock("auto_recharge_lock:{$this->companyId}", 60);

        if (!$lock->get()) {
            Log::info("AutoRechargeJob: lock activo para empresa {$this->companyId}, omitiendo.");
            return;
        }

        try {
            $company = Company::find($this->companyId);

            if (!$company || !$company->shouldAutoRecharge()) {
                return;
            }

            // Si el saldo ya subió (otra recarga lo resolvió), no cobrar
            $threshold = (float) ($company->auto_recharge_threshold ?? 0);
            if ((float) $company->balance > $threshold) {
                Log::info("AutoRechargeJob: empresa {$this->companyId} ya tiene saldo suficiente, omitiendo.");
                return;
            }

            $amount = (float) $company->auto_recharge_amount;
            $intent = $stripe->chargeOffSession($company, $amount);

            // El crédito real se aplica vía webhook payment_intent.succeeded
            Log::info('AutoRechargeJob: PaymentIntent creado', [
                'company_id'        => $company->id,
                'amount'            => $amount,
                'payment_intent_id' => $intent->id,
            ]);

        } catch (CardException $e) {
            Log::error('AutoRechargeJob: tarjeta declinada', [
                'company_id' => $this->companyId,
                'error'      => $e->getMessage(),
                'code'       => $e->getStripeCode(),
            ]);
        } catch (\Exception $e) {
            Log::error('AutoRechargeJob: error inesperado', [
                'company_id' => $this->companyId,
                'error'      => $e->getMessage(),
            ]);
        } finally {
            $lock->release();
        }
    }
}
