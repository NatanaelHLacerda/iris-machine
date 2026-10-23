<?php

namespace App\Support\Crm;

class BotDetector
{
    private const string PATTERN = '/bot|crawl|spider|slurp|headless|lighthouse|preview|facebookexternalhit|curl|wget|python-requests/i';

    public static function isBot(?string $userAgent): bool
    {
        return blank($userAgent) || preg_match(self::PATTERN, $userAgent) === 1;
    }
}
