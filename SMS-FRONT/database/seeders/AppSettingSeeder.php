<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'key'         => 'sms_price_per_segment',
                'value'       => '0.45',
                'description' => 'Costo por segmento SMS (MXN). Un mensaje estándar GSM7 ≤160 chars = 1 segmento.',
            ],
        ];

        foreach ($defaults as $setting) {
            AppSetting::firstOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'description' => $setting['description']]
            );
        }
    }
}
