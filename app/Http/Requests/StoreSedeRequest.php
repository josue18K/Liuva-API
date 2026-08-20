<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSedeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150', 'unique:sedes,nombre'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'prefix_codigo' => ['nullable', 'string', 'max:10', 'unique:sedes,prefix_codigo'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la sede es obligatorio.',
            'nombre.unique' => 'Ya existe una sede con ese nombre.',
        ];
    }
}
