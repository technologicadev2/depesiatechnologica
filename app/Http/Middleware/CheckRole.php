<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserMenuPermission;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $routeName = $request->route()->getName();
        Log::info("Checking access for user ID {$user->id}, route: {$routeName}, roles: " . implode(',', $roles));
        Log::info("User role: " . ($user->role ? $user->role->name : 'none'));
        $userPermissions = UserMenuPermission::where('user_id', $user->id)->pluck('menu_name')->toArray();
        Log::info("Menu permissions: " . json_encode($userPermissions));

        $publicRoutes = ['profil.index', 'profil.update', 'profil.password', 'profil.image.delete', 'welcome'];
        if (in_array($routeName, $publicRoutes)) {
            return $next($request);
        }

        $hasRole = $user->role && in_array($user->role->name, $roles);
        $hasExactPermission = in_array($routeName, $userPermissions);

        $menuActions = [
            'salaries.index' => ['salaries.store', 'salaries.edit', 'salaries.update', 'salaries.destroy', 'salaries.demission', 'salaries.preavis.store', 'salaries.reactivate', 'salaries.list', 'salaries.show', 'salaries.check-unique', 'download.demission', 'download.preavis'],
            'salaries.resigned' => ['demissions.update','salaries.demission.update'],
            'fonction.index' => ['fonction.data', 'fonction.store', 'fonction.update', 'fonction.destroy', 'fonction.show'],
            'conge.index' => ['conge.salarie.store', 'conge.download.pdf'],
            'conges.administratif' => ['conge.store', 'conge.approve', 'conge.calendar', 'conge.download.pdf'],
            'bulletinsa.index' => ['bulletin.downloadPaySlip', 'bulletin.downloadHiddenPaySlip'],
            'bultin.index' => ['bultin.checkBaseSalary', 'bultin.incrementSalary', 'bultin.addSalaryPayment', 'upload-quittance-url', 'bultin.downloadQuittance', 'bultin.downloadGroupPayment', 'bultin.update-anciennete', 'generate_payslip_pdf', 'bultin.downloadPaySlip', 'bultin.uploadPaySlip', 'salary.bulk-payment', 'upload-bulk-payroll', 'bultin.deleteSalaryPayment','calculate.and.generate.payslips','payslips.zip','generate.notepad','bultin.generateAnnualPaySlip','bultin.update-all-anciennetes'],
            'nature_depenses.index' => ['nature_depenses.store', 'nature_depenses.update', 'nature_depenses.destroy'],
            'depenses.varie' => ['depenses.varie'],
            'depenses.avancements' => ['depenses.avancements'],
            'depenses.vehicle' => ['depenses.vehicle'],
            'projets.index' => [
                'projets.store', 'projets.edit', 'projets.update', 'projets.destroy', 
                'projets.cloturer', 'projets.decloturer', 'projets.affecter', 'projets.salaries', 
                'projets.setExitDate', 'projets.store-public', 'projets.upload-documents', 'projets.check-dossier',
                'projets.get-documents', 'projets.download-dossier', 'projets.check-dossier', 
                'ordres-service.store', 'ordres-service.index', 'ordres-service.destroy', 
                'projets.affectation-data', 'projets.check-presence', 'projets.detach-salarie', 
                'projets.upload-private-documents', 'projets.download-private-dossier',
                'projets.list', 'projets.show',
            ],
            'decomptes.index' => ['decomptes.store', 'decomptes.print', 'decomptes.destroy', 'decomptes.public.list', 'decomptes.list'],
            'factures.index' => ['factures.totals', 'factures.store', 'factures.show', 'factures.update', 'factures.destroy', 'factures.download', 'factures.vat-report','factures.import-excel','factures.download.template', 'entities.store'],
            'tickets.index' => ['tickets.store', 'tickets.list', 'tickets.close.ajax', 'tickets.destroy'],
            'parametres.index' => ['parametres.update', 'cotisations.update', 'impotRevenus.update', 'fraisPros.update', 'fraisPros.store', 'create.salary.table', 'anciennete.taux.store', 'anciennete.taux.update', 'company.settings.update', 'company.documents.store', 'company.documents.update', 'company.documents.get', 'company.documents.add.vehicle', 'company.documents.add.additional', 'vehicles.store', 'vehicles.update', 'vehicles.add.additional', 'jrsFerie.update', 'vehicles.expense.store', 'vehicles.get'],
            'presence.index' => ['presence.employees', 'presence.pointage-by-month', 'presence.toggle'],
            'absences.employees' => ['absences.get', 'absence.justify', 'absences.justification'],
            'pointage.index' => ['pointage.save'],
            'pointage.administratif' => ['pointage.admin.save'],
            'dashboard' => ['notifications.read', 'notifications.markAllRead', 'notifications.deleteAll'],
            'manage-entities.index' => ['entities.store', 'manage-entities.create', 'manage-entities.show', 'manage-entities.edit', 'manage-entities.update', 'manage-entities.destroy']
        ];

        $hasRelatedPermission = false;
        foreach ($menuActions as $parent => $actions) {
            if (in_array($parent, $userPermissions)) {
                if (in_array($routeName, $actions)) {
                    $hasRelatedPermission = true;
                    Log::info("Granted access to {$routeName} via parent {$parent}");
                    break;
                }
            }
        }

        if ($hasRole || $hasExactPermission || $hasRelatedPermission) {
            return $next($request);
        }

        Log::error("Access denied for user ID {$user->id} to route {$routeName} - No matching role, exact permission, or related permission");
        abort(403, 'Unauthorized action.');
    }
}