<?php

namespace App\Providers;

use App\Models\Plantation;
use App\Observers\PlantationObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Plantation::observe(PlantationObserver::class);
    }
}
