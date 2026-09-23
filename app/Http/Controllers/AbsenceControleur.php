<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\CompanySettings;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AbsenceControleur extends Controller
{
    /**
     * Display the list of absences with related employee data.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $absences = Absence::with('salarie')->orderBy('created_at', direction: 'desc')->get();
        $companySettings = CompanySettings::first();
        return view('absences.absence', compact('absences', 'companySettings'));
    }

    /**
     * Retrieve a specific absence by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getAbsence($id): JsonResponse
    {
        try {
            $absence = Absence::findOrFail($id);

            return response()->json([
                'description' => $absence->description ?? '',
                'piece_jointe' => $absence->piece_jointe,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Absence non trouvée'], 404);
        }
    }

    /**
     * Update the justification details for a specific absence.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateJustification(Request $request, $id): JsonResponse
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'description' => 'nullable|string|max:1000',
                'piece_jointe' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            ]);

            // Find the absence
            $absence = Absence::findOrFail($id);

            // Prepare data for update
            $data = [
                'description' => $validated['description'] ?? '',
                'justification' => 1, // Set justification to 1
            ];

            // Handle file upload if present
            if ($request->hasFile('piece_jointe')) {
                $data['piece_jointe'] = $this->handleFileUpload($request->file('piece_jointe'), $absence->piece_jointe, $absence->salarie);
            }

            // Update the absence
            $absence->update($data);

            return response()->json(['message' => 'Justification mise à jour avec succès']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Absence non trouvée'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la justification pour absence ID ' . $id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Une erreur s\'est produite'], 500);
        }
    }

    /**
     * Handle the file upload for the justification attachment.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string|null $existingFilePath
     * @param \App\Models\Salarie $salarie
     * @return string
     */
    private function handleFileUpload($file, $existingFilePath = null, $salarie): string
    {
        try {
            // Générer le nom du dossier basé sur le nom et le matricule du salarié
            $folderName = Str::slug($salarie->nom . '_' . $salarie->n_matricule_entreprise, '_');
            $basePath = 'assets/storage/salaries/' . $folderName . '/absences';

            // Créer le dossier si nécessaire
            $fullDirectoryPath = public_path($basePath);
            if (!file_exists($fullDirectoryPath)) {
                mkdir($fullDirectoryPath, 0755, true);
                Log::info('Dossier absences créé', ['path' => $basePath, 'full_path' => $fullDirectoryPath]);
            }

            // Vérifier si le dossier existe après création
            if (!file_exists($fullDirectoryPath)) {
                throw new \Exception("Échec de la création du dossier : $basePath");
            }

            // Supprimer l'ancien fichier s'il existe
            if ($existingFilePath && File::exists(public_path($existingFilePath))) {
                File::delete(public_path($existingFilePath));
                Log::info('Ancien fichier supprimé', ['path' => $existingFilePath]);
            }

            // Générer un nom de fichier unique
            $filename = time() . '_' . $file->getClientOriginalName();
            $fullPath = $basePath . '/' . $filename;

            // Déplacer le fichier vers le dossier
            $file->move($fullDirectoryPath, $filename);

            // Vérifier si le fichier a été déplacé
            if (!file_exists(public_path($fullPath))) {
                throw new \Exception("Échec du déplacement du fichier : $fullPath");
            }

            Log::info('Pièce jointe enregistrée', ['path' => $fullPath]);

            // Retourner le chemin relatif
            return $fullPath;

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'upload de la pièce jointe pour absence', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

public function justify(Request $request, $id)
{
    $absence = Absence::find($id);

    if (!$absence) {
        Log::error('Absence not found', ['absence_id' => $id]);
        return response()->json(['message' => 'Absence non trouvée.'], 404);
    }

    if ($absence->date_fin === null || $absence->nbre_jours === null) {
        Log::error('Cannot justify absence: date_fin or nbre_jours is null', [
            'absence_id' => $id,
            'date_fin' => $absence->date_fin,
            'nbre_jours' => $absence->nbre_jours
        ]);
        return response()->json(['message' => 'La date de fin ou le nombre de jours est manquant.'], 400);
    }

    $absence->update([
        'justification' => 1,
    ]);

    Log::info('Absence justified successfully', ['absence_id' => $id]);
    return response()->json(['message' => 'Absence justifiée avec succès.']);
}
}