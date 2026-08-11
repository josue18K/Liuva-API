<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSellerRequest;
use App\Http\Requests\UpdateSellerRequest;
use App\Models\ActivityLog;
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
            ->with('sede:id,nombre')
            ->get(['id', 'name', 'email', 'role', 'active', 'estado', 'sede_id', 'created_at']);

        return response()->json([
            'sellers' => $sellers,
        ]);
    }

    public function store(StoreSellerRequest $request): JsonResponse
    {
        $seller = User::query()->create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => $request->string('password'),
            'role' => User::ROLE_SELLER,
            'active' => false,
            'estado' => User::STATUS_PENDING,
            'sede_id' => $request->integer('sede_id') ?: null,
        ]);

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Registro de vendedor',
            'modelo' => User::class,
            'modelo_id' => $seller->id,
            'detalle' => 'El administrador creó una cuenta de vendedor pendiente.',
        ]);

        return response()->json([
            'message' => 'Vendedor creado en estado pendiente.',
            'seller' => [
                'id' => $seller->id,
                'name' => $seller->name,
                'email' => $seller->email,
                'role' => $seller->role,
                'active' => $seller->active,
                'estado' => $seller->estado,
                'sede_id' => $seller->sede_id,
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
                'estado' => $seller->estado,
                'sede_id' => $seller->sede_id,
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
        $seller->sede_id = $request->integer('sede_id') ?: null;
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
                'sede_id' => $seller->sede_id,
            ],
        ]);
    }
}
