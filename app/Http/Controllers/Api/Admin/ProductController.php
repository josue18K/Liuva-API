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
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));

        $products = Product::query()
            ->with(['category:id,nombre', 'stocks.sede:id,nombre'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nombre', 'like', '%'.$search.'%')
                        ->orWhere('codigo_interno', 'like', '%'.$search.'%')
                        ->orWhere('codigo_barras', 'like', '%'.$search.'%');
                });
            })
            ->when(isset($validated['category_id']), fn ($query) => $query->where('category_id', $validated['category_id']))
            ->when(array_key_exists('active', $validated), fn ($query) => $query->where('active', $validated['active']))
            ->latest()
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'products' => $products,
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::query()->create([
            'nombre' => $request->string('nombre'),
            'descripcion' => $request->filled('descripcion') ? $request->string('descripcion') : null,
            'codigo_interno' => $request->string('codigo_interno'),
            'codigo_barras' => $request->filled('codigo_barras') ? $request->string('codigo_barras') : null,
            'precio_oficial' => $request->input('precio_oficial'),
            'unidad' => $request->string('unidad'),
            'stock_minimo' => $request->integer('stock_minimo'),
            'category_id' => $request->integer('category_id'),
            'active' => $request->boolean('active', true),
        ]);

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Registro de producto',
            'modelo' => Product::class,
            'modelo_id' => $product->id,
            'detalle' => 'Se registró el producto '.$product->nombre,
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
            'descripcion' => $request->filled('descripcion') ? $request->string('descripcion') : null,
            'codigo_interno' => $request->string('codigo_interno'),
            'codigo_barras' => $request->filled('codigo_barras') ? $request->string('codigo_barras') : null,
            'precio_oficial' => $request->input('precio_oficial'),
            'unidad' => $request->string('unidad'),
            'stock_minimo' => $request->integer('stock_minimo'),
            'category_id' => $request->integer('category_id'),
            'active' => $request->boolean('active'),
        ]);

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Actualización de producto',
            'modelo' => Product::class,
            'modelo_id' => $product->id,
            'detalle' => 'Se actualizó el producto '.$product->nombre,
        ]);

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'product' => $product->load('category:id,nombre'),
        ]);
    }
}
