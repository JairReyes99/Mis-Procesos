<?php

namespace App\Events;

use App\Models\Campaign;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

// C-10: ShouldBroadcast (queued) instead of ShouldBroadcastNow
class CampaignCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Campaign $campaign,
        public float $cost,
        public float $newBalance
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('campaign.' . $this->campaign->id)];
    }

    public function broadcastAs(): string
    {
        return 'completed';
    }

    public function broadcastQueue(): string
    {
        return 'broadcasting';
    }

    public function broadcastWith(): array
    {
        return [
            'sent_count'    => $this->campaign->sent_count,
            'failed_count'  => $this->campaign->failed_count,
            'total'         => $this->campaign->total_recipients,
            'percent'       => 100,
            'cost'          => '$' . number_format($this->cost, 2),
            'balance'       => '$' . number_format($this->newBalance, 2) . ' MXN',
        ];
    }
}
