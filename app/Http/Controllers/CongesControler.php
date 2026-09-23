<?php

namespace App\Http\Controllers;

use App\Models\CompanySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Salarie;
use App\Models\Conge;
use App\Notifications\CongeRequestNotification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\User;

class CongesControler extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $salarie = Salarie::find($user->id_salarie);
        $conges = $salarie ? Conge::where('salarie_id', $salarie->id)->orderBy('created_at', 'desc')->get(): collect();
         $companySettings = CompanySettings::first();
        Log::info('Congés récupérés pour le salarié', [
            'salarie_id' => $salarie ? $salarie->id : null,
            'conges_count' => $conges->count(),
        ]);

        return view('conges.conge', compact('conges', 'salarie', 'companySettings'
));
    }
public function store(Request $request)
{
    Log::info('Début de la méthode store', ['request' => $request->except('signature')]);

    try {
        $user = Auth::user();
        $salarie = Salarie::find($user->id_salarie);

        if (!$salarie) {
            return response()->json(['message' => 'Aucun salarié associé à cet utilisateur.'], 422);
        }

        $validated = $request->validate([
            'date_debut'     => 'required|date',
            'nombre_jours'   => 'required|integer|min:1',
            'raison'         => 'required|string',
            'acceptTerms'    => 'accepted',
            'signature'      => 'required|string|starts_with:data:image/png;base64,',
        ]);

        // ─── Sauvegarde signature ─────────────────────────────────────────────
        $directory = public_path('assets/img/signatures');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $signatureData    = str_replace('data:image/png;base64,', '', $validated['signature']);
        $signatureData    = str_replace(' ', '+', $signatureData);
        $signatureContent = base64_decode($signatureData);

        if ($signatureContent === false) {
            throw new \Exception('Données de signature invalides');
        }

        $filename = 'signature_' . time() . '_' . uniqid() . '.png';
        $path     = 'assets/img/signatures/' . $filename;
        file_put_contents(public_path($path), $signatureContent);

        // ─── Récupération de toutes les dates fériées ─────────────────────────
        $feriesDates = [];

        $jourFeries = \App\Models\JourFerie::all();

        foreach ($jourFeries as $ferie) {
            $debut = Carbon::parse($ferie->date_debut);
            $fin   = Carbon::parse($ferie->date_fin ?? $ferie->date_debut);

            $current = $debut->copy();
            while ($current->lte($fin)) {
                $feriesDates[] = $current->format('Y-m-d');
                $current->addDay();
            }
        }

        $feriesDates = array_unique($feriesDates);

        Log::info('Jours fériés chargés', ['count' => count($feriesDates)]);

        // ─── Calcul date_fin : date_debut INCLUS + samedi inclus + fériés exclus ──
        $start   = Carbon::parse($validated['date_debut']);
        $jours   = (int) $validated['nombre_jours'];
        
        $current = $start->copy();   // On commence à la date_debut
        $counted = 0;

        while ($counted < $jours) {
            $weekday = $current->weekday();           // 0=lundi ... 5=samedi, 6=dimanche
            $dateStr = $current->format('Y-m-d');

            // On compte le jour courant si c'est un jour ouvrable (lundi-samedi) et pas férié
            $isOuvrable = ($weekday <= 5) && !in_array($dateStr, $feriesDates);

            if ($isOuvrable) {
                $counted++;
            }

            // On avance seulement si on n'a pas encore atteint le nombre de jours demandé
            if ($counted < $jours) {
                $current->addDay();
            }
        }

        $date_fin = $current->format('Y-m-d');

        // ─── n_jours_reste (on ne soustrait pas encore) ───────────────────────
        $latestConge = Conge::where('salarie_id', $salarie->id)
                            ->orderBy('created_at', 'desc')
                            ->first();

        $n_jours_reste = $latestConge ? $latestConge->n_jours_reste : 18;

        // ─── Création du congé ────────────────────────────────────────────────
        $conge = Conge::create([
            'salarie_id'    => $salarie->id,
            'date_debut'    => $validated['date_debut'],
            'date_fin'      => $date_fin,
            'num_j'         => $validated['nombre_jours'],
            'raison'        => $validated['raison'],
            'accepter'      => 1,
            'signature'     => $path,
            'approbation'   => 0,
            'n_jours_reste' => $n_jours_reste,
        ]);

        Log::info('Congé créé avec succès', [
            'date_debut' => $validated['date_debut'],
            'date_fin'   => $date_fin,
            'jours_demandes' => $jours
        ]);

        // Notifications
        $superAdmins = User::whereHas('role', fn($q) => $q->where('name', 'superadmin'))->get();
        foreach ($superAdmins as $admin) {
            $admin->notify(new CongeRequestNotification($conge, $salarie));
        }

        return response()->json(['success' => 'Votre demande de congé a été soumise avec succès.']);

    } catch (\Exception $e) {
        Log::error('Erreur dans la méthode store', [
            'error' => $e->getMessage(), 
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['message' => 'Erreur lors de la soumission : ' . $e->getMessage()], 500);
    }
}
    public function downloadPdf($id)
    {
        try {
            $conge = Conge::findOrFail($id);
            if (!$conge->pdf_path || !file_exists(public_path($conge->pdf_path))) {
                Log::error('Fichier PDF introuvable', ['conge_id' => $id, 'pdf_path' => $conge->pdf_path]);
                return redirect()->back()->with('error', 'Le fichier PDF n\'existe pas.');
            }
            return response()->download(public_path($conge->pdf_path), 'demande_conge_' . $id . '.pdf');
        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement du PDF pour congé ID ' . $id . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors du téléchargement du PDF: ' . $e->getMessage());
        }
    }
}
