<?php

namespace App\Providers;

use App\Models\Affectation;
use App\Models\Equipement;
use App\Models\Maintenance;
use App\Models\Panne;
use App\Observers\AffectationObserver;
use App\Observers\EquipementObserver;
use App\Observers\MaintenanceObserver;
use App\Observers\PanneObserver;
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
        Equipement::observe(EquipementObserver::class);
        Affectation::observe(AffectationObserver::class);
        Panne::observe(PanneObserver::class);
        Maintenance::observe(MaintenanceObserver::class);
    }
}
