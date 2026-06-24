<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TestSmsSend;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestSmsController extends Controller
{
    private static array $countries = [
        ['code' => '+52',  'flag' => '🇲🇽', 'name' => 'México',             'len' => 10],
        ['code' => '+1',   'flag' => '🇺🇸', 'name' => 'Estados Unidos',     'len' => 10],
        ['code' => '+57',  'flag' => '🇨🇴', 'name' => 'Colombia',           'len' => 10],
        ['code' => '+54',  'flag' => '🇦🇷', 'name' => 'Argentina',          'len' => 10],
        ['code' => '+56',  'flag' => '🇨🇱', 'name' => 'Chile',              'len' => 9 ],
        ['code' => '+51',  'flag' => '🇵🇪', 'name' => 'Perú',               'len' => 9 ],
        ['code' => '+58',  'flag' => '🇻🇪', 'name' => 'Venezuela',          'len' => 10],
        ['code' => '+55',  'flag' => '🇧🇷', 'name' => 'Brasil',             'len' => 11],
        ['code' => '+34',  'flag' => '🇪🇸', 'name' => 'España',             'len' => 9 ],
        ['code' => '+593', 'flag' => '🇪🇨', 'name' => 'Ecuador',            'len' => 9 ],
        ['code' => '+502', 'flag' => '🇬🇹', 'name' => 'Guatemala',          'len' => 8 ],
        ['code' => '+503', 'flag' => '🇸🇻', 'name' => 'El Salvador',        'len' => 8 ],
        ['code' => '+504', 'flag' => '🇭🇳', 'name' => 'Honduras',           'len' => 8 ],
        ['code' => '+505', 'flag' => '🇳🇮', 'name' => 'Nicaragua',          'len' => 8 ],
        ['code' => '+506', 'flag' => '🇨🇷', 'name' => 'Costa Rica',         'len' => 8 ],
        ['code' => '+507', 'flag' => '🇵🇦', 'name' => 'Panamá',             'len' => 8 ],
        ['code' => '+591', 'flag' => '🇧🇴', 'name' => 'Bolivia',            'len' => 8 ],
        ['code' => '+595', 'flag' => '🇵🇾', 'name' => 'Paraguay',           'len' => 9 ],
        ['code' => '+598', 'flag' => '🇺🇾', 'name' => 'Uruguay',            'len' => 9 ],
        ['code' => '+1809','flag' => '🇩🇴', 'name' => 'Rep. Dominicana',    'len' => 10],
    ];

    public function home(): View
    {
        return view('core.home', ['countries' => self::$countries]);
    }

    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'country_code' => 'required|string|max:10',
            'phone'        => 'required|string|max:20',
            'message'      => 'required|string|max:1600',
        ]);

        TestSmsSend::create([
            'company_id'   => auth()->user()->company_id,
            'user_id'      => auth()->id(),
            'country_code' => $request->country_code,
            'phone'        => preg_replace('/\D/', '', $request->phone),
            'message'      => $request->message,
            'status'       => 0,
        ]);

        return response()->json(['success' => true, 'id' => $testSms->id, 'message' => 'SMS de prueba en cola de envío.']);
    }
}
