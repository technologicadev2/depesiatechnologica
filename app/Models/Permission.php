<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'name',
        'code',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
