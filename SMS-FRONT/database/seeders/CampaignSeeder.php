<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CampaignSendType;
use App\Models\CampaignStatus;
use App\Models\RecipientSendStatus;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        // Tipos de envío
        $sendTypes = [
            ['name' => 'Inmediato',  'slug' => 'immediate', 'order' => 1, 'status_id' => 1],
            ['name' => 'Programado', 'slug' => 'scheduled',  'order' => 2, 'status_id' => 1],
        ];

        foreach ($sendTypes as $type) {
            CampaignSendType::firstOrCreate(['slug' => $type['slug']], $type);
        }

        // Estados de campaña
        $statuses = [
            ['id' => 1, 'name' => 'Borrador',    'slug' => 'draft',      'color' => 'pill--draft', 'order' => 1],
            ['id' => 2, 'name' => 'Programada',  'slug' => 'scheduled',  'color' => 'pill--info',  'order' => 2],
            ['id' => 3, 'name' => 'Procesando',  'slug' => 'processing', 'color' => 'pill--warn',  'order' => 3],
            ['id' => 4, 'name' => 'Completada',  'slug' => 'completed',  'color' => 'pill--ok',    'order' => 4],
            ['id' => 5, 'name' => 'Pausada',     'slug' => 'paused',     'color' => 'pill--warn',  'order' => 5],
            ['id' => 6, 'name' => 'Cancelada',   'slug' => 'cancelled',  'color' => 'pill--err',   'order' => 6],
        ];

        foreach ($statuses as $status) {
            CampaignStatus::firstOrCreate(['id' => $status['id']], $status);
        }

        // Estados de envío de destinatarios
        $sendStatuses = [
            ['id' => 1, 'name' => 'Pendiente',  'slug' => 'pending',  'color' => 'pill-muted', 'order' => 1],
            ['id' => 2, 'name' => 'Enviado',    'slug' => 'sent',     'color' => 'pill-ok',    'order' => 2],
            ['id' => 3, 'name' => 'Error',      'slug' => 'error',    'color' => 'pill-err',   'order' => 3],
            ['id' => 4, 'name' => 'Bloqueado',  'slug' => 'blocked',  'color' => 'pill-warn',  'order' => 4],
        ];

        foreach ($sendStatuses as $s) {
            RecipientSendStatus::firstOrCreate(['id' => $s['id']], $s);
        }
    }
}
