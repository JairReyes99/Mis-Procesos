<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\StripeService::class);
        $this->app->singleton(\App\Services\PayPalService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer(
            ['partials._aside'],
            \App\View\Composers\MenuComposer::class
        );

        // C-03: fail hard on production if webhook secret is missing
        if ($this->app->isProduction() && empty(config('app.campaign_webhook_secret'))) {
            throw new \RuntimeException(
                'CAMPAIGN_WEBHOOK_SECRET must be configured in production.'
            );
        }
    }
}
