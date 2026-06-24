<?php

namespace App\Models\Core;

use Spatie\Permission\Models\Permission as SpatiePermission;
use OwenIt\Auditing\Contracts\Auditable;

class Permission extends SpatiePermission implements Auditable
{
    use \OwenIt\Auditing\Auditable;
}
