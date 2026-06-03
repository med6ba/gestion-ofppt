<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLocalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $this->normalizeHost($request->getHost());

        if ($this->isAllowedLocalHost($host)) {
            return $next($request);
        }

        abort(404);
    }

    private function isAllowedLocalHost(string $host): bool
    {
        if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
            return true;
        }

        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        // Private, loopback, and reserved IPs are accepted for LAN/mobile testing.
        return true;
    }

    private function normalizeHost(string $host): string
    {
        return trim(strtolower($host), "[] \t\n\r\0\x0B");
    }
}
