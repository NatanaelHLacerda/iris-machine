<?php

namespace App\Enums;

enum LostReason: string
{
    case SemResposta = 'sem_resposta';
    case Preco = 'preco';
    case Timing = 'timing';
    case ForaDoPublico = 'fora_do_publico';
    case EscolheuOutro = 'escolheu_outro';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::SemResposta => 'Sem resposta',
            self::Preco => 'Preço',
            self::Timing => 'Não é o momento',
            self::ForaDoPublico => 'Fora do público',
            self::EscolheuOutro => 'Escolheu outra opção',
            self::Outro => 'Outro',
        };
    }
}
