<?php

namespace App\Repositories;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\CampaignSendType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CampaignRepository
{
    /**
     * Retorna query builder para DataTables, cargando relación sendType.
     */
    public function datatable(): Builder
    {
        return Campaign::with(['sendType', 'statusCatalog'])
            ->select('campaigns.*');
    }

    /**
     * Crea una campaña junto con sus destinatarios en una transacción.
     *
     * @param  array  $data        Datos validados del request
     * @param  array  $recipients  Array de ['phone', 'message', 'segments', 'encoding']
     * @return Campaign
     */
    public function createWithRecipients(array $data, array $recipients): Campaign
    {
        return DB::transaction(function () use ($data, $recipients) {
            $campaign = Campaign::create([
                'company_id'       => $data['company_id'] ?? auth()->user()->company_id,
                'name'             => $data['name'],
                'send_type_id'     => $data['send_type_id'],
                // C-11: store in UTC so Python's GETUTCDATE() comparison is correct
                'scheduled_at'     => isset($data['scheduled_at'])
                    ? Carbon::parse($data['scheduled_at'], config('app.timezone'))->utc()->toDateTimeString()
                    : null,
                'no_send_rules'      => $data['no_send_rules'] ?? null,
                'notification_email' => $data['notification_email'] ?? null,
                'total_recipients'   => count($recipients),
                'sent_count'       => 0,
                'failed_count'     => 0,
                'campaign_status'  => CampaignSendType::find($data['send_type_id'])?->slug === 'immediate' ? 3 : 2,
                'created_by'       => auth()->id(),
            ]);

            if (!empty($recipients)) {
                $now = now();

                $rows = array_map(function ($recipient) use ($campaign, $now) {
                    return [
                        'campaign_id'   => $campaign->id,
                        'phone'         => $recipient['phone'],
                        'message'       => $recipient['message'],
                        'segments'      => $recipient['segments'] ?? 1,
                        'encoding'      => $recipient['encoding'] ?? 'GSM-7',
                        'send_status'   => 1, // Pendiente
                        'sent_at'       => null,
                        'error_message' => null,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }, $recipients);

                // SQL Server: máx 2100 parámetros por query. Con 10 columnas → max 210 filas por lote.
                foreach (array_chunk($rows, 200) as $chunk) {
                    CampaignRecipient::insert($chunk);
                }
            }

            return $campaign;
        });
    }

    /**
     * Cancela una campaña si está en estado Programada (2).
     *
     * @param  Campaign  $campaign
     * @return bool
     */
    public function cancel(Campaign $campaign): bool
    {
        if ($campaign->campaign_status !== 2) {
            return false;
        }

        $campaign->campaign_status = 6; // Cancelada
        $campaign->save();

        return true;
    }

    /**
     * Pausa una campaña si está en Programada (2) o Procesando (3).
     */
    public function pause(Campaign $campaign): bool
    {
        if (!in_array($campaign->campaign_status, [2, 3])) {
            return false;
        }

        $campaign->campaign_status = 5; // Pausada
        $campaign->save();

        return true;
    }

    /**
     * Reanuda una campaña pausada (5).
     * Inmediato → Procesando (3). Programado → Programada (2).
     */
    public function resume(Campaign $campaign): bool
    {
        if ($campaign->campaign_status !== 5) {
            return false;
        }

        $sendTypeSlug = $campaign->sendType?->slug;
        $campaign->campaign_status = $sendTypeSlug === 'immediate' ? 3 : 2;
        $campaign->save();

        return true;
    }
}
