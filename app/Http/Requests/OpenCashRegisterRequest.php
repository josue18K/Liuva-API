<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenCashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sede_id' => ['required', 'integer', 'exists:sedes,id'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'denominations' => ['required', 'array', 'min:1'],
            'denominations.*.denominacion' => ['required', 'numeric', 'min:0.1'],
            'denominations.*.cantidad' => ['required', 'integer', 'min:0'],
        ];
    }
}
