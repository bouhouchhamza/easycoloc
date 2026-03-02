<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Colocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_id',
        'invite_token',
        'status',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'colocation_user')
            ->using(Membership::class)
            ->withPivot('role', 'left_at')
            ->withTimestamps();
    }

    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivotNull('left_at');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'colocation_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
