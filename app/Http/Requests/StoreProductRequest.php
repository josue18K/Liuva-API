<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo_interno' => $this->filled('codigo_interno') ? mb_strtoupper(trim((string) $this->input('codigo_interno'))) : '',
            'codigo_barras' => $this->filled('codigo_barras') ? trim((string) $this->input('codigo_barras')) : null,
            'unidad' => mb_strtolower(trim((string) $this->input('unidad', 'unidad'))),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'sede_id' => ['nullable', 'integer', 'exists:sedes,id'],
            'codigo_interno' => ['nullable', 'string', 'max:100', 'unique:products,codigo_interno'],
            'codigo_barras' => ['nullable', 'string', 'regex:/^[0-9]{6,32}$/', 'unique:products,codigo_barras'],
            'precio_oficial' => ['required', 'decimal:0,2', 'min:0'],
            'unidad' => ['required', Rule::in(['unidad', 'par', 'docena', 'caja', 'paquete', 'metro', 'kilogramo'])],
            'stock_minimo' => ['required', 'integer', 'min:0', 'max:1000000'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('active', true)],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
