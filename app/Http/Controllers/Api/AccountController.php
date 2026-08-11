<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateAccountRequest;
use App\Http\Requests\RegisterSellerRequest;
use App\Models\ActivityLog;
use App\Models\License;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function register(RegisterSellerRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::query()->create([
                'name' => $request->string('name'),
                'email' => $request->string('email'),
                'password' => $request->string('password'),
                'role' => User::ROLE_SELLER,
                'active' => false,
                'estado' => User::STATUS_PENDING,
            ]);

            ActivityLog::query()->create([
                'user_id' => $user->id,
                'accion' => 'Registro de cuenta',
                'modelo' => User::class,
                'modelo_id' => $user->id,
                'detalle' => 'El vendedor creó una cuenta pendiente de activación.',
            ]);

            return $user;
        });

        return response()->json([
            'message' => 'Cuenta creada correctamente. Inicia sesión para activarla con tu licencia.',
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function activate(ActivateAccountRequest $request): JsonResponse
    {
        $result = DB::transaction(function () use ($request): array {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);

            if ($user->isDisabled()) {
                abort(403, 'La cuenta está deshabilitada por el administrador.');
            }

            if ($user->isActive()) {
                abort(409, 'La cuenta ya está activa.');
            }

            $license = License::query()
                ->where('code', $request->string('license_code'))
                ->lockForUpdate()
                ->first();

            if (! $license || ! $license->isAvailable()) {
                abort(422, 'La licencia no existe, está bloqueada o ya fue activada.');
            }

            $license->update([
                'status' => 'usada',
                'estado' => License::STATUS_ACTIVATED,
                'used_by_user_id' => $user->id,
                'used_at' => now(),
                'blocked_at' => null,
            ]);

            $user->update([
                'active' => true,
                'estado' => User::STATUS_ACTIVE,
            ]);

            ActivityLog::query()->create([
                'user_id' => $user->id,
                'accion' => 'Activación de licencia',
                'modelo' => License::class,
                'modelo_id' => $license->id,
                'detalle' => 'El vendedor activó su cuenta correctamente.',
            ]);

            return [$user, $license];
        });

        [$user, $license] = $result;

        return response()->json([
            'message' => 'Cuenta activada correctamente.',
            'user' => $this->userPayload($user),
            'license' => [
                'id' => $license->id,
                'code' => $license->code,
                'estado' => $license->estado,
                'activated_at' => $license->used_at,
            ],
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'estado' => $user->estado,
            'sede_id' => $user->sede_id,
        ];
    }
}
