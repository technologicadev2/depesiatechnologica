<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeReglement extends Model
{
    protected $table = 'type_reglement';

    protected $fillable = ['designation'];

    public function salaries()
    {
        return $this->hasMany(Salarie::class, 'reglement_id');
    }
}