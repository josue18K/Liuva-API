<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_SELLER = 'vendedor';

    public const STATUS_PENDING = 'pendiente';

    public const STATUS_ACTIVE = 'activo';

    public const STATUS_DISABLED = 'deshabilitado';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
        'sede_id',
        'estado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function usedLicenses(): HasMany
    {
        return $this->hasMany(License::class, 'used_by_user_id');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function isActive(): bool
    {
        return $this->estado === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->estado === self::STATUS_PENDING;
    }

    public function isDisabled(): bool
    {
        return $this->estado === self::STATUS_DISABLED;
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
