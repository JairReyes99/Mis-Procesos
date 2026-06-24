<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipientSendStatus extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'order'];

    public function recipients()
    {
        return $this->hasMany(CampaignRecipient::class, 'send_status', 'id');
    }
}
