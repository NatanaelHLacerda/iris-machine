<?php

namespace App\Support\Crm;

use App\Models\AdSpend;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Lê o CSV exportado do Gerenciador de Anúncios (pt-BR ou inglês) e faz upsert por dia × campanha × conjunto × anúncio.
 */
class MetaAdsCsvImporter
{
    /**
     * @var array<string, array<int, string>>
     */
    private const array COLUMNS = [
        'date' => ['dia', 'day', 'início dos relatórios', 'reporting starts', 'data'],
        'campaign' => ['nome da campanha', 'campaign name'],
        'spend' => ['valor usado (brl)', 'amount spent (brl)', 'valor usado', 'amount spent', 'valor gasto (brl)'],
        'adset' => ['nome do conjunto de anúncios', 'ad set name'],
        'ad' => ['nome do anúncio', 'ad name'],
        'impressions' => ['impressões', 'impressions'],
        'link_clicks' => ['cliques no link', 'link clicks'],
    ];

    private const array REQUIRED = ['date', 'campaign', 'spend'];

    private const array LABELS = ['date' => 'Dia', 'campaign' => 'Nome da campanha', 'spend' => 'Valor usado (BRL)'];

    /**
     * @return array{created: int, updated: int, skipped: int}
     *
     * @throws ValidationException
     */
    public function import(string $path): array
    {
        $content = (string) file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $lines = preg_split('/\r\n|\n|\r/', trim($content));
        $delimiter = substr_count($lines[0] ?? '', ';') > substr_count($lines[0] ?? '', ',') ? ';' : ',';

        $header = array_map(fn ($cell) => mb_strtolower(trim((string) $cell)), str_getcsv(array_shift($lines) ?? '', $delimiter));
        $map = $this->mapColumns($header);

        $rows = [];
        $skipped = 0;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = str_getcsv($line, $delimiter);
            $row = $this->parseRow($cells, $map);

            if ($row === null) {
                $skipped++;

                continue;
            }

            $key = implode('|', [$row['date'], $row['campaign_key'], $row['adset_key'], $row['ad_key']]);

            if (isset($rows[$key])) {
                $rows[$key]['spend_cents'] += $row['spend_cents'];
            } else {
                $rows[$key] = $row;
            }
        }

        return DB::transaction(function () use ($rows, $skipped) {
            $existing = AdSpend::query()
                ->where('platform', 'meta')
                ->whereIn('date', array_unique(array_column($rows, 'date')))
                ->get(['date', 'campaign_key', 'adset_key', 'ad_key'])
                ->map(fn (AdSpend $spend) => implode('|', [$spend->date, $spend->campaign_key, $spend->adset_key, $spend->ad_key]))
                ->flip();

            $updated = collect(array_keys($rows))->filter(fn (string $key) => $existing->has($key))->count();
            $now = now();

            foreach (array_chunk(array_values($rows), 500) as $chunk) {
                AdSpend::upsert(
                    array_map(fn (array $row) => [...$row, 'platform' => 'meta', 'source' => 'csv', 'created_at' => $now, 'updated_at' => $now], $chunk),
                    ['platform', 'date', 'campaign_key', 'adset_key', 'ad_key'],
                    ['campaign', 'adset', 'ad', 'spend_cents', 'impressions', 'link_clicks', 'source', 'updated_at'],
                );
            }

            return ['created' => count($rows) - $updated, 'updated' => $updated, 'skipped' => $skipped];
        });
    }

    /**
     * @param  array<int, string>  $header
     * @return array<string, int>
     *
     * @throws ValidationException
     */
    private function mapColumns(array $header): array
    {
        $map = [];

        foreach (self::COLUMNS as $field => $aliases) {
            foreach ($aliases as $alias) {
                $index = array_search($alias, $header, true);

                if ($index !== false) {
                    $map[$field] = $index;

                    break;
                }
            }
        }

        $missing = array_diff(self::REQUIRED, array_keys($map));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => 'O CSV não tem as colunas: '.implode(', ', array_map(fn ($field) => self::LABELS[$field], $missing)).'. Exporte o relatório com detalhamento por dia.',
            ]);
        }

        return $map;
    }

    /**
     * @param  array<int, string|null>  $cells
     * @param  array<string, int>  $map
     * @return array<string, mixed>|null
     */
    private function parseRow(array $cells, array $map): ?array
    {
        $value = fn (string $field) => isset($map[$field]) ? trim((string) ($cells[$map[$field]] ?? '')) : '';

        $date = $this->parseDate($value('date'));
        $campaign = $value('campaign');

        if ($date === null || $campaign === '') {
            return null;
        }

        try {
            $spend = Money::toCents($value('spend') ?: '0');
        } catch (\InvalidArgumentException) {
            return null;
        }

        $integer = fn (string $field) => $value($field) === '' ? null : (int) preg_replace('/\D/', '', $value($field));

        return [
            'date' => $date,
            'campaign' => $campaign,
            'adset' => $value('adset') ?: null,
            'ad' => $value('ad') ?: null,
            'campaign_key' => UtmKey::normalize($campaign),
            'adset_key' => UtmKey::normalize($value('adset')) ?? '',
            'ad_key' => UtmKey::normalize($value('ad')) ?? '',
            'spend_cents' => max(0, (int) $spend),
            'impressions' => $integer('impressions'),
            'link_clicks' => $integer('link_clicks'),
        ];
    }

    private function parseDate(string $value): ?string
    {
        foreach (['Y-m-d', 'd/m/Y', 'm/d/Y'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat("!{$format}", $value);
            } catch (\Throwable) {
                continue;
            }

            if ($date && $date->format($format) === $value) {
                return $date->toDateString();
            }
        }

        return null;
    }
}
