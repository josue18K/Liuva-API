<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'license_code' => mb_strtoupper(trim((string) $this->input('license_code'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'license_code' => ['required', 'string', 'max:100'],
        ];
    }
}
