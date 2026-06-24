<?php

use App\Models\Company;

if (! function_exists('current_company')) {
    function current_company(): ?Company
    {
        return app()->has('current_company') ? app('current_company') : null;
    }
}
