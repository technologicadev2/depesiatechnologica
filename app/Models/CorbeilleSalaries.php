<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorbeilleSalaries extends Model
{
    protected $table = 'corbeille_salaries';
    
    protected $fillable = [
        'nom', 'prenom', 'cin', 'phone', 'date_naissance', 'adresse',
        'situation_familiale', 'nombre_enfant', 'n_matricule_cnss',
        'n_matricule_entreprise', 'fonction_id', 'reglement_id', 'deleted_by'
    ];
    
    public function fonction()
    {
        return $this->belongsTo(Fonction::class);
    }
  

    public function reglementSalarie()
    {
        return $this->belongsTo(TypeReglementSalarie::class, 'reglement_salarie_id');
    }
    public function typeReglement()
    {
        return $this->belongsTo(TypeReglement::class, 'reglement_id');
    }
}
