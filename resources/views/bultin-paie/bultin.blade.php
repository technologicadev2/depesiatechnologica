@extends('master_page.app')

@section('title')
    Bulletin de paie
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="check-base-salary-url" content="{{ route('bultin.checkBaseSalary') }}">
    <meta name="increment-salary-url" content="{{ route('bultin.incrementSalary') }}">
    <meta name="add-salary-payment-url" content="{{ route('bultin.addSalaryPayment') }}">
    <meta name="download-quittance-url" content="{{ route('bultin.downloadQuittance') }}">
    <meta name="bulk-salary-payment-url" content="{{ route('salary.bulk-payment') }}">
    <meta name="upload-quittance-url" content="{{ route('upload-quittance-url') }}">
    <meta name="update-anciennete-url" content="{{ route('bultin.update-anciennete') }}">
    <meta name="update-all-anciennetes-url" content="{{ route('bultin.update-all-anciennetes') }}">
    <meta name="upload-bulk-payroll-url" content="{{ route('upload-bulk-payroll') }}">
    <meta name="delete-salary-payment-url" content="{{ route('bultin.deleteSalaryPayment') }}">
    <meta name="generate-annual-payslip-url" content="{{ route('bultin.generateAnnualPaySlip') }}">
    <meta name="generate-notepad-url" content="{{ route('generate.notepad') }}">
    <meta name="generate-virements-pdf-url" content="{{ route('bultin.generateVirementsPdf') }}">
    <meta name="download-pay-slip-cachet-url" content="{{ route('bultin.downloadPaySlipCachet') }}">
    <meta name="download-quittance-cah-url" content="{{ route('bultin.downloadQuittanceCah') }}">

    <h4 class="fw-bold py-3 mb-4">Bulletin de paie</h4>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://printjs-4de6.kxcdn.com/print.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link href="{{ asset('assets/css/bultin.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script> 
<link href="{{ asset('assets/css/bultin.css') }}" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">


    @if (session('error'))
      <script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('#new-action-select').addEventListener('change', function(e) {
        const url = e.target.value;
        if (url) {
            const link = document.createElement('a');
            link.href = url;
            link.download = '';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            e.target.value = '';
        }
    });
});
</script>
    @endif

    <div class="row mb-4">
    <div class="col-12">
        <div class="card sticky-card" id="stickyCard">
            <div class="card-body">
                <div class="row ms-2 me-3 dt-filter-container">
                    <div class="col-12 d-flex align-items-center justify-content-center justify-content-md-start gap-1 dt-controls">
                        <form method="GET" action="{{ route('bultin.index') }}" class="d-flex gap-1 w-100" enctype="multipart/form-data">
                            @csrf
                            <div class="select-container gap-1">
                                <select name="year" id="yearSelect" class="form-select" onchange="this.form.submit()">
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="select-container d-flex gap-1 align-items-center">
                                <select name="month" id="monthSelect" class="form-select" onchange="this.form.submit()">
                                    <option value="" {{ $currentMonth == '' ? 'selected' : '' }}>Sélectionner un mois</option>
                                    <option value="janvier" {{ $currentMonth == 'janvier' ? 'selected' : '' }}>Janvier</option>
                                    <option value="fevrier" {{ $currentMonth == 'fevrier' ? 'selected' : '' }}>Février</option>
                                    <option value="mars" {{ $currentMonth == 'mars' ? 'selected' : '' }}>Mars</option>
                                    <option value="avril" {{ $currentMonth == 'avril' ? 'selected' : '' }}>Avril</option>
                                    <option value="mai" {{ $currentMonth == 'mai' ? 'selected' : '' }}>Mai</option>
                                    <option value="juin" {{ $currentMonth == 'juin' ? 'selected' : '' }}>Juin</option>
                                    <option value="juillet" {{ $currentMonth == 'juillet' ? 'selected' : '' }}>Juillet</option>
                                    <option value="aout" {{ $currentMonth == 'aout' ? 'selected' : '' }}>Août</option>
                                    <option value="septembre" {{ $currentMonth == 'septembre' ? 'selected' : '' }}>Septembre</option>
                                    <option value="octobre" {{ $currentMonth == 'octobre' ? 'selected' : '' }}>Octobre</option>
                                    <option value="novembre" {{ $currentMonth == 'novembre' ? 'selected' : '' }}>Novembre</option>
                                    <option value="decembre" {{ $currentMonth == 'decembre' ? 'selected' : '' }}>Décembre</option>
                                </select>
                                <button type="button" id="total-salary-btn" class="btn btn-primary text-nowrap" title="Calculer le salaire total">
                                    <i class="bi bi-calculator"></i> Salaire Total
                                </button>
                            </div>
                            <div class="select-container">
                                <select name="payment_method" id="paymentMethodSelect" class="form-select">
                                    <option value="" {{ request('payment_method') == '' ? 'selected' : '' }}>Mode de paiement</option>
                                    <option value="espece" {{ request('payment_method') == 'espece' ? 'selected' : '' }}>Espèces</option>
                                    <option value="cheque" {{ request('payment_method') == 'cheque' ? 'selected' : '' }}>Chèque</option>
                                    <option value="virement" {{ request('payment_method') == 'virement' ? 'selected' : '' }}>Virement</option>
                                    <option value="virement-g" {{ request('payment_method') == 'virement-g' ? 'selected' : '' }}>Virement-G</option>
                                </select>
                            </div>
                            <div class="select-container gap-1 ">
                                <button type="button" id="bulk-payment-btn" class="btn btn-primary w-100 text-nowrap" title="Exécuter Paiement Groupé">
                                    <i class="bi bi-cash-stack"></i> Paiement Groupé
                                </button>
                            </div>
                            <div class="select-container d-flex gap-1 align-items-center">
                                <div class="dropdown custom-dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start d-flex ju stify-content-between align-items-center" 
                                            type="button" 
                                            id="bulk-payroll-dropdown" 
                                            data-bs-toggle="dropdown">
                                        <span id="bulk-payroll-selected"> livre de paie</span>
                                    </button>
                                    <ul class="dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;">
                                        @if (!empty($bulkPayrolls))
                                            @foreach ($bulkPayrolls as $payroll)
                                                @php
                                                    Log::info('Valeur de payroll dans Blade', ['payroll' => $payroll]);
                                                    preg_match("/bulk_payroll_(.+)_\d{4}\.pdf/", $payroll, $matches);
                                                    $month = $matches[1];
                                                    $displayText = Str::title($month) . ' ' . $currentYear;
                                                @endphp
                                                <li>
                                                    <div class="dropdown-item d-flex justify-content-between align-items-center" 
                                                         data-value="{{ asset('assets/storage/salaries/bulk_payrolls/' . $payroll) }}"
                                                         data-filename="{{ strtolower($payroll) }}">
                                                        <span>{{ $displayText }}</span>
                                                        <div class="action-icons">
                                                            <i class="bi bi-file-earmark-pdf action-icon download-icon" title="Télécharger le PDF du mois"></i>
                                                            <i class="bi bi-upload action-icon browse-icon" title="Parcourir/Remplacer PDF"></i>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforeach
                                        @else
                                            <li><span class="dropdown-item-text text-muted">Aucun livre de paie trouvé</span></li>
                                        @endif
                                    </ul>
                                </div>
                            </div>

                                <div class="select-container mr-5 d-flex gap-1 align-items-center">
                               

                                <!-- Bouton Générer (optionnel mais très utile) -->
                                <button type="button" id="generate-virements-btn" class="btn btn-success text-nowrap" title="Générer la liste des virements pour le mois sélectionné">
                                    <i class="bi bi-file-earmark-arrow-down"></i> Générer les RIB
                                </button>
                            </div>

                           


                            <div class="select-container">
                                <select name="status" id="statusSelect" class="form-select" onchange="this.form.submit()">
                                    <option value="actif" {{ request('status') == 'actif' ? 'selected' : '' }}>Salarié Actif</option>
                                    <option value="inactif" {{ request('status') == 'inactif' ? 'selected' : '' }}>Salarié Démissionné</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-datatable table-responsive mt-5">
                   <table class="datatables-basic table table-striped table-hover border-top highlight-{{ $currentMonth }}" id="payrollTable">
 <thead>
    <tr>
        <th><input type="checkbox" id="selectAll"></th> <!-- Checkbox column -->
        <th>Matricule</th>
        <th>Nom et Prénom</th>
        @foreach ($months as $monthName)
            <th>{{ $monthName }}</th> <!-- One column per month -->
        @endforeach
        <th>Bulletin Annuel</th>
    </tr>
</thead>
    <tbody>
        @foreach ($salaries as $salarie)
            <tr>
                <td><input type="checkbox" class="employee-checkbox" data-id-salarie="{{ $salarie['id_salarie'] }}"></td>
                <td>{{ $salarie['matricule'] }}</td>
                <td class="small-text">{{ $salarie['nom_prenom'] }}</td>
                @foreach ($months as $monthKey => $monthName)
                    @php
                        $monthData = $salarie[$monthKey];
                        $isBaseSalary = $monthData['paymentId'] === null && $monthData['value'] !== '-' && $monthData['value'] !== '0.00';
                        $typer = $monthData['typer'];
                        $hasQuittanceCah = $monthData['hasQuittanceCah'];
    $badgeClass = $hasQuittanceCah ? 'badge-quittance-cah' : (
        $typer === 'a' ? 'badge-unpaid' : (
            $typer && $typer !== 'a' ? 'badge-paid' : 'badge-initial'
        )
    );                   $monthMap = [
                            'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
                            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
                            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12
                        ];
                        $currentSystemMonthNumber = $monthMap[$currentSystemMonth];
                        $currentMonthNumber = $monthMap[$monthKey];
                        $showIcons = true;
                        if ($salarie['statut'] === 'inactif' && !is_null($salarie['date_demission'])) {
                            $demissionYear = $salarie['date_demission']->year;
                            $demissionMonthNumber = $salarie['date_demission']->month;
                            if ($currentYear > $demissionYear || ($currentYear == $demissionYear && $currentMonthNumber >= $demissionMonthNumber + 1)) {
                                $showIcons = false;
                            }
                        }
                    @endphp
                    <td class="{{ $monthKey == $currentMonth ? 'selected-month' : '' }}" data-id-salarie="{{ $salarie['id_salarie'] }}" data-month="{{ $monthKey }}">
                        <span class="badge {{ $badgeClass }}" style="display: flex; align-items: center; gap: 5px;">
                            <span class="salary-value">{{ $monthData['value'] }}</span>

                            @if ($typer === 'vg')
                                <span class="salary-icon">
                                    <i class="bi bi-people" style="font-size: 0.9em;"></i>
                                </span>
                            @elseif (in_array($typer, ['e', 'c', 'v']))
                                <span class="salary-icon">
                                    <i class="bi bi-person" style="font-size: 0.9em;"></i>
                                </span>
                            @endif
   @if ($monthData['hasPayslip'] ?? false)
    <i class="bi bi-file-earmark-check" 
       style="font-size: 1.15em; color: #ffffff; margin-left: 2px;" 
       title="Bulletin de paie parcouru"></i>
@endif             
  @if ($showIcons && $monthMap[$monthKey] <= $monthMap[$currentSystemMonth])
                                <div class="dropdown-auto" style="display: inline-block;">
                                    <button type="button" class="btn p-0 dropdown salary-icon" data-bs-toggle="dropdown" style="font-size: 0.9em;" aria-label="Ouvrir le menu des actions">
                                        <i class="bi bi-list"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-horizontal">
                                        <a href="#" 
                                           class="dropdown-item increment-salary {{ $monthKey == $currentMonth ? '' : 'disabled' }}"
                                           data-id-salarie="{{ $salarie['id_salarie'] }}"
                                           data-year="{{ $currentYear }}"
                                           data-month="{{ $monthKey }}"
                                           title="{{ $monthKey == $currentMonth ? 'Incrémenter et Payer' : 'Calcul bloqué : mois non sélectionné' }}"
                                           {{ $monthKey == $currentMonth ? '' : 'aria-disabled=true' }}>
                                            <i class="bi bi-calculator"></i> <span class='ca'>Calculer salaire</span>
                                        </a>
                                        <a href="#" 
                                           class="dropdown-item add-salary-payment {{ $monthKey == $currentMonth ? '' : 'disabled' }}"
                                           data-id-salarie="{{ $salarie['id_salarie'] }}"
                                           data-year="{{ $currentYear }}"
                                           data-month="{{ $monthKey }}"
                                           title="{{ $monthKey == $currentMonth ? 'Exécuter Paiement' : 'Paiement bloqué : mois non sélectionné' }}"
                                           {{ $monthKey == $currentMonth ? '' : 'aria-disabled=true' }}>
                                            <i class="bi bi-cash"></i> <span class='pa'>Payée salaire</span> 
                                        </a>
                                        <a href="{{ route('bultin.downloadQuittance') }}" 
                                           class="dropdown-item download-quittance {{ $monthKey == $currentMonth ? '' : 'disabled' }}"
                                           data-id-salarie="{{ $salarie['id_salarie'] }}"
                                           data-year="{{ $currentYear }}"
                                           data-month="{{ $monthKey }}"
                                           title="{{ $monthKey == $currentMonth ? 'Télécharger Quittance' : 'Téléchargement bloqué : mois non sélectionné' }}"
                                           {{ $monthKey == $currentMonth ? '' : 'aria-disabled=true' }}>
                                            <i class="bi bi-download"></i> Télécharger quittance
                                        </a>
                                        <a href="#" 
                                           class="dropdown-item upload-quittance {{ $monthKey == $currentMonth ? '' : 'disabled' }}"
                                           data-id-salarie="{{ $salarie['id_salarie'] }}"
                                           data-year="{{ $currentYear }}"
                                           data-month="{{ $monthKey }}"
                                           title="{{ $monthKey == $currentMonth ? 'Parcourir Quittance' : 'Téléversement bloqué : mois non sélectionné' }}"
                                           {{ $monthKey == $currentMonth ? '' : 'aria-disabled=true' }}>
                                            <i class="bi bi-receipt"></i> <span class='par'>Parcourir quittance</span> 
                                        </a>
                                        <a href="#" 
                                        class="dropdown-item download-quittance-cah {{ $monthKey == $currentMonth ? '' : 'disabled' }}"
                                        data-id-salarie="{{ $salarie['id_salarie'] }}"
                                        data-year="{{ $currentYear }}"
                                        data-month="{{ $monthKey }}"
                                        title="{{ $monthKey == $currentMonth ? 'Télécharger Quittance Cachetée' : 'Téléchargement bloqué : mois non sélectionné' }}"
                                        {{ $monthKey == $currentMonth ? '' : 'aria-disabled=true' }}>
                                            <i class="bi bi-receipt-cutoff"></i> Télécharger quittance cachetée
                                        </a>
                                        <a href="#" 
                                           class="dropdown-item download-pay-slip {{ $monthKey == $currentMonth ? '' : 'disabled' }}"
                                           data-id-salarie="{{ $salarie['id_salarie'] }}"
                                           data-year="{{ $currentYear }}"
                                           data-month="{{ $monthKey }}"
                                           title="{{ $monthKey == $currentMonth ? 'Télécharger Bulletin de Paie' : 'Téléchargement bloqué : mois non sélectionné' }}"
                                           {{ $monthKey == $currentMonth ? '' : 'aria-disabled=true' }}>
                                            <i class="bi bi-file-earmark-pdf"></i> Télécharger bulletin de paie
                                        </a>
                                        <a href="#" 
                                           class="dropdown-item upload-pay-slip {{ $monthKey == $currentMonth ? '' : 'disabled' }}"
                                           data-id-salarie="{{ $salarie['id_salarie'] }}"
                                           data-year="{{ $currentYear }}"
                                           data-month="{{ $monthKey }}"
                                           title="{{ $monthKey == $currentMonth ? 'Parcourir Bulletin de Paie' : 'Téléversement bloqué : mois non sélectionné' }}"
                                           {{ $monthKey == $currentMonth ? '' : 'aria-disabled=true' }}>
                                            <i class="bi bi-file-earmark-pdf"></i> Parcourir bulletin de paie
                                        </a>
                                          <a href="#" 
                                        class="dropdown-item download-pay-slip-cachet {{ $monthKey == $currentMonth ? '' : 'disabled' }}"
                                        data-id-salarie="{{ $salarie['id_salarie'] }}"
                                        data-year="{{ $currentYear }}"
                                        data-month="{{ $monthKey }}"
                                        title="{{ $monthKey == $currentMonth ? 'Télécharger Bulletin de Paie Cacheté' : 'Téléchargement bloqué : mois non sélectionné' }}"
                                        {{ $monthKey == $currentMonth ? '' : 'aria-disabled=true' }}>
                                            <i class="bi bi-file-earmark-check"></i> Télécharger bulletin cacheté
                                        </a>
                                        <a href="#" 
                                           class="dropdown-item delete-salary-payment {{ $monthKey == $currentMonth ? '' : 'disabled' }}"
                                           data-id-salarie="{{ $salarie['id_salarie'] }}"
                                           data-year="{{ $currentYear }}"
                                           data-month="{{ $monthKey }}"
                                           title="{{ $monthKey == $currentMonth ? 'Supprimer Calcul et Paiement' : 'Suppression bloquée : mois non sélectionné' }}"
                                           {{ $monthKey == $currentMonth ? '' : 'aria-disabled=true' }}>
                                            <i class="bi bi-trash"></i> Supprimer calcul et paiement
                                        </a>
                                      
                                    </div>
                                </div>
                            @endif
                        </span>
                    </td>
                @endforeach
   <td class="d-flex justify-content-center align-items-center">
    <i class="bi bi-file-earmark-pdf generate-annual-payslip text-warning" style="font-size: 1.2rem;" 
       data-id-salarie="{{ $salarie['id_salarie'] }}"
       data-year="{{ $currentYear }}"
       title="Générer Bulletin de Paie Annuel"></i>
</td>
            </tr>
        @endforeach
    </tbody>
</table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: rgba(0, 0, 0, 0.5); border: none; box-shadow: none;">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2 text-white">Enregistrement en cours...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const companySettings = @json($companySettings ?? []);
        console.log('Company Settings:', companySettings);

        document.addEventListener('DOMContentLoaded', function() {
            const stickyCard = document.getElementById('stickyCard');
            const originalOffsetTop = stickyCard.offsetTop;

            window.addEventListener('scroll', function() {
                if (window.pageYOffset >= originalOffsetTop) {
                    stickyCard.style.position = 'fixed';
                    stickyCard.style.top = '0';
                    stickyCard.style.width = stickyCard.parentElement.offsetWidth + 'px';
                } else {
                    stickyCard.style.position = 'sticky';
                    stickyCard.style.top = '0';
                    stickyCard.style.width = 'auto';
                }
            });
        });
    </script>

    <script src="{{ asset('assets/js/bultinpaie.js') }}"></script>

@endsection