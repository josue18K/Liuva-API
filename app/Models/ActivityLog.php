<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'accion',
        'modelo',
        'modelo_id',
        'detalle',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'modelo_id' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ActivityLog $log): void {
            if (app()->bound('request')) {
                $log->ip_address ??= request()->ip();
                $log->user_agent ??= request()->userAgent();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
