<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLocalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        $allowedHosts = array_filter(array_map(
            fn (string $host): string => $this->normalizeHost($host),
            explode(',', (string) env('APP_LOCAL_HOSTS', 'localhost,127.0.0.1,::1'))
        ));

        if ($allowedHosts !== [] && ! in_array($this->normalizeHost($request->getHost()), $allowedHosts, true)) {
            abort(404);
        }

        return $next($request);
    }

    private function normalizeHost(string $host): string
    {
        return trim(strtolower($host), "[] \t\n\r\0\x0B");
    }
}
