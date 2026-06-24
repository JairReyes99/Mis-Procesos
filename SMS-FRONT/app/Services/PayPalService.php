<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalService
{
    private string $baseUrl;
    private ?string $clientId;
    private ?string $clientSecret;

    public function __construct()
    {
        $this->clientId     = config('paypal.client_id');
        $this->clientSecret = config('paypal.client_secret');
        $this->baseUrl      = config('paypal.mode') === 'live'
            ? config('paypal.live_url')
            : config('paypal.sandbox_url');
    }

    // ─── Auth ──────────────────────────────────────────────────────────────────

    private function getAccessToken(): string
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('PayPal auth failed: ' . $response->body());
        }

        return $response->json('access_token');
    }

    // ─── Orders API v2 ─────────────────────────────────────────────────────────

    /**
     * Crea una Order de PayPal. Retorna el orderID para el SDK JS.
     */
    public function createOrder(Company $company, float $amountMxn): array
    {
        $token    = $this->getAccessToken();
        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent'         => 'CAPTURE',
                'purchase_units' => [[
                    'amount'      => [
                        'currency_code' => config('paypal.currency', 'MXN'),
                        'value'         => number_format($amountMxn, 2, '.', ''),
                    ],
                    'description' => "Recarga de créditos SMS - {$company->name}",
                    'custom_id'   => (string) $company->id,
                ]],
            ]);

        if (!$response->successful()) {
            Log::error('PayPal createOrder failed', [
                'company_id' => $company->id,
                'response'   => $response->json(),
            ]);
            throw new \RuntimeException('No se pudo crear la order de PayPal.');
        }

        return $response->json();
    }

    /**
     * Captura el pago después de la aprobación del usuario.
     */
    public function captureOrder(string $orderId): array
    {
        $token    = $this->getAccessToken();
        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

        if (!$response->successful()) {
            Log::error('PayPal captureOrder failed', [
                'order_id' => $orderId,
                'response' => $response->json(),
            ]);
            throw new \RuntimeException('No se pudo capturar el pago de PayPal.');
        }

        return $response->json();
    }

    /**
     * Verifica la autenticidad de un webhook de PayPal via API de verificación.
     */
    public function verifyWebhookSignature(array $headers, string $body): bool
    {
        $token    = $this->getAccessToken();
        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v1/notifications/verify-webhook-signature", [
                'transmission_id'   => $headers['PAYPAL-TRANSMISSION-ID'] ?? '',
                'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? '',
                'cert_url'          => $headers['PAYPAL-CERT-URL'] ?? '',
                'auth_algo'         => $headers['PAYPAL-AUTH-ALGO'] ?? '',
                'transmission_sig'  => $headers['PAYPAL-TRANSMISSION-SIG'] ?? '',
                'webhook_id'        => config('paypal.webhook_id'),
                'webhook_event'     => json_decode($body, true),
            ]);

        return $response->json('verification_status') === 'SUCCESS';
    }
}
