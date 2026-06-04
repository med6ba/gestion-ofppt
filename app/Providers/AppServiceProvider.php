<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
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
        app('translator')->getLoader()->addPath(resource_path('lang'));

        View::composer('*', function ($view) {
            $user = auth()->user();

            $view->with('unreadNotifications', $user ? $user->unreadNotifications()->take(6)->get() : collect());
            $view->with('unreadCount', $user ? $user->unreadNotifications()->count() : 0);
            $view->with('currentLocale', app()->getLocale());
            $view->with('textDirection', app()->getLocale() === 'ar' ? 'rtl' : 'ltr');
        });
    }
}
