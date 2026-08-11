<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_secure_command_creates_active_admin(): void
    {
        $this->artisan('liuva:create-admin', [
            '--email' => 'admin@example.com',
            '--name' => 'Administradora',
        ])
            ->expectsQuestion('Contraseña segura (mínimo 10 caracteres, letras y números)', 'Secure12345!')
            ->expectsQuestion('Confirma la contraseña', 'Secure12345!')
            ->expectsOutput('Administrador listo: admin@example.com')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
            'active' => true,
            'estado' => User::STATUS_ACTIVE,
        ]);
    }
}
