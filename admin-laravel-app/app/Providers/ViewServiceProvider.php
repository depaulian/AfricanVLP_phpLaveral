<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Global composer: inject safe defaults to all admin views
        View::composer('*', function ($view) {
            $data = $view->getData();

            $defaults = [
                'pageTitle' => $data['pageTitle'] ?? null,
                'breadcrumbs' => $data['breadcrumbs'] ?? [],
                'flash' => [
                    'success' => Session::get('success'),
                    'error' => Session::get('error'),
                    'warning' => Session::get('warning'),
                    'info' => Session::get('info'),
                ],
                'authUser' => $data['authUser'] ?? Auth::user(),
            ];

            $view->with($defaults);
        });
    }
}
