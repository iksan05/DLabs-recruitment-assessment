<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRoleMapping extends Model
{
    protected $fillable = [
        'user_id',
        'role_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function role()
    {
        return $this->belongsTo(UserRole::class);
    }
}