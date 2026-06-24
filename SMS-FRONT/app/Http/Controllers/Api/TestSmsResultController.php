<?php

namespace App\Http\Controllers\Api;

use App\Events\TestSmsSent;
use App\Http\Controllers\Controller;
use App\Models\TestSmsSend;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestSmsResultController extends Controller
{
    public function update(Request $request, string $id): JsonResponse
    {
        // C-03: fail-closed — reject all requests if secret is not configured
        $secret = config('app.campaign_webhook_secret');
        if (empty($secret)) {
            return response()->json(['error' => 'Webhook not configured'], 500);
        }
        if (!hash_equals($secret, (string) $request->bearerToken())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'status'        => 'required|integer|in:1,2', // 1=enviado, 2=fallido
            'error_message' => 'nullable|string|max:500',
        ]);

        $testSms = TestSmsSend::findOrFail($id);
        $testSms->update([
            'status'        => $request->input('status'),
            'error_message' => $request->input('error_message'),
        ]);

        TestSmsSent::dispatch($testSms);

        return response()->json(['status' => 'ok']);
    }
}
