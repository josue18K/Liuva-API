<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminCommand extends Command
{
    protected $signature = 'liuva:create-admin {--email=} {--name=Administrador Liuva}';

    protected $description = 'Crea o actualiza de forma segura la cuenta administradora inicial';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) ($this->option('email') ?: $this->ask('Correo del administrador'))));
        $name = trim((string) $this->option('name'));
        $password = (string) $this->secret('Contraseña segura (mínimo 10 caracteres, letras y números)');
        $confirmation = (string) $this->secret('Confirma la contraseña');

        $validator = Validator::make(compact('email', 'name', 'password', 'confirmation'), [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'same:confirmation', Password::min(10)->letters()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => User::ROLE_ADMIN,
                'active' => true,
                'estado' => User::STATUS_ACTIVE,
            ]
        );
        $admin->tokens()->delete();

        $this->info("Administrador listo: {$admin->email}");

        return self::SUCCESS;
    }
}
