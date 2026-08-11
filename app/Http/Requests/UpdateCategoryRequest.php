<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'nombre' => ['required', 'string', 'max:150', Rule::unique('categories', 'nombre')->ignore($category)],
            'active' => ['required', 'boolean'],
        ];
    }
}
