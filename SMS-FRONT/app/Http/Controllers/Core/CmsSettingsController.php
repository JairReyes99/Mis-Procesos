<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class CmsSettingsController extends Controller
{
    public function index()
    {
        $smsPriceGlobal = AppSetting::get('sms_price_per_segment', '0.45');

        return view('core.settings.index', compact('smsPriceGlobal'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'sms_price_per_segment' => ['required', 'numeric', 'min:0'],
        ], [], [
            'sms_price_per_segment' => 'precio SMS por segmento',
        ]);

        AppSetting::set(
            'sms_price_per_segment',
            $request->input('sms_price_per_segment'),
            'Costo por segmento SMS (MXN). Un mensaje estándar GSM7 ≤160 chars = 1 segmento.'
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Configuración guardada correctamente.',
        ]);
    }
}
