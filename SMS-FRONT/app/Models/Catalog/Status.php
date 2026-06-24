<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $table = 'statuses';

    public const ACTIVE = 1;
    public const INACTIVE = 2;
    public const DELETED = 3;

    protected $fillable = [
        'name',
        'description',
    ];
}
