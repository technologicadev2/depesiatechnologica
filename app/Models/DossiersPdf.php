<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DossiersPdf extends Model
{

    protected $table = 'dossiers_pdf';
    protected $fillable = [
        'projet_id',
        'marche_document',
        'assurance_document',
        'assurance_montant',
        'demande_cautionnement',
        'caution_provision_document',
        'caution_provision',
        'caution_definitif_document',
        'caution_definitif',
        'created_by',
        'updated_by',
        'ordre_service_document',
        'private_documents',
    ];
    protected $casts = [
        'private_documents' => 'array', 
            'assurance_montant' => 'decimal:2',
    'caution_provision' => 'decimal:2',
    'caution_definitif' => 'decimal:2',
    ];
    public function projet()
    {
        return $this->belongsTo(Projet::class, 'projet_id');
    }
}
