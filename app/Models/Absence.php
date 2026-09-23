<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absence extends Model
{
    use HasFactory;

    // Spécifier explicitement le nom de la table
    protected $table = 'absence';

    protected $fillable = [
        'salarie_id',
        'date_debut',
        'date_fin',
        'nbre_jours',
        'description',
        'piece_jointe',
        'justification',
    ];
 

    public function salarie()
    {
        return $this->belongsTo(Salarie::class);
    }
}