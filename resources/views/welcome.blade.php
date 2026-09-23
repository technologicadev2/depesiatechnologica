<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Bienvenue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Public Sans', sans-serif;
        }

        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            background: #ffffff;
        }

        .modal-body {
            text-align: center;
            padding: 2rem;
        }

        .welcome-logo {
            max-width: 100px;
            margin-bottom: 1.5rem;
            animation: fadeIn 1s ease-in;
        }

        .welcome-message {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            animation: slideUp 0.8s ease-out;
        }

        .dots {
            font-size: 1.2rem;
            color: #696cff;
            display: inline-block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes dots {
            0% {
                content: '.';
            }

            33% {
                content: '..';
            }

            66% {
                content: '...';
            }

            100% {
                content: '....';
            }
        }

        .dots::after {
            content: '';
            animation: dots 1.5s infinite;
            display: inline-block;
            width: 2rem;
            text-align: left;
        }

        .modal-dialog {
            margin-top: 10vh;
        }

        #companySettingsModal .modal-body,
        #companyDocumentsModal .modal-body {
            text-align: left;
        }

        #companySettingsModal .form-label,
        #companyDocumentsModal .form-label {
            font-weight: 600;
            color: #333;
        }

        #companySettingsModal .form-control,
        #companyDocumentsModal .form-control {
            border-radius: 8px;
        }

        #companySettingsModal .modal-footer,
        #companyDocumentsModal .modal-footer {
            border-top: none;
            padding: 1.5rem;
        }

        .error-message {
            color: #dc3545;
            font-size: 1.2rem;
            text-align: center;
            margin-top: 1rem;
        }

        #vehicle-insurance-fields .col-md-6 {
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
 @php
    $companySettings = App\Models\CompanySettings::first();
    $companyDocuments = App\Models\CompanyDocuments::first();
    $vehicles = App\Models\Vehicle::first();
    \Log::info('CompanyDocuments:', ['exists' => !empty($companyDocuments)]);
    \Log::info('Vehicles:', ['exists' => !empty($vehicles)]);
    $logoUrl = $companySettings && $companySettings->logo
        ? Storage::url($companySettings->logo)
        : asset('assets/img/favicon/anassi2.jpg');
@endphp

<!-- Welcome Modal -->
<div class="modal fade" id="welcomeModal" tabindex="-1" aria-labelledby="welcomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <img src="{{ $logoUrl }}" alt="Anassi Logo" class="welcome-logo">
                <div class="welcome-message">
                    Bienvenu {{ $companySettings ? $companySettings->nom_etreprise : 'à vous' }} dans votre application
                    <span class="dots"></span>
                </div>
            </div>
        </div>
    </div>
</div>
    <!-- Company Settings Modal -->
    <div class="modal fade" id="companySettingsModal" tabindex="-1" aria-labelledby="companySettingsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="companySettingsModalLabel">Paramètres de la Société</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="companySettingsForm" method="POST" action="{{ route('company.settings.update') }}"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Logo -->
                            <div class="col-md-6 mb-3">
                                <label for="logo" class="form-label">Logo</label>
                                <input type="file" class="form-control" name="logo" id="logo" accept="image/*">
                                @if ($companySettings && $companySettings->logo)
                                    <img src="{{ Storage::url($companySettings->logo) }}" alt="Logo" width="100"
                                        class="mt-2">
                                @endif
                                @error('logo')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Cachet -->
                            <div class="col-md-6 mb-3">
                                <label for="stamp" class="form-label">Cachet</label>
                                <input type="file" class="form-control" name="stamp" id="stamp" accept="image/*">
                                @if ($companySettings && $companySettings->stamp)
                                    <img src="{{ Storage::url($companySettings->stamp) }}" alt="Cachet" width="100"
                                        class="mt-2">
                                @endif
                                @error('stamp')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                    <!-- Nom_entreprise -->
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Nom_entreprise</label>
                                <input type="nom_etreprise" class="form-control" name="nom_etreprise" id="nom_etreprise"
                                    value="{{ $companySettings ? $companySettings->nom_etreprise : '' }}">
                                @error('email')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email"
                                    value="{{ $companySettings ? $companySettings->email : '' }}">
                                @error('email')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Adresse -->
                            <div class="col-md-6 mb-3">
                                <label for="address" class="form-label">Adresse</label>
                                <textarea class="form-control" name="address" id="address">{{ $companySettings ? $companySettings->address : '' }}</textarea>
                                @error('address')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Numéro de compte bancaire -->
                            <div class="col-md-6 mb-3">
                                <label for="account_number" class="form-label">Numéro de compte bancaire</label>
                                <input type="text" class="form-control" name="account_number" id="account_number"
                                    value="{{ $companySettings ? $companySettings->account_number : '' }}">
                                @error('account_number')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Nom de la banque -->
                            <div class="col-md-6 mb-3">
                                <label for="bank_name" class="form-label">Nom de la banque</label>
                                <input type="text" class="form-control" name="bank_name" id="bank_name"
                                    value="{{ $companySettings ? $companySettings->bank_name : '' }}">
                                @error('bank_name')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- ICE -->
                            <div class="col-md-6 mb-3">
                                <label for="ice" class="form-label">ICE</label>
                                <input type="text" class="form-control" name="ice" id="ice"
                                    value="{{ $companySettings ? $companySettings->ice : '' }}">
                                @error('ice')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Numéro CNSS -->
                            <div class="col-md-6 mb-3">
                                <label for="cnss_number" class="form-label">Numéro CNSS</label>
                                <input type="text" class="form-control" name="cnss_number" id="cnss_number"
                                    value="{{ $companySettings ? $companySettings->cnss_number : '' }}">
                                @error('cnss_number')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Identifiant fiscal (IF) -->
                            <div class="col-md-6 mb-3">
                                <label for="tax_id" class="form-label">Identifiant fiscal (IF)</label>
                                <input type="text" class="form-control" name="tax_id" id="tax_id"
                                    value="{{ $companySettings ? $companySettings->tax_id : '' }}">
                                @error('tax_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Registre de commerce (RC) -->
                            <div class="col-md-6 mb-3">
                                <label for="commercial_register" class="form-label">Registre de commerce (RC)</label>
                                <input type="text" class="form-control" name="commercial_register"
                                    id="commercial_register"
                                    value="{{ $companySettings ? $companySettings->commercial_register : '' }}">
                                @error('commercial_register')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Patente (TP) -->
                            <div class="col-md-6 mb-3">
                                <label for="patent_number" class="form-label">Patente (TP)</label>
                                <input type="text" class="form-control" name="patent_number" id="patent_number"
                                    value="{{ $companySettings ? $companySettings->patent_number : '' }}">
                                @error('patent_number')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Capital -->
                            <div class="col-md-6 mb-3">
                                <label for="capital" class="form-label">Capital</label>
                                <input type="number" step="0.01" class="form-control" name="capital"
                                    id="capital" value="{{ $companySettings ? $companySettings->capital : '' }}">
                                @error('capital')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Numéro de téléphone -->
                            <div class="col-md-6 mb-3">
                                <label for="phone_number" class="form-label">Numéro de téléphone</label>
                                <input type="text" class="form-control" name="phone_number" id="phone_number"
                                    value="{{ $companySettings ? $companySettings->phone_number : '' }}">
                                @error('phone_number')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-primary" id="submitCompanySettings">
                                Enregistrer <span class="spinner-border spinner-border-sm" role="status"
                                    aria-hidden="true" style="display: none;"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<!-- Company Documents Modal -->
<div class="modal fade" id="companyDocumentsModal" tabindex="-1" aria-labelledby="companyDocumentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="companyDocumentsModalLabel">Documents Société et Véhicule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="companyDocumentsForm" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <!-- Documents de la Société -->
                        @foreach ([
                            'attestation_regularite_fiscale' => 'Attestation de Régularité Fiscale',
                            'attestation_cnss' => 'Attestation CNSS',
                            'attestation_registre_commerce' => 'Attestation Registre Commerce',
                            'attestation_soumission_marche' => 'Attestation Soumission Marché',
                            'assurance_accident_travail' => 'Assurance Accident Travail',
                            'assurance_responsabilite_civile' => 'Assurance Responsabilité Civile',
                            'modele_rc_7' => 'Modèle RC 7',
                            'modele_rc_9' => 'Modèle RC 9',
                        ] as $field => $label)
                            <div class="col-md-6 mb-3">
                                <label for="{{ $field }}" class="form-label">{{ $label }} (PDF)</label>
                                <input type="file" class="form-control" name="{{ $field }}" id="{{ $field }}" accept=".pdf">
                                <label for="{{ $field }}_expires_at" class="form-label mt-2">Date d'expiration</label>
                                <input type="date" class="form-control" name="{{ $field }}_expires_at" id="{{ $field }}_expires_at">
                            </div>
                        @endforeach

                        <!-- Véhicule Section (Un seul véhicule) -->
                        <div class="col-12 mb-3">
                            <h6>Ajouter un Véhicule</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="matricule" class="form-label">Matricule <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="matricule" id="matricule" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="assurance_file" class="form-label">Assurance (PDF)</label>
                                    <input type="file" class="form-control" name="assurance_file" id="assurance_file" accept=".pdf">
                                </div>
                                <div class="col-md-4">
                                    <label for="assurance_expires_at" class="form-label">Date d'expiration Assurance</label>
                                    <input type="date" class="form-control" name="assurance_expires_at" id="assurance_expires_at">
                                </div>
                                <div class="col-md-4">
                                    <label for="visite_technique_file" class="form-label">Visite Technique (PDF)</label>
                                    <input type="file" class="form-control" name="visite_technique_file" id="visite_technique_file" accept=".pdf">
                                </div>
                                <div class="col-md-4">
                                    <label for="visite_technique_expires_at" class="form-label">Date d'expiration Visite</label>
                                    <input type="date" class="form-control" name="visite_technique_expires_at" id="visite_technique_expires_at">
                                </div>
                                <div class="col-md-4">
                                    <label for="carte_grise_file" class="form-label">Carte Grise (PDF)</label>
                                    <input type="file" class="form-control" name="carte_grise_file" id="carte_grise_file" accept=".pdf">
                                </div>
                                <div class="col-md-4">
                                    <label for="carte_grise_expires_at" class="form-label">Date d'expiration Carte</label>
                                    <input type="date" class="form-control" name="carte_grise_expires_at" id="carte_grise_expires_at">
                                </div>
                                <div class="col-md-4">
                                    <label for="contrat_achat_file" class="form-label">Contrat Achat (PDF)</label>
                                    <input type="file" class="form-control" name="contrat_achat_file" id="contrat_achat_file" accept=".pdf">
                                </div>
                                <div class="col-md-4">
                                    <label for="contrat_achat_expires_at" class="form-label">Date d'expiration Contrat</label>
                                    <input type="date" class="form-control" name="contrat_achat_expires_at" id="contrat_achat_expires_at">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary" id="submitCompanyDocuments">
                            Enregistrer <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    var userRole = @json($userRole);
    $(document).ready(function () {
        var redirectUrl = "{{ session('redirect_url') ?? '/dashboard' }}";
        console.log("Redirect URL:", redirectUrl);

      var showCompanySettingsModal = @json(session('show_company_settings_modal', false));
    var showCompanyDocumentsModal = @json(!$companyDocuments || !$vehicles);
        console.log("Show company settings modal:", showCompanySettingsModal);
        console.log("Show company documents modal:", showCompanyDocumentsModal);

        // Précharger les champs existants (pour affichage uniquement, pas d'édition ici)
        function loadVehicleInsuranceFields() {
            $.ajax({
                url: '{{ route('vehicles.get') }}',
                type: 'GET',
                success: function (response) {
                    if (response.success && response.data.vehicles) {
                        console.log("Vehicles loaded:", response.data.vehicles);
                    }
                },
                error: function (xhr) {
                    console.error('Erreur lors du chargement des véhicules:', xhr.responseText);
                }
            });
        }

        // Soumission du formulaire
        $('#companyDocumentsForm').on('submit', function (e) {
            e.preventDefault();

            // Validation côté client
            if (!$('#matricule').val().trim()) {
                $('#matricule').addClass('is-invalid');
                Swal.fire({
                    title: 'Erreur',
                    text: 'Le matricule est requis.',
                    icon: 'error'
                });
                return;
            } else {
                $('#matricule').removeClass('is-invalid');
            }

            $('#submitCompanyDocuments .spinner-border').show();
            $('#submitCompanyDocuments').prop('disabled', true);
            console.log("Form submitted");

            const formData = new FormData(this);
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            // Séparer les données pour les deux tables
            const companyDocsData = new FormData();
            const vehicleData = new FormData();

            // Ajouter les documents de la société
            @foreach ([
                'attestation_regularite_fiscale',
                'attestation_cnss',
                'attestation_soumission_marche',
                'assurance_accident_travail',
                'assurance_responsabilite_civile',
                'modele_rc_7',
                'modele_rc_9'
            ] as $field)
                if (formData.has('{{ $field }}')) {
                    companyDocsData.append('{{ $field }}', formData.get('{{ $field }}'));
                    companyDocsData.append('{{ $field }}_expires_at', formData.get('{{ $field }}_expires_at'));
                }
            @endforeach

            // Ajouter le véhicule
            formData.forEach((value, key) => {
                if (key === 'matricule' || key === 'assurance_file' || key === 'assurance_expires_at' ||
                    key === 'visite_technique_file' || key === 'visite_technique_expires_at' ||
                    key === 'carte_grise_file' || key === 'carte_grise_expires_at' ||
                    key === 'contrat_achat_file' || key === 'contrat_achat_expires_at') {
                    vehicleData.append(key, value);
                }
            });

            // Appels AJAX pour les deux tables
            $.when(
                $.ajax({
                    url: '{{ route('company.documents.store') }}',
                    method: 'POST',
                    data: companyDocsData,
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                }),
                $.ajax({
                    url: '{{ route('vehicles.store') }}',
                    method: 'POST',
                    data: vehicleData,
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                })
            ).done(function (companyResponse, vehicleResponse) {
                $('#submitCompanyDocuments .spinner-border').hide();
                $('#submitCompanyDocuments').prop('disabled', false);
                Swal.fire({
                    title: 'Succès',
                    text: 'Documents et véhicule enregistrés avec succès.',
                    icon: 'success'
                }).then(() => {
                    var companyDocumentsModal = bootstrap.Modal.getInstance(document.getElementById('companyDocumentsModal'));
                    companyDocumentsModal.hide();
                    // Réinitialiser les champs du véhicule
                    $('#matricule').val('');
                    $('#assurance_file').val('');
                    $('#assurance_expires_at').val('');
                    $('#visite_technique_file').val('');
                    $('#visite_technique_expires_at').val('');
                    $('#carte_grise_file').val('');
                    $('#carte_grise_expires_at').val('');
                    $('#contrat_achat_file').val('');
                    $('#contrat_achat_expires_at').val('');
                    loadVehicleInsuranceFields(); // Rafraîchir la liste
                });
            }).fail(function (jqXHR, textStatus, errorThrown) {
                $('#submitCompanyDocuments .spinner-border').hide();
                $('#submitCompanyDocuments').prop('disabled', false);
                console.error('Error:', textStatus, errorThrown, jqXHR.responseText);
                Swal.fire({
                    title: 'Erreur',
                    text: 'Erreur lors de l\'enregistrement. Vérifiez la console pour plus de détails.',
                    icon: 'error'
                });
            });
        });

        if (userRole === 'superadmin' && showCompanySettingsModal) {
            var companySettingsModal = new bootstrap.Modal(document.getElementById('companySettingsModal'), {
                keyboard: false,
                backdrop: 'static'
            });
            companySettingsModal.show();
            console.log("Company settings modal shown");

            $('#companySettingsForm').on('submit', function () {
                $('#submitCompanySettings .spinner-border').show();
                $('#submitCompanySettings').prop('disabled', true);
                console.log("Company settings form submitted");
            });

            $('#companySettingsModal').on('hidden.bs.modal', function () {
                console.log("Company settings modal hidden");
                if (userRole === 'superadmin' && showCompanyDocumentsModal) {
                    var companyDocumentsModal = new bootstrap.Modal(document.getElementById('companyDocumentsModal'), {
                        keyboard: false,
                        backdrop: 'static'
                    });
                    companyDocumentsModal.show();
                    console.log("Company documents modal shown");
                    loadVehicleInsuranceFields();
                } else {
                    var welcomeModal = new bootstrap.Modal(document.getElementById('welcomeModal'), {
                        keyboard: false,
                        backdrop: 'static'
                    });
                    welcomeModal.show();
                    console.log("Welcome modal shown");
                    setTimeout(function () {
                        welcomeModal.hide();
                    }, 3000);
                }
            });
        } else if (userRole === 'superadmin' && showCompanyDocumentsModal) {
            var companyDocumentsModal = new bootstrap.Modal(document.getElementById('companyDocumentsModal'), {
                keyboard: false,
                backdrop: 'static'
            });
            companyDocumentsModal.show();
            console.log("Company documents modal shown");
            loadVehicleInsuranceFields();
        } else {
            var welcomeModal = new bootstrap.Modal(document.getElementById('welcomeModal'), {
                keyboard: false,
                backdrop: 'static'
            });
            welcomeModal.show();
            console.log("Welcome modal shown");
            setTimeout(function () {
                welcomeModal.hide();
            }, 3000);
        }

        $('#companyDocumentsModal').on('hidden.bs.modal', function () {
            console.log("Company documents modal hidden");
            var welcomeModal = new bootstrap.Modal(document.getElementById('welcomeModal'), {
                keyboard: false,
                backdrop: 'static'
            });
            welcomeModal.show();
            console.log("Welcome modal shown");
            setTimeout(function () {
                welcomeModal.hide();
            }, 3000);
        });

        $('#welcomeModal').on('hidden.bs.modal', function () {
            console.log("Welcome modal hidden, redirecting to", redirectUrl);
            window.location.href = redirectUrl;
        });

        $(window).on('error', function (e) {
            console.error("Global error:", e);
            $('body').append('<div class="error-message">Une erreur est survenue. Redirection en cours...</div>');
            setTimeout(function () {
                window.location.href = redirectUrl;
            }, 2000);
        });
    });
</script>
</body>

</html>