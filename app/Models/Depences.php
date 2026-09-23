<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Depences extends Model
{
    protected $table = 'depences';
    protected $fillable = [
        'date',
        'created_by',
        'nature_depense',
        'salarie',
        'salarie_id',
        'description',
        'montant',
        'reglement_id',
        'epreuve',
        'code',
        'nature_id',
        'mois_depenses',
        'vehicle_id', 
        'type', 
        'kilometrage',
        'etat_vidange',
        'etat_plaquettes',
        'etat_pneus',
        'etat_pneus',
        'etat_courroie',
        'etat_amortisseur',
        'heures',
         'reference',
    ];


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function typeReglement()
    {
        return $this->belongsTo(TypeReglement::class, 'reglement_id');
    }
    
    public function employee()
    {
        return $this->belongsTo(Salarie::class, 'salarie_id', 'id');
    }

    public function natureDepense()
    {
        return $this->belongsTo(NatureDepense::class, 'nature_id', 'id');
    }
    
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }



    


}