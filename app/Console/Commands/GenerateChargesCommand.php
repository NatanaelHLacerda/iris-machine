<?php

namespace App\Console\Commands;

use App\Support\Crm\ChargeGenerator;
use App\Support\Crm\Period;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('crm:generate-charges')]
#[Description('Gera as cobranças do mês das assinaturas ativas')]
class GenerateChargesCommand extends Command
{
    public function handle(ChargeGenerator $generator): int
    {
        $created = $generator->run(Period::today());

        $this->info("{$created} cobrança(s) criada(s).");

        return self::SUCCESS;
    }
}
