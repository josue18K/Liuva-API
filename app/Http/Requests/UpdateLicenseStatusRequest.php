<?php

namespace App\Http\Requests;

use App\Models\License;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLicenseStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in([
                License::STATUS_AVAILABLE,
                License::STATUS_BLOCKED,
            ])],
        ];
    }
}
