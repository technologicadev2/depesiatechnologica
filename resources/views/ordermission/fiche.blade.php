<div id="fiche-printable" class="p-4 border" style="background:#fff; font-family: Arial, sans-serif;">

    
    {{-- En-tête avec logo et titre --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            @if(isset($companySettings) && $companySettings->logo)
                <img src="{{ asset('storage/' . $companySettings->logo) }}" 
                     alt="Logo" style="max-height: 70px; max-width: 150px; object-fit: contain;">
            @else
                <img src="{{ asset('assets/img/favicon/anassi2.jpg') }}" 
                     alt="Logo" style="max-height: 70px; max-width: 150px; object-fit: contain;">
            @endif
        </div>
        <div style="text-align: center; flex: 1;">
            <h4 style="margin: 0; font-weight: bold;">ORDRE DE MISSION</h4>
        </div>
        <div style="width: 150px;"></div>
    </div>

    <table class="table table-bordered">
        <!-- Code Mission + Objet -->
        <tr>
            <td><strong>Code Mission :</strong> {{ $ordre->code }}</td>
            <td colspan="2"><strong>Objet de la mission :</strong> {{ $ordre->mission ?? '-' }}</td>
        </tr>
        
        <!-- Départ -->
        <tr>
            <td><strong>Date de départ :</strong> {{ $ordre->date_depart ? $ordre->date_depart->format('d/m/Y') : '-' }}</td>
            <td><strong>Heure de départ :</strong> {{ $ordre->heure_depart ?? '-' }}</td>
            <td></td>
        </tr>
        
        <!-- Retour -->
        <tr>
            <td><strong>Date de retour :</strong> {{ $ordre->date_retour ? $ordre->date_retour->format('d/m/Y') : '-' }}</td>
            <td><strong>Heure de retour :</strong> {{ $ordre->heure_retour ?? '-' }}</td>
            <td></td>
        </tr>

        <!-- Emplacement + Gérant sur la même ligne -->
        <tr>
            <td><strong>Emplacement :</strong> {{ $ordre->emplacement ?? '-' }}</td>
            <td colspan="2"><strong>Gérant :</strong> 
                {{ $ordre->gerantRelation ? $ordre->gerantRelation->nom . ' ' . $ordre->gerantRelation->prenom : '-' }}
            </td>
        </tr>

        <!-- Salariés -->
        <tr>
            <td colspan="3"><strong>Salariés participants :</strong> {{ $ordre->salaries_names }}</td>
        </tr>

        @if($ordre->frais)
        <tr>
            <td colspan="3"><strong>Frais estimés :</strong> {{ number_format($ordre->frais, 2) }} DH</td>
        </tr>
        @endif
    </table>

    <!-- Moyen de Transport -->
    <h5>Moyen de Transport :</h5>
    <table class="table table-bordered">
        @if($ordre->transport_public == 1)
            <tr><td colspan="3">Transport Public</td></tr>
        @elseif($ordre->voiture_mission == 1)
            <tr>
                <td>Voiture de Mission</td>
                <td>Marque : {{ $ordre->marque_mission ?? '-' }}</td>
                <td>N° Plaque : {{ $ordre->nplaque_mission ?? '-' }}</td>
            </tr>
        @elseif($ordre->voiture_personnelle == 1)
            <tr>
                <td>Voiture Personnelle</td>
                <td>Marque : {{ $ordre->marque_personnelle ?? '-' }}</td>
                <td>N° Plaque : {{ $ordre->nplaque_p ?? '-' }}</td>
                <td>Puissance : {{ $ordre->puissance_fiscale_p ?? '-' }} CV</td>
            </tr>
        @endif
    </table>


<!-- Signatures -->
<div style="display: flex; justify-content: space-between; margin-top: 60px;">
    <!-- <div style="text-align: center; width: 200px;">
        <p style="margin-bottom: 60px;"><strong>Signature du Gérant</strong></p>
        <div style="border-top: 1px solid #000; padding-top: 5px;">
            &nbsp;
        </div>
    </div> -->
    <div style="text-align: center; width: 200px; position: relative;">
        <p style="margin-bottom: 60px;"><strong>Signature du Superviseur</strong></p>

        @if(isset($companySettings) && $companySettings->stamp)
            <img src="{{ asset('storage/' . $companySettings->stamp) }}"
                 alt="Cachet"
                 style="position: absolute; top: 10px; left: 50%; transform: translateX(-50%) rotate(-8deg); max-width: 110px; max-height: 110px; opacity: 0.85; z-index: 1;">
        @endif

        <div style="border-top: 1px solid #000; padding-top: 5px; position: relative; z-index: 2;">
            &nbsp;
        </div>
    </div>
</div>

</div>