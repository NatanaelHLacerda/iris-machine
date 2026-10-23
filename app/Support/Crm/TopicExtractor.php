<?php

namespace App\Support\Crm;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Palavras mais citadas em "o que você quer destravar", para orientar conteúdo e anúncios.
 */
class TopicExtractor
{
    private const array STOPWORDS = [
        'para', 'como', 'mais', 'quero', 'minha', 'meus', 'minhas', 'meu', 'sobre', 'fazer', 'usar', 'isso',
        'esse', 'essa', 'este', 'esta', 'pela', 'pelo', 'porque', 'quando', 'ainda', 'muito', 'entre', 'cada',
        'tenho', 'estou', 'preciso', 'aprender', 'conseguir', 'sem', 'com', 'que', 'uma', 'umas', 'uns', 'dos',
        'das', 'nos', 'nas', 'vez', 'mesmo', 'melhor', 'algo', 'coisa', 'coisas', 'saber', 'onde', 'qual',
    ];

    /**
     * @param  Collection<int, ?string>  $texts
     * @return array<string, int>
     */
    public static function top(Collection $texts, int $limit = 10): array
    {
        return $texts
            ->filter()
            ->flatMap(fn (string $text) => array_unique(preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($text)), flags: PREG_SPLIT_NO_EMPTY)))
            ->filter(fn (string $word) => strlen($word) >= 4 && ! in_array($word, self::STOPWORDS, true))
            ->countBy()
            ->sortDesc()
            ->take($limit)
            ->all();
    }
}
