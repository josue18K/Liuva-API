<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSedeRequest;
use App\Http\Requests\UpdateSedeRequest;
use App\Models\ActivityLog;
use App\Models\Sede;
use Illuminate\Http\JsonResponse;

class SedeController extends Controller
{
    public function index(): JsonResponse
    {
        $sedes = Sede::query()->latest()->get();

        return response()->json([
            'sedes' => $sedes,
        ]);
    }

    public function store(StoreSedeRequest $request): JsonResponse
    {
        $sede = Sede::query()->create([
            'nombre' => $request->string('nombre'),
            'direccion' => $request->string('direccion'),
            'prefix_codigo' => $request->filled('prefix_codigo') ? strtoupper($request->string('prefix_codigo')) : null,
            'active' => $request->boolean('active', true),
        ]);

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Registro de sede',
            'modelo' => Sede::class,
            'modelo_id' => $sede->id,
            'detalle' => 'Se registró la sede '.$sede->nombre,
        ]);

        return response()->json([
            'message' => 'Sede registrada correctamente.',
            'sede' => $sede,
        ], 201);
    }

    public function show(Sede $sede): JsonResponse
    {
        return response()->json([
            'sede' => $sede,
        ]);
    }

    public function update(UpdateSedeRequest $request, Sede $sede): JsonResponse
    {
        $sede->update([
            'nombre' => $request->string('nombre'),
            'direccion' => $request->string('direccion'),
            'prefix_codigo' => $request->filled('prefix_codigo') ? strtoupper($request->string('prefix_codigo')) : null,
            'active' => $request->boolean('active'),
        ]);

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Actualización de sede',
            'modelo' => Sede::class,
            'modelo_id' => $sede->id,
            'detalle' => 'Se actualizó la sede '.$sede->nombre,
        ]);

        return response()->json([
            'message' => 'Sede actualizada correctamente.',
            'sede' => $sede,
        ]);
    }
}
