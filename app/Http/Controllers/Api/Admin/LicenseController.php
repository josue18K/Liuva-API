<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLicenseRequest;
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
                $code = 'LIUVA-' . strtoupper(Str::random(10));
            } while (License::query()->where('code', $code)->exists());

            $license = License::query()->create([
                'code' => $code,
                'status' => 'disponible',
            ]);

            $licenses[] = $license;
        }

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Generación de licencias',
            'modelo' => License::class,
            'modelo_id' => null,
            'detalle' => 'Se generaron ' . count($licenses) . ' licencias nuevas.',
        ]);

        return response()->json([
            'message' => 'Licencias generadas correctamente.',
            'licenses' => $licenses,
        ], 201);
    }
}
