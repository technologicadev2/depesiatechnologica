<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorbeilleUser extends Model
{use HasFactory;
    protected $table = 'corbeille_users';

    protected $fillable = [
        'nom',
        'prenom',
        'username',
        'phone',
        'email',
        'password',
        'role_id',
        'deleted_at',
        'deleted_by',
    ];
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
