@extends('master_page.app')

@section('title')
    Gestion des Factures
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        #vatReportModal .modal-body {
            padding: 20px;
        }
        #vatReportModal .modal-body h6 {
            font-weight: bold;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        #vatReportModal .modal-body p {
            margin-bottom: 10px;
            font-size: 14px;
        }
        #vatReportModal .modal-body .row.mt-3 {
            border-top: 1px solid #dddddd;
            padding-top: 15px;
        }
    </style>
    <h4 class="fw-bold py-3 mb-4">Gestion des Factures</h4>
    <link href="{{ asset('assets/css/factures.css') }}" rel="stylesheet">

   <div class="row mb-3">
    <div class="col-md-4">
        <label for="start_date">Date de début</label>
        <input type="date" class="form-control" id="start_date">
    </div>
    <div class="col-md-4">
        <label for="end_date">Date de fin</label>
        <input type="date" class="form-control" id="end_date">
    </div>
    <div class="col-md-4">
        <label for="vat_filter_type">Périmètre des factures</label>
        <select class="form-select" id="vat_filter_type">
            <option value="releve_existe">
                Relevé existant (payé + rapproché)
            </option>
            <option value="releve_non_existe">
                Relevé non existant (non payé / non rapproché)
            </option>
            <option value="toutes">
                Toutes les factures
            </option>
        </select>
    </div>
</div>
<button type="button" class="btn btn-success" id="generateVatReportBtn">
    Générer Rapport TVA
</button>

    <!-- VAT Report Modal -->
    <div class="modal fade" id="vatReportModal" tabindex="-1" aria-labelledby="vatReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title" id="vatReportModalLabel">Rapport TVA - <span id="vatPeriod"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Factures d'Achat</h6>
                            <p>Total HT: <span id="vatAchatHt"></span> DH</p>
                            <p>Total TVA: <span id="vatAchatTva"></span> DH</p>
                            <p>Total TTC: <span id="vatAchatTtc"></span> DH</p>
                            
                            <p>Nombre: <span id="vatAchatCount"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Factures de Vente</h6>
                            <p>Total HT: <span id="vatVenteHt"></span> DH</p>
                            <p>Total TVA: <span id="vatVenteTva"></span> DH</p>
                            <p>Total TTC: <span id="vatVenteTtc"></span> DH</p>
                            <p>Nombre: <span id="vatVenteCount"></span></p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>TVA Nette</h6>
                            <p><span id="netTva"></span> DH</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="printVatReportBtn" class="btn btn-primary">Imprimer Rapport TVA</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4 mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <ul class="nav nav-pills " id="factureTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="achat-tab" data-bs-toggle="tab" href="#achat" role="tab" aria-controls="achat" aria-selected="true">Factures d'Achat</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="vente-tab" data-bs-toggle="tab" href="#vente" role="tab" aria-controls="vente" aria-selected="false">Factures de Vente</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="releve-tab" data-bs-toggle="tab" href="#releve" role="tab" aria-controls="releve" aria-selected="false">Relevé</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="factureTabsContent">
                    <!-- Factures d'Achat -->
                    <div class="tab-pane fade show active" id="achat" role="tabpanel" aria-labelledby="achat-tab">
                        <div class="row ms-2 me-3 dt-filter-container">
                            <div class="col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls">
                                <div class="dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3">
                                    <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importExcelModal" data-type="achat">
                                        <i class="bx bx-upload me-sm-1"></i>
                                        <span class="d-none d-sm-inline-block">Importer Excel</span>
                                    </button>
                                    <button class="btn btn-outline-info" id="downloadTemplateAchat">
                                        <i class="bx bx-download me-sm-1"></i>
                                        <span class="d-none d-sm-inline-block">Modèle Excel</span>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter">
                                <!-- No date filters -->
                            </div>
                        </div>
                        <div class="card-datatable table-responsive">
                            <table class="datatables-basic table table-striped table-hover border-top" id="facturesAchatTable">
                                <thead>
                                    <tr>
                                        <th>Numéro Facture</th>
                                        <th>Date Facture</th>
                                        <th>Raison Sociale</th>
                                        <th>ICE</th>
                                        <th>Montant HT</th>
                                        <th>Taux TVA</th>
                                        <th>Montant TVA</th>
                                        <th>Montant TTC</th>
                                        <th><strong>Jours écoulés</strong></th>
                                         <th>Statut</th> 
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($facturesAchat as $facture)
                                        <tr style="{{ $facture->releve_ex ? 'background-color: #cdf584;' : '' }}" >

                                            <td>{{ $facture->numero_facture }}</td>
                                            <td>{{ $facture->date_facture }}</td>
                                            <td>{{ $facture->raison_sociale }}</td>
                                            <td>{{ $facture->ice }}</td>
                                            <td>{{ number_format($facture->montant_ht, 2, '.', '') }}</td>
                                            <td>{{ number_format($facture->taux_tva, 2) }}%</td>
                                            <td>{{ number_format($facture->montant_tva, 2, '.', '') }}</td>
                                            <td>{{ number_format($facture->montant_ttc, 2, '.', '') }}</td>
                                              <td>
                                            @php
                                                $jours = $facture->nm_jours;
                                                $enRetard = !$facture->payee && $jours >= 90;   // Non payée et > 90 jours
                                            @endphp
                                            <span class="badge" style="
                                                {{ $enRetard ? 'background-color: #dc3545; color: white;' :
                                                ($jours >= 60 ? 'background-color: #ffc107; color: #000;' : 'background-color: #84e8f5; color: #000;') }}">
                                                {{ $jours }} jours
                                            </span>
                                        </td>
                                             {{-- ← ajouter colonne statut --}}
                                            <td>
                                                @if ($facture->payee)
                                                    <span class="badge bg-success">
                                                        <i class="bx bx-check-circle"></i> Payée
                                                    </span>
                                                    <br>
                                                    <small class="text-muted">{{ $facture->date_paiement }}</small>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="bx bx-x-circle"></i> Non payée
                                                    </span>
                                                @endif
                                            </td>

                                            <td>
                                                <div class="action-buttons d-flex align-items-center">
                                                    <button class="btn btn-sm btn-icon edit-facture" data-id="{{ $facture->id }}" data-type="achat" title="Modifier">
                                                        <i class="bx bx-edit text-primary"></i>
                                                    </button>
                                                                                        {{-- ← ajouter bouton paiement --}}
                                                    <button class="btn btn-sm btn-icon paiement-facture"
                                                            data-id="{{ $facture->id }}"
                                                            data-payee="{{ $facture->payee }}"
                                                            data-date="{{ $facture->date_paiement }}"
                                                            title="{{ $facture->payee ? 'Modifier paiement' : 'Marquer comme payée' }}">
                                                        <i class="bx bx-credit-card {{ $facture->payee ? 'text-success' : 'text-warning' }}"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon download-facture"
                                                            data-id="{{ $facture->id }}"
                                                            data-type="achat"
                                                            title="{{ $facture->file_path ? 'Télécharger' : 'Aucun fichier disponible' }}"
                                                            {{ $facture->file_path ? '' : 'disabled' }}>
                                                        <i class="bx bx-download {{ $facture->file_path ? 'text-success' : 'text-muted' }}"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon print-facture" data-id="{{ $facture->id }}" data-type="achat" title="Imprimer">
                                                        <i class="bx bx-printer text-dark"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon delete-facture" data-id="{{ $facture->id }}" data-type="achat" title="Supprimer">
                                                        <i class="bx bx-trash text-danger"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- Totaux pour Factures d'Achat -->
                        <div class="totals-container">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Total HT: </strong>
                                    <span id="totalHTAchat">{{ number_format($facturesAchat->sum('montant_ht'), 2, '.', '') }} DH</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Total TVA: </strong>
                                    <span id="totalTVAAchat">{{ number_format($facturesAchat->sum('montant_tva'), 2, '.', '') }} DH</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Total TTC: </strong>
                                    <span id="totalTTCAchat">{{ number_format($facturesAchat->sum('montant_ttc'), 2, '.', '') }} DH</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Nombre de factures: </strong>
                                    <span id="countAchat">{{ $facturesAchat->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Factures de Vente -->
                    <div class="tab-pane fade" id="vente" role="tabpanel" aria-labelledby="vente-tab">
                        <div class="row ms-2 me-3 dt-filter-container">
                            <div class="col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls">
                                <div class="dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3">
                                    <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importExcelModal" data-type="vente">
                                        <i class="bx bx-upload me-sm-1"></i>
                                        <span class="d-none d-sm-inline-block">Importer Excel</span>
                                    </button>
                                    <button class="btn btn-outline-info" id="downloadTemplateVente">
                                        <i class="bx bx-download me-sm-1"></i>
                                        <span class="d-none d-sm-inline-block">Modèle Excel</span>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter">
                                <!-- No date filters -->
                            </div>
                        </div>
                        <div class="card-datatable table-responsive">
                            <table class="datatables-basic table table-striped table-hover border-top" id="facturesVenteTable">
                                <thead>
                                    <tr>
                                        <th>Numéro Facture</th>
                                        <th>Objet</th>
                                        <th>Date Facture</th>
                                        <th>Raison Sociale</th>
                                        <th>ICE</th>
                                        <th>Montant HT</th>
                                        <th>Taux TVA</th>
                                        <th>Montant TVA</th>
                                        <th>Montant TTC</th>
                                        <th><strong>Jours écoulés</strong></th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($facturesVente as $facture)
                                        <tr style="{{ $facture->relve_ex ? 'background-color: #cdf584;' : '' }}">
                                            <td>{{ $facture->numero_facture }}</td>
                                            <td>{{ $facture->objet ?? '-' }}</td>
                                            <td>{{ $facture->date_facture }}</td>
                                            <td>{{ $facture->raison_sociale }}</td>
                                            <td>{{ $facture->ice }}</td>
                                            <td>{{ number_format($facture->montant_ht, 2, '.', '') }}</td>
                                            <td>{{ number_format($facture->taux_tva, 2) }}%</td>
                                            <td>{{ number_format($facture->montant_tva, 2, '.', '') }}</td>
                                            <td>{{ number_format($facture->montant_ttc, 2, '.', '') }}</td>
                                            
                                               <td>
                            @php
                                $jours = $facture->nm_jours;
                                $enRetard = $facture->encaisser == 0 && $jours >= 90;
                            @endphp
                            <span class="badge" style="
                                {{ $enRetard ? 'background-color: #dc3545;' : 
                                ($jours >= 60 ? 'background-color: #ffc107; color: #000;' : 'background-color: #84e8f5; color: #000;') }}">
                                {{ $jours }} jours
                            </span>
                        </td>
                                            <!-- Statut Encaissement -->
                       <td>
    @if ($facture->encaisser)
        <span class="badge bg-success">
            <i class="bx bx-check-circle"></i> Encaissée
        </span>
        <br>
        <small class="text-muted">{{ $facture->date_encaissement ?? '—' }}</small>
    @else
        <span class="badge bg-danger">
            <i class="bx bx-x-circle"></i> Non encaissée
        </span>
    @endif
    {{-- Date cachée pour le filtre JS --}}
    @if ($facture->date_encaissement)
        <span style="display:none;">{{ $facture->date_encaissement }}</span>
    @endif
</td>
                                            <td>
                                                <div class="action-buttons d-flex align-items-center">
                                                    <button class="btn btn-sm btn-icon edit-facture" data-id="{{ $facture->id }}" data-type="vente" title="Modifier">
                                                        <i class="bx bx-edit text-primary"></i>
                                                    </button>
                                                    <!-- Bouton Encaissement (NOUVEAU) -->
                                <button class="btn btn-sm btn-icon encaissement-facture"
                                        data-id="{{ $facture->id }}"
                                        data-encaisser="{{ $facture->encaisser ? 1 : 0 }}"
                                        data-date="{{ $facture->date_encaissement }}"
                                        title="{{ $facture->encaisser ? 'Modifier encaissement' : 'Marquer comme encaissée' }}">
                                    <i class="bx bx-credit-card {{ $facture->encaisser ? 'text-success' : 'text-warning' }}"></i>
                                </button>
                                                    <button class="btn btn-sm btn-icon download-facture"
                                                            data-id="{{ $facture->id }}"
                                                            data-type="vente"
                                                            title="{{ $facture->file_path ? 'Télécharger' : 'Aucun fichier disponible' }}"
                                                            {{ $facture->file_path ? '' : 'disabled' }}>
                                                        <i class="bx bx-download {{ $facture->file_path ? 'text-success' : 'text-muted' }}"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon print-facture" data-id="{{ $facture->id }}" data-type="vente" title="Imprimer">
                                                        <i class="bx bx-printer text-dark"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon delete-facture" data-id="{{ $facture->id }}" data-type="vente" title="Supprimer">
                                                        <i class="bx bx-trash text-danger"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- Totaux pour Factures de Vente -->
                        <div class="totals-container">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Total HT: </strong>
                                    <span id="totalHTVente">{{ number_format($facturesVente->sum('montant_ht'), 2, '.', '') }} DH</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Total TVA: </strong>
                                    <span id="totalTVAVente">{{ number_format($facturesVente->sum('montant_tva'), 2, '.', '') }} DH</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Total TTC: </strong>
                                    <span id="totalTTCVente">{{ number_format($facturesVente->sum('montant_ttc'), 2, '.', '') }} DH</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Nombre de factures: </strong>
                                    <span id="countVente">{{ $facturesVente->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
<!-- Onglet Relevé -->
<div class="tab-pane fade" id="releve" role="tabpanel" aria-labelledby="releve-tab">

    

    <div class="card-datatable table-responsive">
        <table class="datatables-basic table table-striped table-hover border-top" id="relevesTable">
            <thead>
                <tr>
                   
                    <th>Mois</th>
                    <th>Année</th>
                    <th>Fichier</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($releves as $releve)
                    <tr>
                       
                        <td>{{ $releve->mois }}</td>
                        <td>{{ $releve->annee }}</td>
                        <td>
                            @if ($releve->file_path)
                                <span class="badge bg-success"><i class="bx bx-file"></i> Fichier joint</span>
                            @else
                                <span class="badge bg-secondary">Aucun fichier</span>
                            @endif
                        </td>
                        <td>
                            <div class="action-buttons d-flex align-items-center">
                                <button class="btn btn-sm btn-icon download-releve"
                                        data-id="{{ $releve->id }}"
                                        title="{{ $releve->file_path ? 'Télécharger' : 'Aucun fichier' }}"
                                        {{ $releve->file_path ? '' : 'disabled' }}>
                                    <i class="bx bx-download {{ $releve->file_path ? 'text-success' : 'text-muted' }}"></i>
                                </button>
                                <button class="btn btn-sm btn-icon delete-releve"
                                        data-id="{{ $releve->id }}"
                                        title="Supprimer">
                                    <i class="bx bx-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Facture Modal -->
    <div class="modal fade" id="factureModal" tabindex="-1" aria-labelledby="factureModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-light border-bottom">
                        <h5 class="modal-title" id="factureModalLabel">Ajouter une Facture</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="factureForm" enctype="multipart/form-data">
                        <input type="hidden" id="factureId" name="facture_id">
                        <input type="hidden" id="isEdit" name="is_edit" value="false">
                        <input type="hidden" id="type" name="type">
                        <div class="modal-body p-4">

                        <div class="card mb-3" id="scanCard">
        <div class="card-body">
            <h6 class="card-title mb-3">
                <i class="bx bx-scan me-1 text-primary"></i>
                Scan automatique par IA <span class="badge bg-primary ms-1" style="font-size:10px;">GPT-4o</span>
            </h6>
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label for="scan_file" class="form-label">
                        Importer la facture pour extraction automatique  Image
                    </label>
                    <input type="file" class="form-control" id="scan_file"
                        accept=".jpg,.jpeg,.png,.pdf">
                    <small class="text-muted">Formats :  JPG, PNG. Max : 10 Mo.</small>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-primary w-100" id="scanFactureBtn">
                        <i class="bx bx-analyse me-1"></i>
                        <span id="scanBtnText">Scanner la facture</span>
                    </button>
                </div>
            </div>

        {{-- Prévisualisation du fichier sélectionné --}}
        <div id="scanPreview" class="mt-3" style="display:none;">
            <div class="border rounded p-2 text-center bg-light">
                <img id="scanPreviewImg" src="" alt="Aperçu"
                     style="max-width:100%;max-height:180px;display:none;" class="rounded">
                <p id="scanPreviewPdf" class="text-muted mb-0" style="display:none;">
                    <i class="bx bxs-file-pdf text-danger" style="font-size:2rem;"></i><br>
                    <span id="scanPreviewPdfName" class="small"></span>
                </p>
            </div>
        </div>

        {{-- Alerte résultat --}}
        <div id="scanAlert" class="alert mt-3 mb-0" style="display:none;" role="alert"></div>
            </div>
        </div>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title mb-3">Informations de la Facture</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="numero_facture" class="form-label">Numéro Facture <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="numero_facture" name="numero_facture" required>
                                        <div class="invalid-feedback">Veuillez entrer un numéro de facture valide.</div>
                                    </div>
                                    <div class="col-md-6" id="objet_field" style="display: none;">
                                        <label for="objet" class="form-label">Objet</label>
                                        <input type="text" class="form-control" id="objet" name="objet" maxlength="255">
                                        <div class="invalid-feedback">L'objet ne peut pas dépasser 255 caractères.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="date_facture" class="form-label">Date Facture <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="date_facture" name="date_facture"
                                               value="{{ old('date_facture', now()->format('Y-m-d')) }}" required>
                                        <small class="form-text text-muted">
                                            Période de déclaration: {{ $companySettings->tva_declaration ?? 'mensuelle' }}
                                        </small>
                                        <div class="invalid-feedback">Veuillez sélectionner une date.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title mb-3">Informations de l'Entité/Client</h6>
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label for="raison_sociale" class="form-label">Raison Sociale / Nom <span class="text-danger">*</span></label>
                                        <select class="form-select" id="raison_sociale" name="raison_sociale" required>
                                            <option value="">Sélectionner...</option>
                                            <!-- Entités (pour factures achat) -->
                                            @foreach($entites as $entite)
                                                <option value="{{ $entite->raison_sociale }}" 
                                                        data-ice="{{ $entite->ice }}" 
                                                        data-type="entite"
                                                        class="option-entite">
                                                    {{ $entite->raison_sociale }}
                                                </option>
                                            @endforeach
                                            <!-- Clients (pour factures vente) -->
                                            @foreach($clients as $client)
                                                <option value="{{ $client->nom_complet }}" 
                                                        data-ice="{{ $client->ice }}" 
                                                        data-type="client"
                                                        class="option-client">
                                                    {{ $client->nom_complet }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">Veuillez sélectionner une raison sociale.</div>
                                    </div>
                                    <div class="col-md-5">
                                        <label for="ice" class="form-label">ICE <span class="text-danger">*</span></label>
                                        <select class="form-select" id="ice" name="ice" required>
                                            <option value="">Sélectionner...</option>
                                            @foreach($entites as $entite)
                                                <option value="{{ $entite->ice }}" 
                                                        data-type="entite"
                                                        class="option-entite">
                                                    {{ $entite->ice }}
                                                </option>
                                            @endforeach
                                            @foreach($clients as $client)
                                                <option value="{{ $client->ice }}" 
                                                        data-type="client"
                                                        class="option-client">
                                                    {{ $client->ice }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">Veuillez sélectionner un ICE valide.</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label d-block">&nbsp;</label>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" id="addEntityBtn" title="Nouvelle Entité">
                                                <i class="bx bx-building"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary btn-sm flex-fill" id="addClientBtn" title="Nouveau Client">
                                                <i class="bx bx-user-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title mb-3">Détails Financiers</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="taux_tva" class="form-label">Taux TVA (%) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="taux_tva" name="taux_tva" step="0.01" min="0" value="20" required>
                                        <div class="invalid-feedback">Veuillez entrer un taux TVA valide.</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label for="montant_tva" class="form-label mb-0">Montant TVA</label>
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="toggleAutoCalc" title="Activer/Désactiver le calcul automatique">
                                                <i class="bx bx-calculator"></i> Auto
                                            </button>
                                        </div>
                                        <input type="number" class="form-control" id="montant_tva" name="montant_tva" step="0.01" min="0" readonly>
                                        <div class="invalid-feedback">Veuillez entrer un montant TVA valide.</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="montant_htt" class="form-label">Montant HT <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="montant_htt" name="montant_htt" step="0.01" min="0" required>
                                        <div class="invalid-feedback">Veuillez entrer un montant HT valide.</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="montant_ttc" class="form-label">Montant TTC <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="montant_ttc" name="montant_ttc" step="0.01" min="0" required>
                                        <div class="invalid-feedback">Veuillez entrer un montant TTC valide.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title mb-3">Fichier Joint</h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="file" class="form-label">Joindre un fichier (PDF/Image)</label>
                                        <input type="file" class="form-control" id="file" name="file" accept=".pdf,.jpg,.jpeg,.png">
                                        <div class="invalid-feedback">Veuillez sélectionner un fichier valide (PDF, JPG, PNG).</div>
                                        <small class="form-text text-muted">Formats acceptés : PDF, JPG, PNG. Taille max : 5 Mo.</small>
                                        <div id="filePreview" class="mt-2"></div>
                                        <input type="hidden" id="existingFilePath" name="existing_file_path">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <small class="text-muted" id="calcModeInfo">
                                <i class="bx bx-calculator text-success"></i>
                                <span id="calcModeText">Mode calcul automatique activé</span>
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



<!-- Modal Paiement / Encaissement (utilisée pour achat ET vente) -->
<div class="modal fade" id="paiementModal" tabindex="-1" aria-labelledby="paiementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title" id="paiementModalLabel">Statut de Paiement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paiementForm">
                <input type="hidden" id="paiementFactureId">
                <input type="hidden" id="paiementFactureType"> <!-- achat ou vente -->
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="payee_check" name="payee" value="1">
                            <label class="form-check-label fw-bold" for="payee_check" id="payeeLabel">
                                Facture payée / encaissée
                            </label>
                        </div>
                    </div>
                    <div class="mb-3" id="date_paiement_field" style="display:none;">
                        <label for="date_paiement_input" class="form-label">Date de paiement / encaissement</label>
                        <input type="date" class="form-control" id="date_paiement_input" name="date_paiement">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submitPaiementBtn">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

   <!-- Modal Ajouter Relevé -->
<div class="modal fade" id="releveModal" tabindex="-1" aria-labelledby="releveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title" id="releveModalLabel">Ajouter un Relevé</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="releveForm" enctype="multipart/form-data">
                <input type="hidden" id="releve_debit_json"  name="debit">
                <input type="hidden" id="releve_credit_json" name="credit">
                <div class="modal-body p-4"><c

                   {{-- ─── SCAN IA ─── --}}
    <div class="card mb-3 border-primary">
        <div class="card-body">
            <h6 class="card-title mb-3">
                <i class="bx bx-scan me-1 text-primary"></i>
                Scan automatique par IA
                <span class="badge bg-primary ms-1" style="font-size:10px;">GPT-4o</span>
            </h6>

            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label for="releve_scan_file" class="form-label">
                        Importer les images du relevé bancaire (JPG/PNG)
                    </label>
                <input type="file" class="form-control" id="releve_scan_file"
        accept=".jpg,.jpeg,.png,.pdf" multiple>
                    <small class="text-muted">
                        Sélectionnez une ou plusieurs images (max 10). Max 10 Mo par image.
                    </small>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-primary w-100" id="scanReleveBtn">
                        <i class="bx bx-analyse me-1"></i>
                        <span id="scanReleveBtnText">Scanner le relevé</span>
                    </button>
                </div>
            </div>

        {{-- Grille de prévisualisations --}}
        <div id="releveScamPreviewGrid" class="mt-3 d-flex flex-wrap gap-2" style="display:none!important;"></div>

        {{-- Barre de progression --}}
        <div id="releveScanProgressWrapper" class="mt-3" style="display:none;">
            <div class="d-flex justify-content-between mb-1">
                <small class="text-muted" id="releveScanProgressLabel">Analyse en cours...</small>
                <small class="text-muted" id="releveScanProgressCount">0 / 0</small>
            </div>
            <div class="progress" style="height:8px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                     id="releveScanProgressBar" role="progressbar" style="width:0%"></div>
            </div>
        </div>

        {{-- Alerte --}}
        <div id="releveScamAlert" class="alert mt-3 mb-0" style="display:none;" role="alert"></div>
    </div>
</div>

                  

                    <div class="mb-3">
                        <label for="releve_mois" class="form-label">Mois <span class="text-danger">*</span></label>
                        <select class="form-select" id="releve_mois" name="mois" required>
                            <option value="">-- Sélectionner le mois --</option>
                            <option value="Janvier">Janvier</option>
                            <option value="Février">Février</option>
                            <option value="Mars">Mars</option>
                            <option value="Avril">Avril</option>
                            <option value="Mai">Mai</option>
                            <option value="Juin">Juin</option>
                            <option value="Juillet">Juillet</option>
                            <option value="Août">Août</option>
                            <option value="Septembre">Septembre</option>
                            <option value="Octobre">Octobre</option>
                            <option value="Novembre">Novembre</option>
                            <option value="Décembre">Décembre</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="releve_annee" class="form-label">Année <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="releve_annee" name="annee"
                               min="2000" max="2100" value="{{ date('Y') }}" required>
                    </div>

                  {{-- ─── RÉSULTAT SCAN : TABLEAU DÉBIT ─── --}}
<div id="releveDebitSection" class="mb-3">  {{-- Retirer style="display:none;" --}}
    <h6 class="fw-bold text-danger">
        <i class="bx bx-minus-circle me-1"></i>Opérations Débit
        <span class="badge bg-danger ms-1" id="releveDebitCount">0</span>
        — Total : <span id="releveDebitTotal" class="text-danger">0.00</span> DH
    </h6>
    <div class="table-responsive" style="max-height:220px;overflow-y:auto;">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-danger">
                <tr>
                    <th>Date</th>
                    <th>Libellé</th>
                    <th>Montant (DH)</th>
                    <th>Référence</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="releveDebitBody"></tbody>
        </table>
    </div>
    {{-- ← BOUTON AJOUT MANUEL --}}
    <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="addDebitRowBtn">
        <i class="bx bx-plus me-1"></i>Ajouter une ligne débit
    </button>
</div>

{{-- ─── RÉSULTAT SCAN : TABLEAU CRÉDIT ─── --}}
<div id="releveCreditSection" class="mb-3">  {{-- Retirer style="display:none;" --}}
    <h6 class="fw-bold text-success">
        <i class="bx bx-plus-circle me-1"></i>Opérations Crédit
        <span class="badge bg-success ms-1" id="releveCreditCount">0</span>
        — Total : <span id="releveCreditTotal" class="text-success">0.00</span> DH
    </h6>
    <div class="table-responsive" style="max-height:220px;overflow-y:auto;">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-success">
                <tr>
                    <th>Date</th>
                    <th>Libellé</th>
                    <th>Montant (DH)</th>
                    <th>Référence</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="releveCreditBody"></tbody>
        </table>
    </div>
    {{-- ← BOUTON AJOUT MANUEL --}}
    <button type="button" class="btn btn-sm btn-outline-success mt-2" id="addCreditRowBtn">
        <i class="bx bx-plus me-1"></i>Ajouter une ligne crédit
    </button>
</div>
                    {{-- ─── PIÈCE JOINTE ─── --}}
                    <div class="mb-3">
                        <label for="releve_file" class="form-label">Pièce jointe (PDF/Image)</label>
                        <input type="file" class="form-control" id="releve_file" name="file"
                               accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Formats : PDF, JPG, PNG. Max : 5 Mo.</small>
                        <div id="releveFilePreview" class="mt-2"></div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submitReleveBtn">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Entity Modal -->
    <div class="modal fade" id="entityModal" tabindex="-1" aria-labelledby="entityModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="entityModalLabel">Ajouter une nouvelle entité</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="entityForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="entity_raison_sociale" class="form-label">Raison Sociale <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="entity_raison_sociale" name="new_raison_sociale" required>
                                <div class="invalid-feedback">Veuillez entrer une raison sociale.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="entity_ice" class="form-label">ICE <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="entity_ice" name="new_ice" required>
                                <div class="invalid-feedback">Veuillez entrer un ICE valide.</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="entity_numero" class="form-label">Numéro</label>
                                <input type="text" class="form-control" id="entity_numero" name="new_numero">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="entity_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="entity_email" name="new_email">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="submitEntityBtn">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Client Modal -->
    <div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clientModalLabel">Ajouter un nouveau client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="clientForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="client_nom_complet" class="form-label">Nom Complet <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="client_nom_complet" name="new_nom_complet" required>
                                <div class="invalid-feedback">Veuillez entrer un nom complet.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="client_ice" class="form-label">ICE <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="client_ice" name="new_ice" required>
                                <div class="invalid-feedback">Veuillez entrer un ICE valide.</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="client_telephone" class="form-label">Téléphone</label>
                                <input type="text" class="form-control" id="client_telephone" name="new_telephone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="client_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="client_email" name="new_email">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="client_ville" class="form-label">Ville</label>
                                <input type="text" class="form-control" id="client_ville" name="new_ville">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="client_adresse" class="form-label">Adresse</label>
                                <textarea class="form-control" id="client_adresse" name="new_adresse" rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="submitClientBtn">Enregistrer</button>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>

    <!-- Import Excel Modal -->
    <div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title" id="importExcelModalLabel">Importer un fichier Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="importExcelForm" enctype="multipart/form-data">
                    <input type="hidden" id="importType" name="type">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="excelFile" class="form-label">Sélectionner un fichier Excel <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="excelFile" name="excel_file" accept=".xlsx,.xls" required>
                            <div class="invalid-feedback">Veuillez sélectionner un fichier Excel valide (.xlsx ou .xls).</div>
                            <small class="form-text text-muted">Le fichier doit contenir les colonnes : Numéro Facture, Date Facture, Raison Sociale, ICE, Taux TVA (%), Montant TVA, Montant HT, Montant TTC, (Objet pour les ventes).</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="submitExcelBtn">Importer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const companySettings = @json($companySettings ?? []);
        console.log('Company Settings:', companySettings);
    </script>
    <script src="{{ asset('assets/js/factures.js') }}"></script>
    <script>
// ===============================================
// FILTRAGE DES OPTIONS SELON LE TYPE DE FACTURE
// ===============================================
function filterOptionsBasedOnType(factureType, skipReset = false) {
    const raisonSelect = $('#raison_sociale');
    const iceSelect    = $('#ice');

    raisonSelect.find('option').hide();
    iceSelect.find('option').hide();

    raisonSelect.find('option[value=""]').show();
    iceSelect.find('option[value=""]').show();

    if (factureType === 'achat') {
        raisonSelect.find('.option-entite').show();
        iceSelect.find('.option-entite').show();
        $('#addEntityBtn').show();
        $('#addClientBtn').hide();
    } else if (factureType === 'vente') {
        raisonSelect.find('.option-client').show();
        iceSelect.find('.option-client').show();
        $('#addEntityBtn').hide();
        $('#addClientBtn').show();
    }

    // Ne réinitialiser que si ce n'est pas une édition
    if (!skipReset) {
        raisonSelect.val('').trigger('change');
        iceSelect.val('').trigger('change');
    }
}
// Appliquer le filtre lors de l'ouverture du modal
$('#factureModal').on('show.bs.modal', function (e) {
    let type = $('#type').val();
    const isEdit = $('#isEdit').val() === 'true';

    if (!type) {
        const activeTab = $('#factureTabs .nav-link.active').attr('href');
        type = (activeTab === '#achat') ? 'achat' : 'vente';
        $('#type').val(type);
    }

    filterOptionsBasedOnType(type, isEdit); // skipReset si c'est une édition
    
    const title = isEdit ? 'Modifier la Facture' : 
        (type === 'achat' ? "Ajouter une Facture d'Achat" : 'Ajouter une Facture de Vente');
    $('#factureModalLabel').text(title);
});
// B. Quand on change d'onglet → mettre à jour le type caché
$('#factureTabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    const target = $(e.target).attr('href');
    const type = target === '#achat' ? 'achat' : 'vente';
    $('#type').val(type);

    // Si le modal est déjà ouvert → on met à jour le filtre immédiatement
    if ($('#factureModal').hasClass('show')) {
        filterOptionsBasedOnType(type);
    }
});
// ===============================================
// SYNCHRONISATION RAISON SOCIALE ET ICE
// ===============================================

// Synchroniser raison sociale vers ICE
$("#raison_sociale").on("change", function() {
    const selectedOption = $(this).find('option:selected');
    const ice = selectedOption.data("ice");
    $("#ice").val(ice);
});

// Synchroniser ICE vers raison sociale
$("#ice").on("change", function() {
    const selectedIce = $(this).val();
    const matchingOption = $("#raison_sociale").find(`option[data-ice="${selectedIce}"]`);
    if (matchingOption.length) {
        $("#raison_sociale").val(matchingOption.val());
    }
});

// ===============================================
// GESTION DU MODAL ENTITÉ
// ===============================================

// Ouvrir le modal entité
$(document).on('click', '#addEntityBtn', function() {
    $('#entityModal').modal('show');
});

// Soumission du formulaire entité
$('#entityForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = $('#submitEntityBtn');
    
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement...');
    
    $.ajax({
        url: '/entites/store',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.message || response.raison_sociale) {
                // Ajouter la nouvelle entité aux selects
                const entityOptionRaison = `<option value="${response.raison_sociale}" data-ice="${response.ice}" data-type="entite" class="option-entite">${response.raison_sociale}</option>`;
                const entityOptionIce = `<option value="${response.ice}" data-type="entite" class="option-entite">${response.ice}</option>`;
                
                $('#raison_sociale').append(entityOptionRaison);
                $('#ice').append(entityOptionIce);
                
                // Sélectionner automatiquement la nouvelle entité
                $('#raison_sociale').val(response.raison_sociale);
                $('#ice').val(response.ice);
                
                // Fermer le modal et réinitialiser
                $('#entityModal').modal('hide');
                $('#entityForm')[0].reset();
                submitBtn.prop('disabled', false).html('Enregistrer');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: response.message || 'Entité ajoutée avec succès',
                    timer: 3000,
                    timerProgressBar: true
                });
            }
        },
        error: function(xhr) {
            submitBtn.prop('disabled', false).html('Enregistrer');
            
            let errorMessage = 'Une erreur s\'est produite';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                html: errorMessage,
                timer: 5000,
                timerProgressBar: true
            });
        }
    });
});

// ===============================================
// GESTION DU MODAL CLIENT
// ===============================================

// Ouvrir le modal client
$(document).on('click', '#addClientBtn', function() {
    $('#clientModal').modal('show');
});

// Soumission du formulaire client
$('#clientForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = $('#submitClientBtn');
    
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement...');
    
    $.ajax({
        url: '/clients/store-from-facture',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                // Ajouter le nouveau client aux selects
                const clientOptionRaison = `<option value="${response.nom_complet}" data-ice="${response.ice}" data-type="client" class="option-client">${response.nom_complet}</option>`;
                const clientOptionIce = `<option value="${response.ice}" data-type="client" class="option-client">${response.ice}</option>`;
                
                $('#raison_sociale').append(clientOptionRaison);
                $('#ice').append(clientOptionIce);
                
                // Sélectionner automatiquement le nouveau client
                $('#raison_sociale').val(response.nom_complet);
                $('#ice').val(response.ice);
                
                // Fermer le modal et réinitialiser
                $('#clientModal').modal('hide');
                $('#clientForm')[0].reset();
                submitBtn.prop('disabled', false).html('Enregistrer');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: response.message,
                    timer: 3000,
                    timerProgressBar: true
                });
            }
        },
        error: function(xhr) {
            submitBtn.prop('disabled', false).html('Enregistrer');
            
            let errorMessage = 'Une erreur s\'est produite';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                html: errorMessage,
                timer: 5000,
                timerProgressBar: true
            });
        }
    });
});
    </script>
@endsection