<?php

namespace App\Providers;

use App\View\Composers\FooterComposer;
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
        // Register footer composer for footer component
        View::composer('components.footer', FooterComposer::class);

        // Global composer: inject safe defaults to all views
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
