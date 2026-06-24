<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Status;
use OwenIt\Auditing\Contracts\Auditable;

class Menu extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'menus';

    protected $fillable = [
        'name',
        'icon',
        'visible_menu',
        'order',
        'status_id',
    ];

    public function subMenus()
    {
        return $this->hasMany(SubMenu::class, 'menu_id');
        // Relation order by order
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
