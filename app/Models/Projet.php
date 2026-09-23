<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SalProjet;
use Dom\Document;

class Projet extends Model
{
    protected $table = 'projet'; // Nom de la table


    protected $fillable = [
        'intitule',
        'num_p',
        'date_offre',
        'date_marche',
        'ville',
        'maitre_ouvrage',
        'budget',
        'rg',
        'delai_execution',
        'date_debut',
        'date_fin',
        'type_projet',
        'description',
        'cloture',
        'created_by',
        'updated_by',
        'total_revision',
        'total_decompte',
        'travaux_executier',
        'caution_definitif',
        'reception_definitive',
        'commande_type',
        'cloture_date',
           'marche_cadre' ,
    ];
    
   protected $casts = [
    'date_offre' => 'date',
    'date_marche' => 'date',
    'date_debut' => 'date',
    'date_fin' => 'date',
    'reception_definitive' => 'date',
    'cloture' => 'boolean',
    'rg' => 'float',
    'total_decompte' => 'float',
    'budget' => 'float',
    'total_revision' => 'float',
    'travaux_executier' => 'float',
    'caution_definitif' => 'float',
    'created_at' => 'datetime',
        'updated_at' => 'datetime',
         'delai_execution' => 'integer',
         'cloture_date' => 'date',

];
    public function responsable()
    {
        return $this->salaries()
            ->wherePivot('role', 'responsable')
            ->whereNull('projet_salarie.date_sortie')
            ->first();
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function ouvriers()
    {
        return $this->salaries()
            ->wherePivot('role', 'ouvrier')
            ->whereNull('projet_salarie.date_sortie')
            ->get();
    }
    public function salaries()
    {
        return $this->belongsToMany(Salarie::class, 'sal_projet', 'projet_id', 'salarie_id')
            ->using(SalProjet::class) // Utilise la classe Pivot
            ->withPivot('date_integration', 'role', 'date_sortie')
            ->withTimestamps();
    }
    public function dossiersPdf()
    {
        return $this->hasMany(DossiersPdf::class, 'projet_id');
    }
    public function ordresService()
    {
        return $this->hasMany(OrdreService::class, 'projet_id');
    }
    public function estEnArret()
    {
        $latestOrdre = $this->ordresService()->orderBy('date_ordre', 'desc')->first();
        return $latestOrdre && $latestOrdre->type === 'arret';
    }
    public function dossier()
    {
        return $this->hasOne(DossiersPdf::class, 'projet_id');
    }


    public function decomptes()
    {
        return $this->hasMany(Decompte::class, 'projet_id');
    }
}
