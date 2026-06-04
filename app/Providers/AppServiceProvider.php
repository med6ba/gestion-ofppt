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

        \Illuminate\Support\Facades\Gate::define('update-settings', function ($user) {
            return $user->isDirecteur();
        });

        View::composer('*', function ($view) {
            $user = auth()->user();

            $unreadNotifications = $user ? $user->unreadNotifications()->take(6)->get() : collect();
            $unreadCount = $user ? $user->unreadNotifications()->count() : 0;
            
            $unreadCountByCategory = collect();
            if ($user) {
                $unreadCountByCategory = $user->unreadNotifications()
                    ->reorder()
                    ->get(['data'])
                    ->countBy(fn ($notification) => $notification->data['category'] ?? 'system');
            }

            $view->with('unreadNotifications', $unreadNotifications);
            $view->with('unreadCount', $unreadCount);
            $view->with('unreadCountByCategory', $unreadCountByCategory);
            $view->with('currentLocale', app()->getLocale());
            $view->with('textDirection', app()->getLocale() === 'ar' ? 'rtl' : 'ltr');
        });
    }
}
