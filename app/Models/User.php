<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasRoles;

    public const ROLE_GLOBAL_ADMIN = 'global_admin';
    public const ROLE_ADMIN = self::ROLE_GLOBAL_ADMIN;
    public const ROLE_USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'reputation',
        'is_banned',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_banned' => 'boolean',
        ];
    }

    public function ownedColocations(): HasMany
    {
        return $this->hasMany(Colocation::class, 'owner_id');
    }

    public function colocations(): BelongsToMany
    {
        return $this->belongsToMany(Colocation::class, 'colocation_user')
            ->using(Membership::class)
            ->withPivot('role', 'left_at')
            ->withTimestamps();
    }

    public function activeColocations(): BelongsToMany
    {
        return $this->colocations()
            ->wherePivotNull('left_at')
            ->where('colocations.status', 'active');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'user_id');
    }

    public function outgoingPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'from_user_id');
    }

    public function incomingPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'to_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_GLOBAL_ADMIN);
    }
}
