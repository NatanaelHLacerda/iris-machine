<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('crm:create-admin {--name= : Nome do admin} {--email= : E-mail de login}')]
#[Description('Cria o usuário admin do Gestão CRM')]
class CreateAdminCommand extends Command
{
    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?: $this->ask('Nome'),
            'email' => $this->option('email') ?: $this->ask('E-mail'),
            'password' => $this->secret('Senha (mín. 8 caracteres)'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create($validator->validated());

        $this->info("Admin {$data['email']} criado. Acesse /gestao-crm.");

        return self::SUCCESS;
    }
}
