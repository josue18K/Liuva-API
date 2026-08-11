<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_log_captures_request_context(): void
    {
        $user = User::factory()->active()->create(['password' => 'Secret123!']);

        $this->withHeader('User-Agent', 'Liuva-Android-Test')
            ->postJson('/api/login', ['email' => $user->email, 'password' => 'Secret123!'])
            ->assertOk();

        $log = ActivityLog::query()->where('accion', 'Inicio de sesión')->firstOrFail();
        $this->assertSame('127.0.0.1', $log->ip_address);
        $this->assertSame('Liuva-Android-Test', $log->user_agent);
    }

    public function test_admin_filters_paginated_activity_log(): void
    {
        $admin = User::factory()->admin()->create();
        ActivityLog::query()->create([
            'user_id' => $admin->id,
            'accion' => 'Registro de producto',
            'modelo' => Product::class,
            'modelo_id' => 10,
            'detalle' => 'Producto de prueba',
        ]);

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->getJson('/api/admin/activity-logs?accion=producto&modelo='.urlencode(Product::class).'&per_page=5')
            ->assertOk()
            ->assertJsonCount(1, 'activity_logs.data')
            ->assertJsonPath('activity_logs.data.0.modelo_id', 10);
    }
}
