<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'codigo_interno' => ['required', 'string', 'max:100', Rule::unique('products', 'codigo_interno')->ignore($product)],
            'codigo_barras' => ['nullable', 'string', 'max:100', Rule::unique('products', 'codigo_barras')->ignore($product)],
            'precio_oficial' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'active' => ['required', 'boolean'],
        ];
    }
}
