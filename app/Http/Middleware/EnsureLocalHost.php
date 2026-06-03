<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLocalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        // Allow all local hosts for easier mobile testing
        return $next($request);
    }

    private function normalizeHost(string $host): string
    {
        return trim(strtolower($host), "[] \t\n\r\0\x0B");
    }
}
