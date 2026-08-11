<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\ActivityLog;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with(['category:id,nombre', 'stocks.sede:id,nombre'])
            ->latest()
            ->get();

        return response()->json([
            'products' => $products,
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::query()->create([
            'nombre' => $request->string('nombre'),
            'codigo_interno' => $request->string('codigo_interno'),
            'codigo_barras' => $request->filled('codigo_barras') ? $request->string('codigo_barras') : null,
            'precio_oficial' => $request->input('precio_oficial'),
            'category_id' => $request->integer('category_id'),
            'active' => $request->boolean('active', true),
        ]);

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Registro de producto',
            'modelo' => Product::class,
            'modelo_id' => $product->id,
            'detalle' => 'Se registró el producto ' . $product->nombre,
        ]);

        return response()->json([
            'message' => 'Producto registrado correctamente.',
            'product' => $product->load('category:id,nombre'),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['category:id,nombre', 'stocks.sede:id,nombre']);

        return response()->json([
            'product' => $product,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update([
            'nombre' => $request->string('nombre'),
            'codigo_interno' => $request->string('codigo_interno'),
            'codigo_barras' => $request->filled('codigo_barras') ? $request->string('codigo_barras') : null,
            'precio_oficial' => $request->input('precio_oficial'),
            'category_id' => $request->integer('category_id'),
            'active' => $request->boolean('active'),
        ]);

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Actualización de producto',
            'modelo' => Product::class,
            'modelo_id' => $product->id,
            'detalle' => 'Se actualizó el producto ' . $product->nombre,
        ]);

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'product' => $product->load('category:id,nombre'),
        ]);
    }
}
