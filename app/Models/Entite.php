<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entite extends Model
{
    protected $fillable = ['raison_sociale', 'ice', 'numero', 'email','rib','rib1',
    'rib2',];

    public function facturesAchat()
    {
        return $this->hasMany(FactureAchat::class);
    }

    public function facturesVente()
    {
        return $this->hasMany(FactureVente::class);
    }
}