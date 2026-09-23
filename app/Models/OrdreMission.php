<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdreMission extends Model
{
    use HasFactory;

    protected $table = 'ordremissions';

    protected $fillable = [
        'code', 'salaries', 'gerant', 'emplacement', 'mission',
        'conducteur', 'date_depart', 'heure_depart', 'date_retour',
        'heure_retour', 'transport_public', 'voiture_mission',
        'marque_mission', 'nplaque_mission', 'voiture_personnelle',
        'marque_personnelle', 'puissance_fiscale_p', 'nplaque_p','ordre','frais'
    ];

    protected $casts = [
        'date_depart'         => 'date',
        'date_retour'         => 'date',
        'transport_public'    => 'boolean',
        'voiture_mission'     => 'boolean',
        'voiture_personnelle' => 'boolean',
        'puissance_fiscale_p' => 'integer',
        'salaries'            => 'array',   // très important
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];

    // ==================== RELATIONS ====================

    public function gerantRelation()
    {
        return $this->belongsTo(Salarie::class, 'gerant', 'id');
    }

    // Accesseur pour afficher le nom complet du gérant
    public function getGerantNameAttribute()
    {
        if ($this->gerantRelation) {
            return $this->gerantRelation->nom . ' ' . $this->gerantRelation->prenom;
        }
        return $this->gerant ? 'ID: ' . $this->gerant : '-';
    }

    // Accesseur pour afficher les participants
    public function getSalariesNamesAttribute()
    {
        if (empty($this->salaries)) {
            return '-';
        }

        $ids = is_array($this->salaries) ? $this->salaries : json_decode($this->salaries, true);

        $salaries = Salarie::whereIn('id', $ids)->get();

        return $salaries->map(function ($s) {
            return ($s->matricule ? $s->matricule . ' - ' : '') . $s->nom . ' ' . $s->prenom;
        })->implode(', ');
    }
}