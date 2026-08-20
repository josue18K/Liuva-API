<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSedeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('prefix_codigo') && $this->filled('nombre')) {
            $name = mb_strtoupper((string) $this->input('nombre'));
            $prefix = str_contains($name, 'PAUZA') ? 'PAU' : (str_contains($name, 'MUJER') ? 'MUJE' : '');

            if ($prefix === '') {
                $clean = preg_replace('/[^A-Z0-9]/', '', str_replace('LIUVA', '', $name));
                $prefix = substr($clean, 0, 4);
            }

            $this->merge(['prefix_codigo' => $prefix]);
        }
    }

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
