<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyCreditTransaction extends Model
{
    protected $fillable = [
        'company_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'concept',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type'           => 'integer',
            'amount'         => 'decimal:4',
            'balance_before' => 'decimal:4',
            'balance_after'  => 'decimal:4',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 1 ? 'Recarga' : 'Cargo';
    }

    public function getTypeColorAttribute(): string
    {
        return $this->type === 1 ? 'ok' : 'err';
    }
}
