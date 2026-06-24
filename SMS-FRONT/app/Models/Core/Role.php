<?php

namespace App\Models\Core;

use Spatie\Permission\Models\Role as SpatieRole;
use OwenIt\Auditing\Contracts\Auditable;

class Role extends SpatieRole implements Auditable
{
    use \OwenIt\Auditing\Auditable;
}
