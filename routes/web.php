<?php

use App\Http\Controllers\AbsenceControleur;
use App\Http\Controllers\BultinController;
use App\Http\Controllers\BultinSaController;
use App\Http\Controllers\CongesControler;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepenseController;
use App\Http\Controllers\loginController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NatureDepenseController;
use App\Http\Controllers\PointageController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\SalarieController;
use App\Http\Controllers\SalarieFonctionController;
use App\Http\Controllers\PointageadDuControler;
use App\Http\Controllers\CongeAdministratifController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\DpProjetController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\OrdermissionsuperController;
use App\Http\Controllers\OpenAiTestController;
use App\Http\Controllers\OrdreVirementController;
use App\Http\Controllers\OrdermissionsalController;

// Public routes
Route::get('/', [loginController::class, 'login'])->name('login');
Route::post('/login-post', [loginController::class, 'loginPost'])->name('login.post');
Route::get('/log', function () {
    return view('login');
})->name('login.view');
Route::get('/factures/vat-report', [FactureController::class, 'generateVatReport'])->name('factures.vat-report');

Route::get('/tickets/{id}/close/{token}', function($id, $token) {
    return view('tickets.close', compact('id', 'token'));
})->name('tickets.close');

Route::post('/tickets/{id}/close/{token}', [TicketController::class, 'close'])->name('tickets.close.post');

Route::get('/salaries/generate-matricule', [SalarieController::class, 'generateMatricule'])->name('salaries.generate-matricule');
Route::get('/salaries/resigned', [SalarieController::class, 'resigned'])->name('salaries.resigned')->middleware('role:superadmin');
Route::post('/logout', [UserController::class, 'logout'])->name('logout');
Route::middleware('auth')->delete('/tickets/{id}', [TicketController::class, 'destroy'])->name('tickets.destroy');
// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/welcome', [loginController::class, 'welcome'])->name('welcome');
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');
    Route::post('/profil/update', [ProfilController::class, 'updateProfile'])->name('profil.update');
    Route::post('/profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password');
    Route::post('/profil/image/delete', [ProfilController::class, 'deleteProfileImage'])->name('profil.image.delete');

    Route::get('/test-openai', [OpenAiTestController::class, 'index'])->name('test.openai');
    Route::post('/test-openai/import', [OpenAiTestController::class, 'import'])->name('test.openai.import');


// tickets 
Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/list', [TicketController::class, 'list'])->name('tickets.list');
    
    Route::patch('/tickets/{id}/close', [TicketController::class, 'closeTicket'])->name('tickets.close.ajax');
    // Route::delete('/tickets/{id}', [TicketController::class, 'destroy'])->name('tickets.destroy');
Route::patch('/tickets/{id}/close-ticket', [TicketController::class, 'closeTicket'])->name('tickets.closeTicket');
Route::get('/tickets/{id}', [TicketController::class, 'show'])->name('tickets.show');
    // Menu Permissions Route
    Route::get('/menu-permissions', [UserController::class, 'getMenuPermissions'])->name('menu.permissions')->middleware('role:superadmin');

    // Superadmin Routes
    Route::middleware('role:superadmin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/vehicles', [DashboardController::class, 'getVehicles'])->name('dashboard.vehicles');   
        Route::get('/users', [UserController::class, 'showUsersView'])->name('users.index');
        Route::get('/users/list', [UserController::class, 'index'])->name('users.list');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::put('/users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
// Route pour charger les véhicules avec AJAX
Route::get('/dashboard/load-vehicles', [DashboardController::class, 'loadVehicles'])->name('dashboard.loadVehicles');
Route::get('/dashboard/vehicles-paginated', [DashboardController::class, 'getVehiclesPaginated'])->name('dashboard.vehicles.paginated');
     

        
 // facturation 
Route::get('/factures', [FactureController::class, 'index'])->name('factures.index');

Route::get('/factures/totals/{type}', [FactureController::class, 'totals'])->name('factures.totals');
Route::get('/factures/declaration', [FactureController::class, 'getDeclaration'])->name('factures.declaration');

Route::post('/factures', [FactureController::class, 'store'])->name('factures.store');
Route::get('/factures/{id}', [FactureController::class, 'show'])->name('factures.show');
Route::put('/factures/{id}', [FactureController::class, 'update'])->name('factures.update');
Route::delete('/factures/{id}', [FactureController::class, 'destroy'])->name('factures.destroy');
Route::post('/entities', [FactureController::class, 'storeEntity'])->name('entities.store');
Route::get('/factures/{id}/download', [FactureController::class, 'downloadFile'])->name('factures.download');
Route::post('/factures/import-excel', [FactureController::class, 'importExcel'])->name('factures.import-excel');
Route::get('/factures/download-template/{type}', [FactureController::class, 'downloadTemplate'])->name('factures.download.template');

Route::post('/releves',              [FactureController::class, 'storeReleve']);
Route::delete('/releves/{id}',       [FactureController::class, 'destroyReleve']);
Route::get('/releves/{id}/download', [FactureController::class, 'downloadReleve']);

Route::put('/factures/{id}/paiement', [FactureController::class, 'updatePaiement']);
// routes/web.php
Route::put('/factures/{id}/encaissement', [FactureController::class, 'updateEncaissement'])->name('factures.encaissement.update');
Route::post('/factures/scan', [FactureController::class, 'scan'])->name('factures.scan');
Route::post('/releves/scan', [FactureController::class, 'scanReleve']);






   Route::get('manage-entities', [EntityController::class, 'index'])->name('manage-entities.index');
Route::get('manage-entities/create', [EntityController::class, 'create'])->name('manage-entities.create');
Route::post('manage-entities', [EntityController::class, 'store'])->name('manage-entities.store');
Route::get('manage-entities/{id}', [EntityController::class, 'show'])->name('manage-entities.show');
Route::get('manage-entities/{id}/edit', [EntityController::class, 'edit'])->name('manage-entities.edit');
Route::put('manage-entities/{id}', [EntityController::class, 'update'])->name('manage-entities.update');
Route::patch('manage-entities/{id}', [EntityController::class, 'update'])->name('manage-entities.update'); 
Route::delete('manage-entities/{id}', [EntityController::class, 'destroy'])->name('manage-entities.destroy');


Route::prefix('clients')->name('clients.')->middleware(['auth'])->group(function () {
    Route::get('/', [ClientController::class, 'index'])->name('index');
    Route::post('/', [ClientController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [ClientController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ClientController::class, 'update'])->name('update');
    Route::delete('/{id}', [ClientController::class, 'destroy'])->name('destroy');
});

// routes/web.php
Route::prefix('ordermissionsuper')
    ->name('ordermissionsuper.')
    ->middleware(['auth', 'role:superadmin'])
    ->group(function () {

        Route::get('/', [OrdermissionsuperController::class, 'index'])->name('index');
        Route::post('/store', [OrdermissionsuperController::class, 'store'])->name('store');
        
        // Routes pour modification
        Route::get('{id}/edit', [OrdermissionsuperController::class, 'edit'])->name('edit');
        Route::put('{id}', [OrdermissionsuperController::class, 'update'])->name('update');
        
        // Autres routes
        Route::delete('{id}', [OrdermissionsuperController::class, 'destroy'])->name('destroy');
        
        Route::post('{id}/validate', [OrdermissionsuperController::class, 'validateOrdre'])
             ->name('validate');
             
        Route::get('{id}/fiche', [OrdermissionsuperController::class, 'showFiche'])
        
             ->name('fiche');
        Route::get('/ordermissionsuper/{id}/fiche', [OrdermissionsuperController::class, 'showFiche'])->name('ordermissionsuper.fiche');

});
Route::post('/clients/store-from-facture', [FactureController::class, 'storeClient'])->name('clients.store.from.facture');


        Route::get('/salaries/fonction', [SalarieFonctionController::class, 'index'])->name('fonction.index');
        Route::get('/salaries/fonction/data', [SalarieFonctionController::class, 'getFonctions'])->name('fonction.data');
        Route::get('/salaries/fonction/{id}', [SalarieFonctionController::class, 'show'])->name('fonction.show');
        Route::post('/salaries/fonction', [SalarieFonctionController::class, 'store'])->name('fonction.store');
        Route::put('/salaries/fonction/{id}', [SalarieFonctionController::class, 'update'])->name('fonction.update');
        Route::delete('/salaries/fonction/{id}', [SalarieFonctionController::class, 'destroy'])->name('fonction.destroy');

        Route::get('/salaries', [SalarieController::class, 'index'])->name('salaries.index');
        Route::get('/salaries/list', [SalarieController::class, 'list'])->name('salaries.list');
        Route::post('/salaries', [SalarieController::class, 'store'])->name('salaries.store');
        Route::get('/salaries/{salarie}/edit', [SalarieController::class, 'edit'])->name('salaries.edit');
        Route::put('/salaries/{salarie}', [SalarieController::class, 'update'])->name('salaries.update');
        Route::delete('/salaries/{salarie}', [SalarieController::class, 'destroy'])->name('salaries.destroy');
        Route::get('salaries/{salarie}', [SalarieController::class, 'show'])->name('salaries.show');
        Route::post('/salaries/demission', [SalarieController::class, 'demission'])->name('salaries.demission');
        Route::post('/salaries/preavis', [SalarieController::class, 'storePreavis'])->name('salaries.preavis.store');
        Route::post('/salaries/check-unique', [SalarieController::class, 'checkUnique'])->name('salaries.check-unique');
        Route::get('/download/demission/{id}', [SalarieController::class, 'downloadDemission'])->name('download.demission');
        Route::get('/download/preavis/{id}', [SalarieController::class, 'downloadPreavis'])->name('download.preavis');
        Route::get('/salaries/resigned', [SalarieController::class, 'resigned'])->name('salaries.resigned');
        Route::put('/demissions/{id}', [SalarieController::class, 'updateDemission'])->name('demissions.update');
        Route::post('/salaries/{id}/reactivate', [SalarieController::class, 'reactivate'])->name('salaries.reactivate');
        Route::post('/salaries/demission/update/{id}', [SalarieController::class, 'updateDemission'])->name('salaries.demission.update');
        Route::get('/conges-administratif', [CongeAdministratifController::class, 'index'])->name('conges.administratif');
        Route::post('/conges-administratif/ajouter', [CongeAdministratifController::class, 'store'])->name('conge.store');
        Route::post('/conges-administratif/approve/{id}', [CongeAdministratifController::class, 'approve'])->name('conge.approve');
        Route::get('/conges-administratif/download-pdf/{id}', [CongeAdministratifController::class, 'downloadPdf'])->name('conge.download.administration.pdf');
        Route::get('/conges-administratif/calendar', [CongeAdministratifController::class, 'getCalendarEvents'])->name('conge.calendar');

        Route::delete('/conges-administratif/destroy/{id}', [CongeAdministratifController::class, 'destroy'])
        ->name('conge.destroy');
        Route::get('/conges-administratif/edit/{id}', [CongeAdministratifController::class, 'edit'])->name('conge.edit');
        Route::put('/conges-administratif/update/{id}', [CongeAdministratifController::class, 'update'])->name('conge.update')->middleware(['auth', 'role:superadmin']);

        Route::delete('/conges-administratif/delete/{id}', [CongeAdministratifController::class, 'destroy'])->name('conge.destroy');


        Route::get('/presence', [PresenceController::class, 'index'])->name('presence.index');
        Route::get('/presence/employees', [PresenceController::class, 'getEmployeesByStatus'])->name('presence.employees');
        Route::get('/presence/pointage-by-month', [PresenceController::class, 'getPointageByMonth'])->name('presence.pointage-by-month');
        Route::post('/presence/toggle', [PresenceController::class, 'togglePresence'])->name('presence.toggle');
        Route::post('/presence/delete-absence', [PresenceController::class, 'deleteAbsence'])->name('presence.delete-absence');
        Route::post('/presence/confirm-delete-absence', [PresenceController::class, 'confirmDeleteAbsence'])->name('presence.confirm-delete-absence');

        Route::get('/absences', [AbsenceControleur::class, 'index'])->name('absences.employees');
        Route::get('/absences/{id}', [AbsenceControleur::class, 'getAbsence'])->name('absences.get');
        Route::post('/absence/justify/{id}', [AbsenceControleur::class, 'justify'])->name('absence.justify');
        Route::post('/absences/{id}/justification', [AbsenceControleur::class, 'updateJustification'])->name('absences.justification');

        Route::get('/bultin', [BultinController::class, 'index'])->name('bultin.index');
        Route::post('/bultin/check-base-salary', [BultinController::class, 'checkBaseSalary'])->name('bultin.checkBaseSalary');
        Route::post('/bultin/increment-salary', [BultinController::class, 'incrementSalary'])->name('bultin.incrementSalary');
        Route::post('bultin/increment-salary-from-net', [BultinController::class, 'incrementSalaryFromNet'])->name('bultin.incrementSalaryFromNet');
        Route::post('/bultin/add-salary-payment', [BultinController::class, 'addSalaryPayment'])->name('bultin.addSalaryPayment');
        Route::post('bultin/upload-quittance', [BultinController::class, 'uploadQuittance'])->name('upload-quittance-url');
        Route::post('bultin/download-quittance', [BultinController::class, 'downloadQuittance'])->name('bultin.downloadQuittance');
        Route::get('/bultin/downloadGroupPayment/{year}/{month}', [BultinController::class, 'downloadGroupPayment'])->name('bultin.downloadGroupPayment');
        Route::post('/bultin/update-anciennete', [BultinController::class, 'updateAnciennete'])->name('bultin.update-anciennete');
        Route::post('/generate-payslip-pdf', [BultinController::class, 'generatePaySlipPDF'])->name('generate_payslip_pdf');
        Route::post('/bultin/download-pay-slip', [BultinController::class, 'downloadPaySlip'])->name('bultin.downloadPaySlip');
        Route::post('/bultin/upload-pay-slip', [BultinController::class, 'uploadPaySlip'])->name('bultin.uploadPaySlip');
        Route::post('/salary/bulk-payment', [BultinController::class, 'addBulkSalaryPayment'])->name('salary.bulk-payment');
        Route::post('/upload-bulk-payroll', [BultinController::class, 'uploadBulkPayroll'])->name('upload-bulk-payroll');
        Route::post('/bultin/delete-salary-payment', [BultinController::class, 'deleteSalaryPayment'])->name('bultin.deleteSalaryPayment');
        Route::post('/bultin/generate-annual-payslip', [BultinController::class, 'generateAnnualPaySlip'])->name('bultin.generateAnnualPaySlip');
        Route::post('/bultin/download-pay-slip-cachet', [BultinController::class, 'downloadPaySlipCachet'])
    ->name('bultin.downloadPaySlipCachet');
    Route::post('/bultin/download-quittance-cah', [BultinController::class, 'downloadQuittanceCah'])
    ->name('bultin.downloadQuittanceCah');
                
        Route::post('/generate-notepad', [BultinController::class, 'generateNotepad'])->name('generate.notepad');
       Route::post('/calculate-and-generate-payslips', [BultinController::class, 'calculateAndGeneratePaySlips'])->name('calculate.and.generate.payslips');
       
        Route::post('/generate-payslips-zip', [BultinController::class, 'generatePayslipsZip'])->name('payslips.zip');
        
        Route::post('/update-all-anciennetes', [BultinController::class, 'updateAllAnciennetes'])->name('bultin.update-all-anciennetes');
        
         Route::post('/fetch-payroll-data', [BultinController::class, 'fetchPayrollData'])->name('fetch-payroll-data');

        Route::post('/bultin/configuration-bp/get', [BultinController::class, 'getConfigurationBp'])->name('bultin.config-bp.get');

        


Route::post('bultin/generate-virements-pdf', [BultinController::class, 'generateVirementsPdf'])
    ->name('bultin.generateVirementsPdf');

Route::get('bultin/download-virements-pdf/{year}/{month}', [BultinController::class, 'downloadVirementsPdf'])
    ->name('bultin.downloadVirementsPdf');

Route::post('bultin/upload-virements-pdf', [BultinController::class, 'uploadVirementsPdf'])
    ->name('bultin.uploadVirementsPdf');
      Route::post('/bultin/print-data', [BultinController::class, 'getPrintData'])
     ->name('bultin.printData');


        Route::get('/projets', [ProjetController::class, 'index'])->name('projets.index');
        Route::get('/projets/list', [ProjetController::class, 'list'])->name('projets.list');
        Route::get('/projets/{projet}', [ProjetController::class, 'show'])->name('projets.show');
        Route::post('/projets', [ProjetController::class, 'store'])->name('projets.store');
        Route::get('/projets/{projet}/edit', [ProjetController::class, 'edit'])->name('projets.edit');
        Route::put('/projets/{projet}', [ProjetController::class, 'update'])->name('projets.update');
        Route::delete('/projets/{projet}', [ProjetController::class, 'destroy'])->name('projets.destroy');
        Route::post('/projets/{projet}/cloturer', [ProjetController::class, 'cloturer'])->name('projets.cloturer');
        Route::post('/projets/{id}/decloturer', [ProjetController::class, 'decloturer'])->name('projets.decloturer');
        Route::get('/projets/{projet}/salaries', [ProjetController::class, 'getSalaries'])->name('projets.salaries');
        Route::post('/projets/affecter', [ProjetController::class, 'affecter'])->name('projets.affecter');
        Route::post('/projets/set-exit-date', [ProjetController::class, 'setExitDate'])->name('projets.setExitDate');
        Route::post('/projets/store-public', [ProjetController::class, 'storePublic'])->name('projets.store-public');
        Route::post('/projets/upload-documents', [ProjetController::class, 'uploadDocuments'])->name('projets.upload-documents');
        Route::get('/projets/{projet_id}/documents', [ProjetController::class, 'getDocuments'])->name('projets.get-documents');
        Route::get('projets/{projet}/download-dossier', [ProjetController::class, 'downloadDossier'])->name('projets.download-dossier');
        Route::get('/projets/{projet}/check-dossier', [ProjetController::class, 'checkDossierFiles'])->name('projets.check-dossier');
        Route::get('/projets/{id}/check-dossier', [ProjetController::class, 'checkDossier'])->name('projets.check-dossier');
        Route::get('/projets/{id}/download-decompte-dossier', [DownloadController::class, 'downloadDecompteDossier']);
        Route::get('/projets/{projet_id}/download-document/{document_id}', [DownloadController::class, 'downloadSingleDocument']);
        Route::get('/projets/{id}/download-ordre-service-dossier', [DownloadController::class, 'downloadOrdreServiceDossier']);
        Route::post('/ordres-service/store', [ProjetController::class, 'storeOrdreService'])->name('ordres-service.store');
        Route::get('/projets/{projet_id}/ordres-service', [ProjetController::class, 'getOrdresService'])->name('ordres-service.index');
        Route::get('/projets/{projet}/affectation-data', [ProjetController::class, 'getAffectationData'])->name('projets.affectation-data');
        Route::delete('/projets/{projet_id}/ordres-service/{pair_index}', [ProjetController::class, 'destroyOrdreService'])->name('ordres-service.destroy');
        Route::get('/projets/check-presence/{salarie_id}', [ProjetController::class, 'checkPresence'])->name('projets.check-presence');
        Route::delete('/projets/{projet}/salarie/{salarie}/detach', [ProjetController::class, 'detachSalarie'])->name('projets.detach-salarie');
        Route::post('/projets/upload-private-documents', [ProjetController::class, 'uploadPrivateDocuments'])->name('projets.upload-private-documents');
        Route::get('/projets/{projet}/download-private-dossier', [ProjetController::class, 'downloadPrivateDossier'])->name('projets.download-private-dossier');
        

        Route::get('/decomptes', [DpProjetController::class, 'index'])->name('decomptes.index');
        Route::get('/decomptes/public/list', [DpProjetController::class, 'listPublicProjects'])->name('decomptes.public.list');
        Route::get('/decomptes/list', [DpProjetController::class, 'listDecomptes'])->name('decomptes.list');
        Route::post('/decomptes', [DpProjetController::class, 'storeDecompte'])->name('decomptes.store');
        Route::get('/decomptes/print/{projet_id}', [DpProjetController::class, 'printDecompte'])->name('decomptes.print');
        Route::delete('/decomptes/{id}', [DpProjetController::class, 'destroyDecompte'])->name('decomptes.destroy');
        Route::get('/decomptes/{id}/edit', [DpProjetController::class, 'editDecompte'])->name('decomptes.edit');
        Route::put('/decomptes/{id}', [DpProjetController::class, 'updateDecompte'])->name('decomptes.update');

        Route::get('/parametres', [ParametreController::class, 'index'])->name('parametres.index');
        Route::post('/parametres/update', [ParametreController::class, 'parametres'])->name('parametres.update');
        Route::put('/parametres/cotisations', [ParametreController::class, 'updateCotisations'])->name('cotisations.update');
        Route::put('/parametres/impotRevenus', [ParametreController::class, 'updateImpotRevenus'])->name('impotRevenus.update');
        Route::put('/parametres/fraisPros', [ParametreController::class, 'updateFraisPros'])->name('fraisPros.update');
        Route::post('/parametres/fraisPros', [ParametreController::class, 'storeFraisPros'])->name('fraisPros.store');
        Route::delete('/frais-pros/{id}', [ParametreController::class, 'destroyFraisPros'])->name('fraisPros.destroy');
        Route::post('/create-salary-table', [ParametreController::class, 'createSalaryTable'])->name('create.salary.table');
        Route::post('/anciennete-taux', [ParametreController::class, 'storeAncienneteTaux'])->name('anciennete.taux.store');
        Route::delete('/anciennete-taux/{id}', [ParametreController::class, 'destroyAncienneteTaux'])->name('anciennete.taux.destroy');
        Route::post('heures-supp/store', [ParametreController::class, 'storeHeurs'])->name('heuresSupp.storeHeurs');
        Route::delete('/heures-supp/{id}', [ParametreController::class, 'destroyHeurs'])->name('heuresSupp.destroy');
        Route::put('heures-supp/update', [ParametreController::class, 'updateHeurs'])->name('heuresSupp.updateHeurs');
        Route::put('/anciennete-taux', [ParametreController::class, 'updateAncienneteTaux'])->name('anciennete.taux.update');
        Route::put('/company-settings', [ParametreController::class, 'updateCompanySettings'])->name('company.settings.update');
        Route::post('/company/documents/store', [ParametreController::class, 'storeCompanyDocuments'])->name('company.documents.store');
        Route::put('/company/documents/update', [ParametreController::class, 'updateCompanyDocuments'])->name('company.documents.update');
        Route::get('/company/documents/get', [ParametreController::class, 'getCompanyDocuments'])->name('company.documents.get');    
        Route::post('/company/documents/add-vehicle', [ParametreController::class, 'addVehicleInsurance'])->name('company.documents.add.vehicle');
        Route::post('/company/documents/add-additional', [ParametreController::class, 'addAdditionalDocument'])->name('company.documents.add.additional');
        Route::post('/vehicles/store', [ParametreController::class, 'storeVehicle'])->name('vehicles.store');
        Route::put('/vehicles/{id}', [ParametreController::class, 'updateVehicle'])->name('vehicles.update'); // Fixed: Changed POST to PUT and method name
        Route::post('/vehicles/{id}/add-additional', [ParametreController::class, 'addAdditionalVehicleDocument'])->name('vehicles.add.additional'); // Fixed: Added closing quote
        Route::put('/parametres/jrsFerie', [ParametreController::class, 'updateJrsFerie'])->name('jrsFerie.update');
        
        Route::delete('/jours-feries/{id}', [ParametreController::class, 'destroyJourFerie'])->name('jrsFerie.destroy');
        Route::post('/vehicles/expense/store', [ParametreController::class, 'storeVehicleExpense'])->name('vehicles.expense.store');
        Route::get('/vehicles', [ParametreController::class, 'getVehicles'])->name('vehicles.get');
        Route::delete('/vehicles/{id}', [ParametreController::class, 'destroyVehicle'])
        ->name('vehicles.destroy');
        Route::post('/vehicles/{id}/add-resiliation-contract', [ParametreController::class, 'addResiliationContract'])
     ->name('vehicles.add.resiliation.contract');      

        Route::get('/vehicles/{id}/resiliation-pdf', [ParametreController::class, 'generateResiliationPdf'])
     ->name('vehicles.resiliation.pdf');

        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');
        Route::delete('/notifications/delete-all', [NotificationController::class, 'deleteAll'])->name('notifications.deleteAll');
    });
    // Routes shared for Superadmin, Admin, and Manager
    Route::middleware('role:superadmin,admin,manager,salarier,responsable')->group(function () {
        Route::get('/nature-depenses', [NatureDepenseController::class, 'index'])->name('nature_depenses.index');
        Route::post('/nature-depenses', [NatureDepenseController::class, 'store'])->name('nature_depenses.store');
        Route::put('/nature-depenses/{id}', [NatureDepenseController::class, 'update'])->name('nature_depenses.update');
        Route::delete('/nature-depenses/{id}', [NatureDepenseController::class, 'destroy'])->name('nature_depenses.destroy');

        Route::get('/depenses', [DepenseController::class, 'index'])->name('depenses.index');

        
        Route::post('/depenses', [DepenseController::class, 'store'])->name('depenses.depenses');
        Route::get('/depenses/{id}/edit', [DepenseController::class, 'edit'])->name('depenses.edit');
        Route::put('/depenses/{id}', [DepenseController::class, 'update'])->name('depenses.update');
        Route::delete('/depenses/{id}', [DepenseController::class, 'destroy'])->name('depenses.destroy');
        
        Route::get('/depenses/varie', [DepenseController::class, 'varie'])->name('depenses.varie');
        Route::get('/depenses/avancements', [DepenseController::class, 'avancements'])->name('depenses.avancements');
        Route::get('/depenses/vehicle', [DepenseController::class, 'vehicle'])->name('depenses.vehicle');

        Route::post('/depenses/store', [DepenseController::class, 'store'])->name('depenses.store');
                
    });
    // Salarier Routes
    Route::middleware('role:salarier,responsable')->group(function () {
        Route::get('/congés', [CongesControler::class, 'index'])->name('conge.index');
        Route::post('/congés', [CongesControler::class, 'store'])->name('conge.salarie.store');
        Route::get('/conges/download-pdf/{id}', [CongesControler::class, 'downloadPdf'])->name('conge.download.pdf');

        Route::get('/bulletinSa', [BultinSaController::class, 'index'])->name('bulletinsa.index');
        Route::get('/bulletin/downloadPaySlip/{id_salarie}/{year}/{month}', [BultinSaController::class, 'downloadPaySlip'])->name('bulletin.downloadPaySlip');
        Route::get('/bulletin/downloadHiddenPaySlip/{id_salarie}/{year}/{month}', [BultinSaController::class, 'downloadHiddenPaySlip'])->name('bulletin.downloadHiddenPaySlip');
    });

    // Responsable Routes
    Route::middleware('role:superadmin')->group(function () {
       Route::get('/pointage-administratif', [PointageadDuControler::class, 'index'])->name('pointage.administratif');
        Route::post('/pointage/admin/save', [PointageadDuControler::class, 'save'])->name('pointage.admin.save');
    });
        Route::delete('/demissions/{id}', [SalarieController::class, 'destroyDemission'])->name('demissions.destroy');
            Route::delete('/frais-pro/{id}', [ParametreController::class, 'deleteFraisPro'])->name('fraisPros.delete');
    

               // Responsable Routes
    Route::middleware('role:responsable')->group(function () {
        Route::get('/pointage', [PointageController::class, 'index'])->name('pointage.index');
        Route::post('/pointage/save', [PointageController::class, 'savePresence'])->name('pointage.save');
    });
});


// ==================== ORDRES DE MISSION SALARIÉ ====================
Route::middleware('role:salarier,responsable')->group(function () {
    Route::resource('ordermissionsal', OrdermissionsalController::class)->only(['index']);
    Route::get('ordermissionsal/{id}/fiche', [OrdermissionsalController::class, 'showFiche'])
         ->name('ordermissionsal.fiche');

         Route::get('ordermissionsal/{id}/edit', [OrdermissionsalController::class, 'getForEdit']);
Route::post('ordermissionsal/{id}/update', [OrdermissionsalController::class, 'updateMission']);
});
Route::resource('manage-ordres-virement', OrdreVirementController::class);
Route::get('manage-ordres-virement/{id}/download', [OrdreVirementController::class, 'download'])->name('manage-ordres-virement.download');
Route::get('/depenses/avances/pdf', [DepenseController::class, 'downloadAvancesPdf'])->name('depenses.avances.pdf');
Route::get('/depenses/avances/pdf/selected', [DepenseController::class, 'downloadSelectedAvancesPdf'])->name('depenses.avances.pdf.selected');
Route::post('/manage-ordres-virement/generate-reference', [OrdreVirementController::class, 'generateReference'])->name('manage-ordres-virement.generate-reference');
Route::get('manage-ordres-virement/entite/{id}/ribs', [OrdreVirementController::class, 'getEntiteRibs'])
    ->name('manage-ordres-virement.entite-ribs');
Route::get('/depenses/avances/pdf', [DepenseController::class, 'downloadAvancesPdf'])->name('depenses.avances.pdf');
Route::get('/depenses/avances/pdf/selected', [DepenseController::class, 'downloadSelectedAvancesPdf'])->name('depenses.avances.pdf.selected');
Route::get('/depenses/avances/last-reference', [DepenseController::class, 'getLastAvanceReference'])
    ->name('depenses.avances.last-reference');

Route::get('/depenses/check-reference', [DepenseController::class, 'checkReferenceExists'])
    ->name('depenses.check-reference');
Route::get('/depenses/reference/check', [DepenseController::class, 'checkReferenceExists'])
    ->name('depenses.reference.check');

