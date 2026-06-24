<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->company_id !== null) {
            // Company::find() respects SoftDeletes global scope — returns null if soft-deleted.
            $company = Company::find(auth()->user()->company_id);

            if ($company) {
                App::instance('current_company', $company);
                view()->share('currentCompany', $company);
            } else {
                // Company was deleted or does not exist — share null so views don't error.
                view()->share('currentCompany', null);
            }
        } else {
            // Super-admin (company_id === null) or unauthenticated — no company context.
            view()->share('currentCompany', null);
        }

        return $next($request);
    }
}
