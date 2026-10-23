<?php

namespace Tests\Unit\Crm;

use App\Enums\Channel;
use App\Support\Crm\ChannelClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChannelClassifierTest extends TestCase
{
    /**
     * @return array<string, array{?string, ?string, ?string, Channel}>
     */
    public static function cases(): array
    {
        return [
            'utm do meta' => ['facebook', null, null, Channel::Meta],
            'utm ig em caixa alta' => ['IG', null, null, Channel::Meta],
            'só fbclid' => [null, 'abc', null, Channel::Meta],
            'utm do google' => ['google', null, null, Channel::Google],
            'outra utm' => ['newsletter', null, null, Channel::Outro],
            'buscador sem utm' => [null, null, 'https://www.google.com/search?q=iris', Channel::Organico],
            'link do instagram sem utm' => [null, null, 'https://l.instagram.com/', Channel::Meta],
            'sem nada' => [null, null, null, Channel::Direto],
            'outro site' => [null, null, 'https://blog.example.com/post', Channel::Outro],
        ];
    }

    #[DataProvider('cases')]
    public function test_it_classifies_the_channel(?string $utmSource, ?string $fbclid, ?string $referrer, Channel $expected): void
    {
        $this->assertSame($expected, ChannelClassifier::classify($utmSource, $fbclid, $referrer));
    }
}
