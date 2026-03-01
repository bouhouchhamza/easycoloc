<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Membership extends Pivot
{
    protected $table = 'colocation_user';

    protected $fillable = [
        'colocation_id',
        'user_id',
        'role',
        'left_at',
    ];

    protected $casts = [
        'left_at' => 'datetime',
    ];

    public $timestamps = true;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function colocation(): BelongsTo
    {
        return $this->belongsTo(Colocation::class);
    }

    public function isActive(): bool
    {
        return $this->left_at === null;
    }
}
