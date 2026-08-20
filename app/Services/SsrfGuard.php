<?php

namespace App\Services;

class SsrfGuard
{
    /**
     * Whitelisted streaming and CDN host domains.
     */
    protected static array $trustedDomains = [
        'aoneroom.com',
        'inmoviebox.com',
        'anichin.bio',
        'dramabox.com',
        'hwzthls.com',
        'shortmax.com',
        'zencdn.net',
        'tmdb.org',
        'themoviedb.org',
        'imdb.com',
        'media-amazon.com',
        'faiilmov.my.id',
    ];

    /**
     * Check if a URL is safe to fetch from server-side.
     */
    public static function isSafeUrl(?string $url, bool $strictCdnWhitelist = false): bool
    {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');
        $host = strtolower($parsed['host'] ?? '');

        // 1. Only allow HTTP and HTTPS
        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        // 2. Reject empty host or localhost
        if (empty($host) || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return false;
        }

        // 3. Resolve DNS to IP and check for private / loopback / metadata ranges
        $ip = gethostbyname($host);
        if ($ip === $host && !filter_var($ip, FILTER_VALIDATE_IP)) {
            // DNS resolution failed
            return false;
        }

        // 4. Validate IP is public (not private, not reserved, not loopback)
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        // 5. Explicitly block cloud metadata IP (169.254.169.254) and link-local (169.254.0.0/16)
        if (str_starts_with($ip, '169.254.') || str_starts_with($ip, '127.') || str_starts_with($ip, '0.')) {
            return false;
        }

        // 6. Optional strict CDN whitelist verification
        if ($strictCdnWhitelist) {
            $isTrusted = false;
            foreach (self::$trustedDomains as $trusted) {
                if ($host === $trusted || str_ends_with($host, '.' . $trusted)) {
                    $isTrusted = true;
                    break;
                }
            }
            if (!$isTrusted) {
                return false;
            }
        }

        return true;
    }
}
