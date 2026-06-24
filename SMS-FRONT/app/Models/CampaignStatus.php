<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignStatus extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'order'];

    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'campaign_status', 'id');
    }
}
