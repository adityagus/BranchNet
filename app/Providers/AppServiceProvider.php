<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        Gate::define('manage-network', fn ($user) => $user->isNetworkAdmin());
        Gate::define('manage-users', fn ($user) => $user->isSuperAdmin());
        Gate::define('view-network', fn ($user) => in_array($user->role, ['super_admin', 'network_admin', 'viewer'], true));
    }
}
