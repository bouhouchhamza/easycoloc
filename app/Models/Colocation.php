<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Colocation extends Model
{
    use HasFactory;

    protected $fillable=[
        'name',
        'owner_id',
        'invite_token',
    ];

    public function owner(){
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(){
        return $this->belongsToMany(\App\Models\User::class)->withPivot('role','left_at')->withTimestamps();
    }
}
