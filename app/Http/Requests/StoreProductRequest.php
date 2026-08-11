<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'codigo_interno' => ['required', 'string', 'max:100', 'unique:products,codigo_interno'],
            'codigo_barras' => ['nullable', 'string', 'max:100', 'unique:products,codigo_barras'],
            'precio_oficial' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
