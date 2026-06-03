<?php

namespace App\Services;

class AttendanceSecurityService
{
    public function isAllowedIp(?string $ip): bool
    {
        if (!$ip) {
            return false;
        }

        foreach (config('smartcampus.allowed_ip_ranges', []) as $range) {
            if ($this->matchesRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    private function matchesRange(string $ip, string $range): bool
    {
        if ($range === '*' || $ip === $range) {
            return true;
        }

        if ($ip === '::1' && $range === '::1') {
            return true;
        }

        if (!str_contains($range, '/')) {
            return false;
        }

        [$subnet, $bits] = explode('/', $range, 2);

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || !filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $bits = (int) $bits;
        $mask = -1 << (32 - $bits);

        return (ip2long($ip) & $mask) === (ip2long($subnet) & $mask);
    }
}
