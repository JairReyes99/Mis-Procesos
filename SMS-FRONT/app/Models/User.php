<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use OwenIt\Auditing\Contracts\Auditable;
use App\Models\Catalog\Status;

class User extends Authenticatable implements Auditable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, \OwenIt\Auditing\Auditable;

    /** Default password assigned on reset */
    const DEFAULT_PASSWORD = 'contraseña';

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'active_role_id',
        'status_id',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'must_change_password' => 'boolean',
            'status_id'            => 'integer',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->company_id === null;
    }

    public function activeRole()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'active_role_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
