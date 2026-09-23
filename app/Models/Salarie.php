<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salarie extends Model
{
    protected $table = 'salaries';
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'contrat',
        'cin',
        'phone',
        'date_naissance',
        'adresse',
        'situation_familiale',
        'nombre_enfant',
        'n_matricule_cnss',
        'n_matricule_entreprise',
        'fonction_id',
        'reglement_id',
        'created_by',
        'updated_by',
        'photo',
        'salaire_base',
        'type_travail',
        'code_qr',
        'rib',
        'salaire_base',
        'salaire_net',
        'salaire_journalier',
        'anciennete',
        'quitte_post',
        'date_demission',
        'date_sortie',
        'id_conge',
        'type_contrat',
        'id_absence',
        'id_presence',
        'statut',
        'date_embauche',
        'docPreavis',
        'cin_piece_jointe'
        
    ];
    public function demission()
    {
        return $this->hasOne(Demission::class, 'salarie_id');
    }

    public function fonction()
    {
        return $this->belongsTo(Fonction::class, 'fonction_id');
    }

    public function reglement()
    {
        return $this->belongsTo(TypeReglement::class, 'reglement_id');
    }

    public function projets()
    {
        return $this->belongsToMany(Projet::class, 'sal_projet', 'salarie_id', 'projet_id')
            ->using(SalProjet::class)
            ->withPivot('date_integration', 'role', 'date_sortie')
            ->withTimestamps();
    }
    public function preavis()
    {
        return $this->hasOne(Preavis::class, 'salarie_id');
    }
    public function isAssignedToProject()
    {
        return $this->projets()
            ->whereNull('projet_salarie.date_sortie')
            ->exists();
    }
    public function getCurrentProject()
    {
        return $this->projets()
            ->whereNull('projet_salarie.date_sortie')
            ->first();
    }
    public function presences()
    {
        return $this->hasMany(Presence::class, 'salarie_id');
    }
    public function getPhotoUrlAttribute()
    {
        return $this->photo ? asset($this->photo) : asset('assets/img/avatars/1.png');
    }

    public function conges()
    {
        return $this->hasMany(Conge::class, 'salarie_id');
    }

    public function paiements()
    {
        return $this->hasMany(PaiementSalaire::class, 'id_salarie');
    }

    // Accessors pour nettoyer les identifiants
    public function getCinAttribute($value)
    {
        return $this->cleanIdentifier($value);
    }

    public function getNMatriculeCnssAttribute($value)
    {
        return $this->cleanIdentifier($value);
    }

    public function getNMatriculeEntrepriseAttribute($value)
    {
        return $this->cleanIdentifier($value);
    }

    // Méthode privée pour le nettoyage
    private function cleanIdentifier($value)
    {
        if (is_null($value)) {
            return '-';
        }
        return preg_replace('/-\d+$/', '', $value); // Supprime - suivi d'un nombre à la fin
    }
      public function user()
    {
        return $this->hasOne(User::class, 'id_salarie', 'id_salarie');
    }
}