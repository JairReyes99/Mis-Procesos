<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StripePaymentEvent extends Model
{
    protected $fillable = [
        'stripe_event_id',
        'event_type',
        'status',
        'company_id',
        'notes',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public static function alreadyProcessed(string $eventId): bool
    {
        return static::where('stripe_event_id', $eventId)
            ->where('status', 'processed')
            ->exists();
    }
}
