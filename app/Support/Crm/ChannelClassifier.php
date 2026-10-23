<?php

namespace App\Support\Crm;

use App\Enums\Channel;

class ChannelClassifier
{
    private const array META_SOURCES = ['facebook', 'instagram', 'meta', 'fb', 'ig'];

    private const array SEARCH_ENGINES = ['google.', 'bing.', 'duckduckgo.', 'yahoo.'];

    /**
     * Deriva o canal de aquisição da UTM, do fbclid e do referrer.
     */
    public static function classify(?string $utmSource, ?string $fbclid = null, ?string $referrer = null): Channel
    {
        $source = strtolower(trim((string) $utmSource));

        if (in_array($source, self::META_SOURCES, true) || filled($fbclid)) {
            return Channel::Meta;
        }

        if ($source === 'google') {
            return Channel::Google;
        }

        if ($source !== '') {
            return Channel::Outro;
        }

        if (blank($referrer)) {
            return Channel::Direto;
        }

        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));

        foreach (self::SEARCH_ENGINES as $engine) {
            if (str_contains($host, $engine)) {
                return Channel::Organico;
            }
        }

        if (str_contains($host, 'facebook.') || str_contains($host, 'instagram.')) {
            return Channel::Meta;
        }

        return Channel::Outro;
    }
}
