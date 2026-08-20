<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(): JsonResponse
    {
        $admins = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->latest()
            ->get(['id', 'name', 'email', 'role', 'active', 'estado', 'created_at']);

        return response()->json([
            'admins' => $admins,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => User::ROLE_ADMIN,
            'active' => true,
            'estado' => User::STATUS_ACTIVE,
        ]);

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Registro de administrador',
            'modelo' => User::class,
            'modelo_id' => $admin->id,
            'detalle' => 'El administrador creó una cuenta de administrador.',
        ]);

        return response()->json([
            'message' => 'Administrador creado correctamente.',
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
                'active' => $admin->active,
                'estado' => $admin->estado,
            ],
        ], 201);
    }

    public function update(Request $request, User $admin): JsonResponse
    {
        abort_if($admin->role !== User::ROLE_ADMIN, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin)],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $admin->name = $validated['name'];
        $admin->email = $validated['email'];

        if (!empty($validated['password'])) {
            $admin->password = $validated['password'];
        }

        $admin->save();

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Actualización de administrador',
            'modelo' => User::class,
            'modelo_id' => $admin->id,
            'detalle' => 'Se actualizó la cuenta de administrador.',
        ]);

        return response()->json([
            'message' => 'Administrador actualizado correctamente.',
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
            ],
        ]);
    }

    public function destroy(Request $request, User $admin): JsonResponse
    {
        abort_if($admin->role !== User::ROLE_ADMIN, 404);
        abort_if($admin->id === $request->user()->id, 422, 'No puedes eliminarte a ti mismo.');

        $admin->tokens()->delete();
        $admin->delete();

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Eliminación de administrador',
            'modelo' => User::class,
            'modelo_id' => $admin->id,
            'detalle' => 'Se eliminó la cuenta de administrador.',
        ]);

        return response()->json([
            'message' => 'Administrador eliminado correctamente.',
        ]);
    }
}
