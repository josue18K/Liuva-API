<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegisterDenomination extends Model
{
    protected $fillable = [
        'cash_register_id',
        'denominacion',
        'cantidad',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'denominacion' => 'decimal:2',
            'cantidad' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }
}
