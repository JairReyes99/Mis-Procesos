<?php

namespace App\Services;

use App\Jobs\AutoRechargeJob;
use App\Models\AppSetting;
use App\Models\Company;
use App\Models\CompanyCreditTransaction;
use Illuminate\Support\Facades\DB;

class CompanyCreditService
{
    /**
     * Deduct balance from a company (cargo).
     *
     * @throws \Exception if balance is insufficient
     */
    public function charge(
        Company $company,
        float $amount,
        string $concept,
        ?string $notes = null,
        ?int $createdBy = null
    ): CompanyCreditTransaction {
        return $this->apply($company, 2, $amount, $concept, $notes, $createdBy);
    }

    /**
     * Add balance to a company (recarga).
     */
    public function credit(
        Company $company,
        float $amount,
        string $concept,
        ?string $notes = null,
        ?int $createdBy = null
    ): CompanyCreditTransaction {
        return $this->apply($company, 1, $amount, $concept, $notes, $createdBy);
    }

    /**
     * Resolve the SMS price per segment for a company.
     * Company-level override takes precedence over the global setting.
     */
    public function smsPrice(Company $company): float
    {
        $companyPrice = $company->settings['sms_price_per_segment'] ?? null;

        if ($companyPrice !== null) {
            return (float) $companyPrice;
        }

        return (float) AppSetting::get('sms_price_per_segment', 0.45);
    }

    /**
     * Calculate the cost for a given number of segments.
     */
    public function calculateCost(Company $company, int $segments): float
    {
        return $segments * $this->smsPrice($company);
    }

    /**
     * Check whether a company has enough balance to cover a given segment count.
     */
    public function hasSufficientBalance(Company $company, int $segments): bool
    {
        return (float) $company->balance >= $this->calculateCost($company, $segments);
    }

    // -------------------------------------------------------------------------

    private function apply(
        Company $company,
        int $type,
        float $amount,
        string $concept,
        ?string $notes,
        ?int $createdBy
    ): CompanyCreditTransaction {
        $transaction    = null;
        $shouldRecharge = false;

        DB::transaction(function () use ($company, $type, $amount, $concept, $notes, $createdBy, &$transaction, &$shouldRecharge) {
            $locked = Company::lockForUpdate()->find($company->id);

            $balanceBefore = (float) $locked->balance;

            if ($type === 2 && $balanceBefore < $amount) {
                throw new \Exception('Saldo insuficiente para aplicar el cargo.');
            }

            $balanceAfter = $type === 1
                ? $balanceBefore + $amount
                : $balanceBefore - $amount;

            $transaction = CompanyCreditTransaction::create([
                'company_id'     => $locked->id,
                'type'           => $type,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'concept'        => $concept,
                'notes'          => $notes,
                'created_by'     => $createdBy ?? auth()->id() ?? 1,
            ]);

            $locked->update(['balance' => $balanceAfter]);

            if ($type === 2) {
                $threshold      = (float) ($locked->auto_recharge_threshold ?? 0);
                $shouldRecharge = $locked->shouldAutoRecharge() && (float) $balanceAfter <= $threshold;
            }
        });

        // Dispatch DESPUÉS del commit para garantizar que el job lee el estado actualizado
        if ($shouldRecharge) {
            AutoRechargeJob::dispatch($transaction->company_id)->onQueue('default');
        }

        return $transaction;
    }
}
