<?php

namespace App\Enums;

enum CashCategory: string
{
    case Alunos = 'alunos';
    case Anuncios = 'anuncios';
    case Ferramentas = 'ferramentas';
    case Infraestrutura = 'infraestrutura';
    case Impostos = 'impostos';
    case ProLabore = 'pro_labore';
    case Outros = 'outros';
    case OutrasEntradas = 'outras_entradas';

    public function label(): string
    {
        return match ($this) {
            self::Alunos => 'Pagamentos de alunos',
            self::Anuncios => 'Anúncios',
            self::Ferramentas => 'Ferramentas',
            self::Infraestrutura => 'Infraestrutura',
            self::Impostos => 'Impostos',
            self::ProLabore => 'Pró-labore',
            self::Outros => 'Outros',
            self::OutrasEntradas => 'Outras entradas',
        };
    }

    /**
     * Categorias disponíveis para lançamento manual em cada direção.
     *
     * @return array<int, self>
     */
    public static function manual(CashDirection $direction): array
    {
        return $direction === CashDirection::In
            ? [self::OutrasEntradas]
            : [self::Ferramentas, self::Infraestrutura, self::Impostos, self::ProLabore, self::Anuncios, self::Outros];
    }
}
