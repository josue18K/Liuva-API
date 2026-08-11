<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()->latest()->get();

        return response()->json([
            'categories' => $categories,
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::query()->create([
            'nombre' => $request->string('nombre'),
            'active' => $request->boolean('active', true),
        ]);

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Registro de categoría',
            'modelo' => Category::class,
            'modelo_id' => $category->id,
            'detalle' => 'Se registró la categoría ' . $category->nombre,
        ]);

        return response()->json([
            'message' => 'Categoría registrada correctamente.',
            'category' => $category,
        ], 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'category' => $category,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update([
            'nombre' => $request->string('nombre'),
            'active' => $request->boolean('active'),
        ]);

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Actualización de categoría',
            'modelo' => Category::class,
            'modelo_id' => $category->id,
            'detalle' => 'Se actualizó la categoría ' . $category->nombre,
        ]);

        return response()->json([
            'message' => 'Categoría actualizada correctamente.',
            'category' => $category,
        ]);
    }
}
