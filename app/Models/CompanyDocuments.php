<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDocuments extends Model
{
    use HasFactory;

    protected $table = 'company_documents';

   protected $fillable = [
        'attestation_regularite_fiscale',
        'attestation_regularite_fiscale_expires_at',
        'attestation_cnss',
        'attestation_cnss_expires_at',
        'attestation_registre_commerce',
        'attestation_registre_commerce_expires_at',
        'attestation_soumission_marche',
        'attestation_soumission_marche_expires_at',
        'assurance_accident_travail',
        'assurance_accident_travail_expires_at',
        'assurance_responsabilite_civile',
        'assurance_responsabilite_civile_expires_at',
        'modele_rc_7',
        'modele_rc_7_expires_at',
        'modele_rc_9',
        'modele_rc_9_expires_at',
        'assurance_vehicule',
        'created_by',
        'updated_by',
        'signature_electronic',
        'date_exp_signature',
    ];

    protected $casts = [
        'assurance_vehicule' => 'array',
        'attestation_regularite_fiscale_expires_at' => 'date',
        'attestation_cnss_expires_at' => 'date',
        'attestation_registre_commerce_expires_at' => 'date',
        'attestation_soumission_marche_expires_at' => 'date',
        'assurance_accident_travail_expires_at' => 'date',
        'assurance_responsabilite_civile_expires_at' => 'date',
        'modele_rc_7_expires_at' => 'date',
        'modele_rc_9_expires_at' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}