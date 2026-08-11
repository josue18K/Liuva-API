<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSedeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sede = $this->route('sede');

        return [
            'nombre' => ['required', 'string', 'max:150', Rule::unique('sedes', 'nombre')->ignore($sede)],
            'direccion' => ['nullable', 'string', 'max:255'],
            'active' => ['required', 'boolean'],
        ];
    }
}
