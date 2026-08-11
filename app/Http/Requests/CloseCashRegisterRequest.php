<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseCashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cash_register_id' => ['required', 'integer', 'exists:cash_registers,id'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'denominations' => ['required', 'array', 'min:1'],
            'denominations.*.denominacion' => ['required', 'numeric', 'min:0.1'],
            'denominations.*.cantidad' => ['required', 'integer', 'min:0'],
        ];
    }
}
