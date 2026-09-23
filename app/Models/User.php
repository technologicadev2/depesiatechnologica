<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'role_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'profil_image',
        'id_salarie',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function permissions()
    {
        return $this->role ? $this->role->permissions() : collect();
    }

    public function salarie()
    {
        return $this->belongsTo(Salarie::class, 'id_salarie', 'id');
    }


    public function menuPermissions()
    {
        return $this->hasMany(UserMenuPermission::class, 'user_id');
    }
}