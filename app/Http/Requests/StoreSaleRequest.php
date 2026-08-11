<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sede_id' => ['required', 'integer', 'exists:sedes,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_vendido' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'sede_id.required' => 'La sede es obligatoria.',
            'items.required' => 'Debes enviar al menos un producto.',
            'items.array' => 'Los items deben enviarse como arreglo.',
            'items.min' => 'Debes enviar al menos un producto.',
            'items.*.product_id.required' => 'El producto es obligatorio.',
            'items.*.cantidad.required' => 'La cantidad es obligatoria.',
            'items.*.cantidad.min' => 'La cantidad mínima es 1.',
            'items.*.precio_vendido.required' => 'El precio vendido es obligatorio.',
            'items.*.precio_vendido.min' => 'El precio vendido no puede ser negativo.',
        ];
    }
}
