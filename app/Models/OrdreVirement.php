<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Entite;
use App\Models\Salarie;

class OrdreVirement extends Model
{
    protected $table = 'ordres_virement';

    protected $fillable = [
        'ref',
        'type_destinataire',
        'destinataire_id',
        'reference',
        'montant',
        'date_virement',
        'motif',
        'file_path',
        'rib_virement'
    ];

    public function getDestinataireAttribute()
    {
        if ($this->type_destinataire === 'societe') {
            return Entite::find($this->destinataire_id);
        } else {
            return Salarie::find($this->destinataire_id);
        }
    }

    public function getNameAttribute()
    {
        $dest = $this->destinataire;
        if ($this->type_destinataire === 'societe') {
            return $dest->raison_sociale ?? 'Inconnu';
        } else {
            return ($dest->nom ?? '') . ' ' . ($dest->prenom ?? '');
        }
    }

    public function getRibDestinataireAttribute()
    {
        return $this->destinataire->rib ?? '';
    }
}