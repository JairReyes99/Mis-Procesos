<?php

namespace App\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

/**
 * Aplica este trait a cualquier modelo que deba estar aislado por empresa.
 * Agrega un scope global que filtra automáticamente por la empresa del usuario logueado.
 * El super-admin (company_id = null) ve todos los registros sin filtro.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check() && auth()->user()->company_id !== null) {
                $query->where(
                    (new static)->getTable() . '.company_id',
                    auth()->user()->company_id
                );
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->company_id !== null && empty($model->company_id)) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
