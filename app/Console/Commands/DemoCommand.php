<?php

namespace App\Console\Commands;

use App\Models\Lead;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('crm:demo {--fresh : Recria o banco antes de popular} {--scale=1 : Multiplica o volume de visitas (testes usam menos)}')]
#[Description('Popula o CRM com 6 meses de dados fictícios coerentes (só fora de produção)')]
class DemoCommand extends Command
{
    public function handle(): int
    {
        if ($this->laravel->isProduction()) {
            $this->error('crm:demo não roda em produção.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->call('migrate:fresh', ['--force' => true]);
        } elseif (Lead::query()->exists()) {
            $this->warn('Já existem leads no banco. Use --fresh para recriar com os dados de demonstração.');

            return self::FAILURE;
        }

        $seeder = $this->laravel->make(DemoSeeder::class);
        $seeder->scale = max(0.05, (float) $this->option('scale'));

        $this->components->task('Simulando 6 meses de operação', fn () => $seeder->run());

        $this->newLine();
        $this->components->twoColumnDetail('Acesse', url('/gestao-crm'));
        $this->components->twoColumnDetail('E-mail', DemoSeeder::ADMIN_EMAIL);
        $this->components->twoColumnDetail('Senha', (string) $seeder->password);

        return self::SUCCESS;
    }
}
