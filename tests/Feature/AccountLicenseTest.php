<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\License;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountLicenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_registers_as_pending_without_a_license(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Vendedora Liuva',
            'email' => 'VENDEDORA@EXAMPLE.COM',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ])
            ->assertCreated()
            ->assertJsonPath('user.estado', User::STATUS_PENDING)
            ->assertJsonPath('user.email', 'vendedora@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'vendedora@example.com',
            'role' => User::ROLE_SELLER,
            'active' => false,
            'estado' => User::STATUS_PENDING,
        ]);
    }

    public function test_pending_seller_activates_account_with_available_license(): void
    {
        $user = User::factory()->create();
        $license = $this->createLicense('LIUVA-ACTIVATE1');
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/account/activate', [
                'license_code' => 'liuva-activate1',
            ])
            ->assertOk()
            ->assertJsonPath('user.estado', User::STATUS_ACTIVE)
            ->assertJsonPath('license.estado', License::STATUS_ACTIVATED);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'active' => true,
            'estado' => User::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('licenses', [
            'id' => $license->id,
            'status' => 'usada',
            'estado' => License::STATUS_ACTIVATED,
            'used_by_user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'accion' => 'Activación de licencia',
            'modelo_id' => $license->id,
        ]);
    }

    public function test_activated_license_cannot_be_reused(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $license = $this->createLicense('LIUVA-ONLYONCE');

        $this->withToken($firstUser->createToken('first')->plainTextToken)
            ->postJson('/api/account/activate', ['license_code' => $license->code])
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withToken($secondUser->createToken('second')->plainTextToken)
            ->postJson('/api/account/activate', ['license_code' => $license->code])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'La licencia no existe, está bloqueada o ya fue activada.');

        $this->assertDatabaseHas('licenses', [
            'id' => $license->id,
            'used_by_user_id' => $firstUser->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $secondUser->id,
            'estado' => User::STATUS_PENDING,
        ]);
    }

    public function test_blocked_license_cannot_activate_an_account(): void
    {
        $user = User::factory()->create();
        $license = $this->createLicense('LIUVA-BLOCKED1', License::STATUS_BLOCKED);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/account/activate', ['license_code' => $license->code])
            ->assertUnprocessable();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'estado' => User::STATUS_PENDING,
        ]);
    }

    public function test_only_admin_can_block_and_unblock_available_license(): void
    {
        $admin = User::factory()->admin()->create();
        $seller = User::factory()->active()->create();
        $license = $this->createLicense('LIUVA-MANAGE01');

        $this->withToken($seller->createToken('seller')->plainTextToken)
            ->putJson("/api/admin/licenses/{$license->id}/status", [
                'estado' => License::STATUS_BLOCKED,
            ])
            ->assertForbidden();

        $this->app['auth']->forgetGuards();

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->putJson("/api/admin/licenses/{$license->id}/status", [
                'estado' => License::STATUS_BLOCKED,
            ])
            ->assertOk()
            ->assertJsonPath('license.estado', License::STATUS_BLOCKED);

        $this->assertNotNull($license->fresh()->blocked_at);

        $this->app['auth']->forgetGuards();

        $this->withToken($admin->createToken('admin-2')->plainTextToken)
            ->putJson("/api/admin/licenses/{$license->id}/status", [
                'estado' => License::STATUS_AVAILABLE,
            ])
            ->assertOk();

        $this->assertNull($license->fresh()->blocked_at);
    }

    public function test_admin_creates_pending_seller_and_assigns_sede(): void
    {
        $admin = User::factory()->admin()->create();
        $sede = Sede::query()->create([
            'nombre' => 'Sede Norte',
            'active' => true,
        ]);

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->postJson('/api/admin/sellers', [
                'name' => 'Nuevo vendedor',
                'email' => 'nuevo@example.com',
                'password' => 'Secret123!',
                'sede_id' => $sede->id,
            ])
            ->assertCreated()
            ->assertJsonPath('seller.estado', User::STATUS_PENDING)
            ->assertJsonPath('seller.sede_id', $sede->id);
    }

    public function test_admin_can_delete_a_seller_without_operational_history(): void
    {
        $admin = User::factory()->admin()->create();
        $seller = User::factory()->create();

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->deleteJson("/api/admin/sellers/{$seller->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Vendedor eliminado correctamente.');

        $this->assertDatabaseMissing('users', ['id' => $seller->id]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'accion' => 'Eliminación de vendedor',
            'modelo_id' => $seller->id,
        ]);
    }

    public function test_admin_cannot_delete_a_seller_with_operational_history(): void
    {
        $admin = User::factory()->admin()->create();
        $sede = Sede::query()->create(['nombre' => 'Sede Centro', 'active' => true]);
        $seller = User::factory()->active()->create(['sede_id' => $sede->id]);

        CashRegister::query()->create([
            'user_id' => $seller->id,
            'sede_id' => $sede->id,
            'tipo' => 'apertura',
            'monto_contado' => 100,
            'fecha_hora' => now(),
        ]);

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->deleteJson("/api/admin/sellers/{$seller->id}")
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Este vendedor tiene ventas, movimientos de caja o ajustes registrados. Para conservar el historial, deshabilita su cuenta en lugar de eliminarla.',
            );

        $this->assertDatabaseHas('users', ['id' => $seller->id]);
    }

    private function createLicense(string $code, string $estado = License::STATUS_AVAILABLE): License
    {
        return License::query()->create([
            'code' => $code,
            'status' => 'disponible',
            'estado' => $estado,
            'blocked_at' => $estado === License::STATUS_BLOCKED ? now() : null,
        ]);
    }
}
