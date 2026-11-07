<?php

namespace App\Providers;

use App\Models\Category;
use App\Services\PresenceService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.site', function ($view) {
            $view->with('navCategories', Category::query()->orderBy('sort_order')->get(['name', 'slug']));
        });

        // Usuarios en línea (últimos 5 min), solo para el administrador. Se comparte
        // con todas las vistas del panel (incluidas las que extienden layouts.admin).
        View::composer('layouts.admin', function ($view) {
            $user = auth()->user();

            $online = $user?->hasRole('admin')
                ? app(PresenceService::class)->onlineUsers()
                : collect();

            View::share('onlineUsers', $online);
        });
    }
}
