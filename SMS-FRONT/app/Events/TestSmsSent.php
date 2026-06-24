<?php

namespace App\Events;

use App\Models\TestSmsSend;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class TestSmsSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public TestSmsSend $testSms) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('test-sms.' . $this->testSms->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'result';
    }

    public function broadcastWith(): array
    {
        return [
            'id'     => $this->testSms->id,
            'status' => $this->testSms->status, // 1=enviado, 2=fallido
            'error'  => $this->testSms->error_message,
        ];
    }
}
