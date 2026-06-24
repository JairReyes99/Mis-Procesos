<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\CompanyCreditService;
use Illuminate\Console\Command;

class ExpireStalePausedCampaigns extends Command
{
    protected $signature = 'sms:expire-paused
                            {--days=7  : Días de inactividad para considerar una campaña pausada como expirada}
                            {--dry-run : Muestra qué campañas serían canceladas sin ejecutar cambios}';

    protected $description = 'Cancela campañas pausadas con más de N días de inactividad y cobra los mensajes enviados';

    public function handle(CompanyCreditService $credits): int
    {
        $days   = max(1, (int) $this->option('days'));
        $dryRun = $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $campaigns = Campaign::with('company')
            ->where('campaign_status', 5) // Pausada
            ->where('updated_at', '<', $cutoff)
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info("No hay campañas pausadas con más de {$days} días de inactividad.");
            return self::SUCCESS;
        }

        $rows    = [];
        $expired = 0;

        foreach ($campaigns as $campaign) {
            $company  = $campaign->company;
            $sent     = $campaign->recipients()->where('send_status', 2);
            $segments = (int) $sent->sum('segments');

            $totalCost = round((float) $sent->whereNotNull('cost')->sum('cost'), 4);
            if ($totalCost <= 0 && $segments > 0) {
                $totalCost = round($segments * $credits->smsPrice($company), 4);
            }

            $delta       = round($totalCost - (float) $campaign->charged_cost, 4);
            $inactiveDays = (int) now()->diffInDays($campaign->updated_at);

            $rows[] = [
                $campaign->id,
                $campaign->name,
                $company->name,
                $inactiveDays . ' días',
                '$' . number_format($totalCost, 2),
                '$' . number_format((float) $campaign->charged_cost, 2),
                '$' . number_format($delta, 2),
            ];

            if ($dryRun) {
                continue;
            }

            if ($delta > 0) {
                try {
                    $credits->charge(
                        $company,
                        $delta,
                        "Campaña #{$campaign->id} expirada — {$campaign->name} ({$segments} seg. enviados)",
                        null,
                        null
                    );
                    $campaign->charged_cost = $totalCost;
                } catch (\Exception $e) {
                    $this->error("Campaña #{$campaign->id} — error al cobrar: {$e->getMessage()}");
                }
            }

            $campaign->campaign_status = 6; // Cancelada
            $campaign->charged_at      = now();
            $campaign->save();
            $expired++;
        }

        $this->table(
            ['ID', 'Campaña', 'Empresa', 'Inactividad', 'Costo total', 'Ya cobrado', 'Delta'],
            $rows
        );

        if ($dryRun) {
            $this->warn('Modo dry-run — no se realizó ningún cambio.');
        } else {
            $this->info("{$expired} campaña(s) expiradas y cerradas correctamente.");
        }

        return self::SUCCESS;
    }
}
