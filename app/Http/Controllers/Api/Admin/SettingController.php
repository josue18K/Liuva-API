<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::query()
            ->orderBy('clave')
            ->get();

        return response()->json([
            'settings' => $settings,
        ]);
    }

    public function upsert(UpdateSettingRequest $request): JsonResponse
    {
        $saved = [];

        foreach ($request->input('settings') as $item) {
            $setting = Setting::query()->updateOrCreate(
                ['clave' => $item['clave']],
                ['valor' => $item['valor'] ?? null]
            );

            $saved[] = $setting;
        }

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Actualización de configuraciones',
            'modelo' => Setting::class,
            'modelo_id' => null,
            'detalle' => 'Se actualizaron configuraciones generales del sistema.',
        ]);

        return response()->json([
            'message' => 'Configuraciones guardadas correctamente.',
            'settings' => collect($saved)->sortBy('clave')->values(),
        ]);
    }

    public function showByKey(string $key): JsonResponse
    {
        $setting = Setting::query()
            ->where('clave', $key)
            ->first();

        if (! $setting) {
            return response()->json([
                'message' => 'La configuración no existe.',
            ], 404);
        }

        return response()->json([
            'setting' => $setting,
        ]);
    }
}
