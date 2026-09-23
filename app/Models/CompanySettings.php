<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySettings extends Model
{
    protected $fillable = [
        'logo',
        'stamp',
        'email',
        'address',
        'account_number',//cb
        'bank_name',
        'ice',
        'cnss_number',
        'tax_id',//if 
        'commercial_register',
        'patent_number',//tp
        'capital',
        'phone_number',
        'nom_etreprise',
        'tva_declaration',
        'activite_societe',
        'gerant',
        'ville',
        'tva',
      'assuranceCompagnie',
       'adresseAssurance',

    ];
}