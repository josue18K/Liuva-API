<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    protected $fillable = [
        'parent_cash_register_id',
        'user_id',
        'sede_id',
        'tipo',
        'monto_esperado',
        'monto_contado',
        'diferencia',
        'observaciones',
        'fecha_hora',
    ];

    protected function casts(): array
    {
        return [
            'monto_esperado' => 'decimal:2',
            'monto_contado' => 'decimal:2',
            'diferencia' => 'decimal:2',
            'fecha_hora' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function denominations(): HasMany
    {
        return $this->hasMany(CashRegisterDenomination::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'parent_cash_register_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CashRegister::class, 'parent_cash_register_id');
    }
}
