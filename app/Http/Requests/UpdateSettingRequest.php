<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.clave' => ['required', 'string', 'max:120'],
            'settings.*.valor' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.required' => 'Debes enviar al menos una configuración.',
            'settings.array' => 'La configuración debe enviarse como arreglo.',
            'settings.min' => 'Debes enviar al menos una configuración.',
            'settings.*.clave.required' => 'La clave es obligatoria.',
            'settings.*.valor.max' => 'El valor es demasiado largo.',
        ];
    }
}
