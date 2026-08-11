<?php

namespace App\Http\Requests;

use App\Models\InventoryMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'sede_id' => ['required', 'integer', 'exists:sedes,id'],
            'tipo' => ['required', Rule::in([
                InventoryMovement::TYPE_ENTRY,
                InventoryMovement::TYPE_EXIT,
                InventoryMovement::TYPE_ADJUSTMENT,
            ])],
            'cantidad' => [
                Rule::requiredIf(fn () => in_array($this->input('tipo'), [
                    InventoryMovement::TYPE_ENTRY,
                    InventoryMovement::TYPE_EXIT,
                ], true)),
                'nullable',
                'integer',
                'min:1',
                'max:1000000',
            ],
            'stock_objetivo' => [
                Rule::requiredIf(fn () => $this->input('tipo') === InventoryMovement::TYPE_ADJUSTMENT),
                'nullable',
                'integer',
                'min:0',
                'max:1000000',
            ],
            'motivo' => ['required', 'string', 'max:1000'],
        ];
    }
}
