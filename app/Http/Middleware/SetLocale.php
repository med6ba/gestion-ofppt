<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('app.supported_locales', ['fr', 'ar', 'en']);
        $locale = session('locale', config('app.locale', 'fr'));

        if (!in_array($locale, $supportedLocales, true)) {
            $locale = config('app.locale', 'fr');
        }

        App::setLocale($locale);
        session([
            'locale' => $locale,
            'direction' => $locale === 'ar' ? 'rtl' : 'ltr',
        ]);

        return $next($request);
    }
}
