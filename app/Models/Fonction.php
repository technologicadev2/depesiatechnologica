<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Fonction extends Model
{
    protected $fillable = ['designation'];

    public function salaries()
    {
        return $this->hasMany(Salarie::class, 'fonction_id');
    }
}
