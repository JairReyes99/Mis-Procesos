<?php

namespace App\Http\Controllers\Api;

use App\Events\CampaignCompleted;
use App\Events\CampaignProgress;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\CompanyCreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignProgressController extends Controller
{
    public function __construct(private CompanyCreditService $credits) {}

    public function update(Request $request, string $id): JsonResponse
    {
        // C-03: fail-closed — reject all requests if secret is not configured
        $secret = config('app.campaign_webhook_secret');
        if (empty($secret)) {
            \Log::critical('CAMPAIGN_WEBHOOK_SECRET not configured — webhook rejected');
            return response()->json(['error' => 'Webhook not configured'], 500);
        }
        if (!hash_equals($secret, (string) $request->bearerToken())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'sent'         => 'required|integer|min:0',
            'failed'       => 'required|integer|min:0',
            'completed'    => 'boolean',
            'paused_reason' => 'nullable|string|in:no_balance',
        ]);

        // C-05: dispatch events AFTER transaction to avoid coupling broadcast to DB lock
        $eventData = DB::transaction(function () use ($request, $id) {
            $campaign = Campaign::with('company')
                ->lockForUpdate()
                ->findOrFail($id);

            // Guard: ignore webhooks on already-terminal campaigns
            if (in_array($campaign->campaign_status, [4, 6])) {
                return ['skip' => true];
            }

            $pausedReason = $request->input('paused_reason');
            $prevSent     = (int) $campaign->sent_count;
            $prevFailed   = (int) $campaign->failed_count;
            $newSent      = $request->integer('sent');
            $newFailed    = $request->integer('failed');

            // Idempotency: duplicate progress webhook (Python retry), skip silently
            if (!$request->boolean('completed')
                && $newSent <= $prevSent
                && $newFailed <= $prevFailed
                && $pausedReason === null) {
                return ['skip' => true];
            }

            $deltaSent   = max(0, $newSent   - $prevSent);
            $deltaFailed = max(0, $newFailed - $prevFailed);

            $campaign->sent_count   = $newSent;
            $campaign->failed_count = $newFailed;

            $this->updateRecipients($campaign, $deltaSent, $deltaFailed);

            $cost       = 0;
            $newBalance = (float) $campaign->company->balance;

            if ($request->boolean('completed') && $campaign->campaign_status !== 4) {
                $campaign->campaign_status = 4;
                $campaign->completed_at    = now();

                $totalSentCost = round(
                    (float) $campaign->recipients()->where('send_status', 2)->sum('cost'),
                    4
                );

                if ($totalSentCost <= 0) {
                    $exitosos      = max(0, (int) $campaign->sent_count - (int) $campaign->failed_count);
                    $totalSentCost = round($exitosos * $this->credits->smsPrice($campaign->company), 4);
                }

                $delta    = round($totalSentCost - (float) $campaign->charged_cost, 4);
                $segments = (int) $campaign->recipients()->where('send_status', 2)->sum('segments') ?: 0;

                if ($delta > 0) {
                    try {
                        $this->credits->charge(
                            $campaign->company,
                            $delta,
                            "Campaña #{$campaign->id} — {$campaign->name} ({$segments} seg. enviados)",
                            null,
                            (int) $campaign->created_by
                        );
                        $campaign->charged_cost = $totalSentCost;
                        $newBalance = (float) $campaign->company->fresh()->balance;
                    } catch (\Exception $e) {
                        \Log::error("Campaign #{$campaign->id} charge failed: " . $e->getMessage());
                    }
                }

                $campaign->charged_at = now();
                $cost = $totalSentCost;
                $campaign->save();

                return ['completed' => true, 'campaign' => $campaign, 'cost' => $cost, 'balance' => $newBalance];
            }

            // Auto-pause by Python (no_balance) — set status if not already paused
            if ($pausedReason === 'no_balance' && $campaign->campaign_status !== 5) {
                $campaign->campaign_status = 5;
            }
            $campaign->save();

            return ['completed' => false, 'campaign' => $campaign, 'paused_reason' => $pausedReason];
        });

        if ($eventData['skip'] ?? false) {
            return response()->json(['status' => 'ok']);
        }

        if ($eventData['completed']) {
            CampaignCompleted::dispatch($eventData['campaign'], $eventData['cost'], $eventData['balance']);
        } else {
            CampaignProgress::dispatch($eventData['campaign'], $eventData['paused_reason']);
        }

        return response()->json(['status' => 'ok']);
    }

    private function updateRecipients(Campaign $campaign, int $deltaSent, int $deltaFailed): void
    {
        $now = now()->format('Y-m-d H:i:s.v');
        $cid = $campaign->id;

        // Wrapped in the outer DB::transaction — both UPDATEs are atomic together
        if ($deltaFailed > 0) {
            DB::statement("
                UPDATE cr
                SET cr.send_status = 3,
                    cr.updated_at  = ?
                FROM campaign_recipients cr
                INNER JOIN (
                    SELECT TOP(?) id
                    FROM campaign_recipients
                    WHERE campaign_id = ? AND send_status = 1
                    ORDER BY id ASC
                ) sub ON cr.id = sub.id
            ", [$now, $deltaFailed, $cid]);
        }

        if ($deltaSent > 0) {
            $price = $this->credits->smsPrice($campaign->company);

            DB::statement("
                UPDATE cr
                SET cr.send_status = 2,
                    cr.sent_at     = ?,
                    cr.cost        = cr.segments * ?,
                    cr.updated_at  = ?
                FROM campaign_recipients cr
                INNER JOIN (
                    SELECT TOP(?) id
                    FROM campaign_recipients
                    WHERE campaign_id = ? AND send_status = 1
                    ORDER BY id ASC
                ) sub ON cr.id = sub.id
            ", [$now, $price, $now, $deltaSent, $cid]);
        }
    }
}
