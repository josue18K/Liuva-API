<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['preferences' => $this->preferences($request)]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], [
            'brand_color.regex' => 'Selecciona un color principal válido.',
            'background_color.regex' => 'Selecciona un color de fondo válido.',
        ]);

        $request->user()->update($validated);

        return response()->json([
            'message' => 'Tus colores se guardaron correctamente.',
            'preferences' => $this->preferences($request),
        ]);
    }

    private function preferences(Request $request): array
    {
        return [
            'brand_color' => $request->user()->brand_color ?: '#0b756c',
            'background_color' => $request->user()->background_color ?: '#f5f7f6',
        ];
    }
}
