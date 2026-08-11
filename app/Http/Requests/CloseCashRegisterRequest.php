<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'denominations.*.denominacion' => ['required', 'distinct:strict', Rule::in([
                '0.10', '0.20', '0.50', '1', '2', '5', '10', '20', '50', '100', '200',
            ])],
            'denominations.*.cantidad' => ['required', 'integer', 'min:0'],
        ];
    }
}
