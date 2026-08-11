<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    public const STATUS_AVAILABLE = 'disponible';

    public const STATUS_ACTIVATED = 'activada';

    public const STATUS_BLOCKED = 'bloqueada';

    protected $fillable = [
        'code',
        'status',
        'estado',
        'used_by_user_id',
        'used_at',
        'blocked_at',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
            'blocked_at' => 'datetime',
        ];
    }

    public function usedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    public function isAvailable(): bool
    {
        return $this->estado === self::STATUS_AVAILABLE;
    }

    public function isActivated(): bool
    {
        return $this->estado === self::STATUS_ACTIVATED;
    }
}
