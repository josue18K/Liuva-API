<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSellerRequest;
use App\Http\Requests\UpdateSellerRequest;
use App\Models\ActivityLog;
use App\Models\License;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sellers = User::query()
            ->where('role', 'vendedor')
            ->latest()
            ->get(['id', 'name', 'email', 'role', 'active', 'created_at']);

        return response()->json([
            'sellers' => $sellers,
        ]);
    }

    public function store(StoreSellerRequest $request): JsonResponse
    {
        $license = License::query()
            ->where('code', $request->string('license_code'))
            ->where('status', 'disponible')
            ->firstOrFail();

        $seller = User::query()->create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => $request->string('password'),
            'role' => 'vendedor',
            'active' => true,
        ]);

        $license->update([
            'status' => 'usada',
            'used_by_user_id' => $seller->id,
            'used_at' => now(),
        ]);

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Registro de vendedor',
            'modelo' => User::class,
            'modelo_id' => $seller->id,
            'detalle' => 'Se registró al vendedor '.$seller->email,
        ]);

        return response()->json([
            'message' => 'Vendedor registrado correctamente.',
            'seller' => [
                'id' => $seller->id,
                'name' => $seller->name,
                'email' => $seller->email,
                'role' => $seller->role,
                'active' => $seller->active,
            ],
            'license' => [
                'code' => $license->code,
                'status' => $license->status,
                'used_at' => $license->used_at,
            ],
        ], 201);
    }

    public function show(User $seller): JsonResponse
    {
        abort_if($seller->role !== 'vendedor', 404);

        return response()->json([
            'seller' => [
                'id' => $seller->id,
                'name' => $seller->name,
                'email' => $seller->email,
                'role' => $seller->role,
                'active' => $seller->active,
                'created_at' => $seller->created_at,
            ],
        ]);
    }

    public function update(UpdateSellerRequest $request, User $seller): JsonResponse
    {
        abort_if($seller->role !== 'vendedor', 404);

        $seller->name = $request->string('name');
        $seller->email = $request->string('email');
        $seller->active = $request->boolean('active');
        $seller->estado = $request->boolean('active')
            ? User::STATUS_ACTIVE
            : User::STATUS_DISABLED;

        if ($request->filled('password')) {
            $seller->password = $request->string('password');
        }

        $seller->save();

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Actualización de vendedor',
            'modelo' => User::class,
            'modelo_id' => $seller->id,
            'detalle' => 'Se actualizó al vendedor '.$seller->email,
        ]);

        return response()->json([
            'message' => 'Vendedor actualizado correctamente.',
            'seller' => [
                'id' => $seller->id,
                'name' => $seller->name,
                'email' => $seller->email,
                'role' => $seller->role,
                'active' => $seller->active,
                'estado' => $seller->estado,
            ],
        ]);
    }
}
