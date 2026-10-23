<?php

namespace App\Enums;

/**
 * "Seu momento" no formulário da landing; valores iguais às chaves de config('iris.levels').
 */
enum LeadLevel: string
{
    case Estudante = 'estudante';
    case Estagiario = 'estagiario';
    case Junior = 'junior';
    case Outro = 'outro';

    public function label(): string
    {
        return config("iris.levels.{$this->value}");
    }
}
