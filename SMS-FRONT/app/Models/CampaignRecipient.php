<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignRecipient extends Model
{
    protected $fillable = [
        'campaign_id',
        'phone',
        'message',
        'segments',
        'encoding',
        'cost',
        'send_status',
        'sent_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'segments'          => 'integer',
            'send_status'       => 'integer',
            'sent_at'           => 'datetime',
            'cost' => 'decimal:4',
        ];
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function sendStatusCatalog()
    {
        return $this->belongsTo(RecipientSendStatus::class, 'send_status', 'id');
    }
}
