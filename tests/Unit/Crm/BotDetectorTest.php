<?php

namespace Tests\Unit\Crm;

use App\Support\Crm\BotDetector;
use PHPUnit\Framework\TestCase;

class BotDetectorTest extends TestCase
{
    public function test_it_flags_crawlers_and_empty_agents(): void
    {
        $this->assertTrue(BotDetector::isBot(null));
        $this->assertTrue(BotDetector::isBot('Mozilla/5.0 (compatible; Googlebot/2.1)'));
        $this->assertTrue(BotDetector::isBot('Mozilla/5.0 HeadlessChrome/120.0'));
        $this->assertTrue(BotDetector::isBot('facebookexternalhit/1.1'));
    }

    public function test_it_lets_real_browsers_through(): void
    {
        $this->assertFalse(BotDetector::isBot('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1'));
    }
}
