<?php

namespace App\Events;

use App\Models\Campaign;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

// C-10: ShouldBroadcast (queued) instead of ShouldBroadcastNow — webhook returns 200
// immediately; Reverb latency no longer blocks the Python worker.
class CampaignProgress implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public Campaign $campaign, public ?string $pausedReason = null) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('campaign.' . $this->campaign->id)];
    }

    public function broadcastAs(): string
    {
        return 'progress';
    }

    public function broadcastQueue(): string
    {
        return 'broadcasting';
    }

    public function broadcastWith(): array
    {
        $total = $this->campaign->total_recipients ?: 1;

        return [
            'sent_count'    => $this->campaign->sent_count,
            'failed_count'  => $this->campaign->failed_count,
            'total'         => $this->campaign->total_recipients,
            'percent'       => round(($this->campaign->sent_count / $total) * 100, 1),
            'status'        => $this->campaign->campaign_status,
            'status_label'  => $this->campaign->statusLabel,
            'status_color'  => $this->campaign->statusColor,
            'paused_reason' => $this->pausedReason,
        ];
    }
}
