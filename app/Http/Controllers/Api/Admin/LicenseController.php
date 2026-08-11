<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLicenseRequest;
use App\Http\Requests\UpdateLicenseStatusRequest;
use App\Models\ActivityLog;
use App\Models\License;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class LicenseController extends Controller
{
    public function index(): JsonResponse
    {
        $licenses = License::query()
            ->with('usedByUser:id,name,email')
            ->latest()
            ->get();

        return response()->json([
            'licenses' => $licenses,
        ]);
    }

    public function store(StoreLicenseRequest $request): JsonResponse
    {
        $licenses = [];

        for ($i = 0; $i < $request->integer('cantidad'); $i++) {
            do {
                $code = 'LIUVA-'.strtoupper(Str::random(10));
            } while (License::query()->where('code', $code)->exists());

            $license = License::query()->create([
                'code' => $code,
                'status' => 'disponible',
                'estado' => License::STATUS_AVAILABLE,
            ]);

            $licenses[] = $license;
        }

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Generación de licencias',
            'modelo' => License::class,
            'modelo_id' => null,
            'detalle' => 'Se generaron '.count($licenses).' licencias nuevas.',
        ]);

        return response()->json([
            'message' => 'Licencias generadas correctamente.',
            'licenses' => $licenses,
        ], 201);
    }

    public function updateStatus(UpdateLicenseStatusRequest $request, License $license): JsonResponse
    {
        if ($license->isActivated()) {
            return response()->json([
                'message' => 'Una licencia activada no puede cambiar de estado.',
                'code' => 'ACTIVATED_LICENSE_IMMUTABLE',
            ], 409);
        }

        $estado = $request->string('estado')->toString();

        $license->update([
            'estado' => $estado,
            'blocked_at' => $estado === License::STATUS_BLOCKED ? now() : null,
        ]);

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Cambio de estado de licencia',
            'modelo' => License::class,
            'modelo_id' => $license->id,
            'detalle' => 'La licencia cambió al estado '.$estado.'.',
        ]);

        return response()->json([
            'message' => 'Estado de licencia actualizado correctamente.',
            'license' => $license->fresh(),
        ]);
    }
}
