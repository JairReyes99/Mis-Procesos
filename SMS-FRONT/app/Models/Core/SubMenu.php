<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Status;
use OwenIt\Auditing\Contracts\Auditable;

class SubMenu extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'sub_menus';

    protected $fillable = [
        'menu_id',
        'name',
        'description',
        'visible_menu',
        'icon',
        'route',
        'order',
        'permission',
        'status_id',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
