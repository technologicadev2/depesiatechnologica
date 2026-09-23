<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = ['menu_name', 'label'];

    public function userPermissions()
    {
        return $this->hasMany(UserMenuPermission::class, 'menu_name', 'menu_name');
    }
}