<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\CompanyCreditService;
use Illuminate\Console\Command;

class ApplyCampaignCharges extends Command
{
    protected $signature = 'sms:apply-charges
                            {--campaign= : ID de campaña específica}
                            {--dry-run   : Muestra el cálculo sin aplicar el cobro}';

    protected $description = 'Aplica cargos pendientes por mensajes enviados en campañas completadas o canceladas';

    public function handle(CompanyCreditService $credits): int
    {
        $dryRun     = $this->option('dry-run');
        $campaignId = $this->option('campaign');

        // Busca campañas finalizadas (status 4 o 6) sin cierre contable definitivo
        $query = Campaign::with('company')
            ->whereNull('charged_at')
            ->whereIn('campaign_status', [4, 6]);

        if ($campaignId) {
            $query->where('id', $campaignId);
        }

        $campaigns = $query->get();

        if ($campaigns->isEmpty()) {
            $this->info('No hay campañas pendientes de cobro.');
            return self::SUCCESS;
        }

        $rows    = [];
        $charged = 0;

        foreach ($campaigns as $campaign) {
            $company  = $campaign->company;
            $sent     = $campaign->recipients()->where('send_status', 2);
            $segments = (int) $sent->sum('segments');

            // Si Python ya escribió el costo por registro, sumamos directo.
            // Si no (legacy sin cost), recalculamos con el precio vigente.
            $totalCost = round((float) $sent->whereNotNull('cost')->sum('cost'), 4);
            if ($totalCost <= 0 && $segments > 0) {
                $price     = $credits->smsPrice($company);
                $totalCost = round($segments * $price, 4);
                $source    = 'calculado';
            } else {
                $price  = $segments > 0 ? round($totalCost / $segments, 4) : 0;
                $source = 'registrado';
            }

            // Delta: lo que queda por cobrar descontando cobros parciales previos (ej: pausa)
            $delta = round($totalCost - (float) $campaign->charged_cost, 4);

            if ($delta <= 0 && $segments === 0) {
                // Campaña sin envíos — cerrar contablemente sin cargo
                if (!$dryRun) {
                    $campaign->charged_at = now();
                    $campaign->save();
                }
                continue;
            }

            $rows[] = [
                $campaign->id,
                $campaign->name,
                $company->name,
                number_format($segments),
                '$' . number_format($price, 4),
                '$' . number_format($totalCost, 2),
                '$' . number_format((float) $campaign->charged_cost, 2),
                '$' . number_format($delta, 2),
                $source,
            ];

            if (!$dryRun && $delta > 0) {
                try {
                    $credits->charge(
                        $company,
                        $delta,
                        "Campaña #{$campaign->id} — {$campaign->name} ({$segments} seg. enviados)",
                        null,
                        null
                    );

                    $campaign->charged_cost = $totalCost;
                    $campaign->charged_at   = now();
                    $campaign->save();
                    $charged++;
                } catch (\Exception $e) {
                    $this->error("Campaña #{$campaign->id}: {$e->getMessage()}");
                }
            }
        }

        $this->table(
            ['ID', 'Campaña', 'Empresa', 'Segmentos', 'Precio/seg', 'Costo total', 'Ya cobrado', 'Delta', 'Fuente'],
            $rows
        );

        if ($dryRun) {
            $this->warn('Modo dry-run — no se aplicó ningún cobro.');
        } else {
            $this->info("{$charged} de " . count($rows) . ' campaña(s) cobradas correctamente.');
        }

        return self::SUCCESS;
    }
}
