<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clients';

    protected $fillable = [
        'nom_complet',
        'ice',
        'telephone',
        'email',
        'adresse',
        'ville',
        'type_client',
    ];

    // Relations utiles (selon ton projet)
    public function facturesVente()
    {
        return $this->hasMany(FactureVente::class, 'client_id');
    }

    // Si tu veux chercher par nom ou ICE facilement
    public function scopeSearch($query, $search)
    {
        return $query->where('nom_complet', 'like', "%{$search}%")
                     ->orWhere('ice', 'like', "%{$search}%")
                     ->orWhere('telephone', 'like', "%{$search}%")
                     ->orWhere('email', 'like', "%{$search}%");
    }
}