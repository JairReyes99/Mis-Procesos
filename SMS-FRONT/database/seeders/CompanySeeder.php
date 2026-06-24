<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::firstOrCreate(
            ['name' => 'Empresa Demo'],
            [
                'rfc'       => 'XAXX010101000',
                'email'     => 'demo@sms-intelix.com',
                'phone'     => '5500000000',
                'status_id' => 1,
            ]
        );
    }
}
