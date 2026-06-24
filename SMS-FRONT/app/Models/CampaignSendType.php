<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignSendType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'order',
        'status_id',
    ];

    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'send_type_id');
    }
}
