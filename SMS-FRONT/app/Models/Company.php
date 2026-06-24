<?php

namespace App\Models;

use App\Models\Catalog\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// User is in the same namespace but explicit import is required for static analysis / IDE support
use App\Models\User;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'rfc',
        'email',
        'phone',
        'status_id',
        'balance',
        'settings',
        'stripe_customer_id',
        'stripe_pm_id',
        'auto_recharge_enabled',
        'auto_recharge_amount',
        'auto_recharge_threshold',
    ];

    protected function casts(): array
    {
        return [
            'settings'               => 'array',
            'status_id'              => 'integer',
            'balance'                => 'decimal:4',
            'auto_recharge_enabled'  => 'boolean',
            'auto_recharge_amount'   => 'decimal:2',
            'auto_recharge_threshold' => 'decimal:2',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(CompanyCreditTransaction::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function isActive(): bool
    {
        return $this->status_id === 1;
    }

    public function hasStripeCard(): bool
    {
        return !empty($this->stripe_pm_id);
    }

    public function shouldAutoRecharge(): bool
    {
        return $this->auto_recharge_enabled
            && !empty($this->stripe_pm_id)
            && $this->auto_recharge_amount > 0;
    }
}
