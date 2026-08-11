<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSellerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $seller = $this->route('seller');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($seller),
            ],
            'password' => ['nullable', 'string', 'min:8', 'max:50'],
            'active' => ['required', 'boolean'],
            'sede_id' => ['nullable', 'integer', 'exists:sedes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Debes ingresar un correo válido.',
            'email.unique' => 'Ese correo ya está registrado por otro usuario.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'active.required' => 'El estado es obligatorio.',
            'active.boolean' => 'El estado debe ser verdadero o falso.',
        ];
    }
}
