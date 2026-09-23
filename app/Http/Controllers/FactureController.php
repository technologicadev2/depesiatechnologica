<?php

namespace App\Http\Controllers;

use App\Models\Entite;
use App\Models\FactureAchat;
use App\Models\FactureVente;
use App\Models\CompanySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\FacturesImport;
use Illuminate\Support\Facades\Log;
use App\Models\Client;
use App\Models\Releve;
use OpenAI\Laravel\Facades\OpenAI;
 use Illuminate\Support\Carbon;

class FactureController extends Controller
{
// Dans FactureController::index()
public function index()
{
    $facturesAchat = FactureAchat::orderBy('created_at', 'desc')->get();
    $facturesVente = FactureVente::orderBy('created_at', 'desc')->get();
    $releves         = Releve::orderBy('created_at', 'desc')->get();
    $entites = Entite::all();
    $clients  = Client::all();
    $companySettings = CompanySettings::first();

  
    $defaultType = request()->get('tab', 'achat');
    return view('factures.index', compact(
        'facturesAchat',
        'facturesVente',
        'companySettings',
        'entites',
        'clients',
        'releves',          
        'defaultType'         
    ));
}
public function show($id, Request $request)
{
    try {
        $type = $request->query('type');
        $model = $type === 'achat' ? FactureAchat::class : FactureVente::class;

        $facture = $model::findOrFail($id);

        return response()->json([
            'success' => true,
            'facture' => $facture,
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la récupération de la facture: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Facture non trouvée ou erreur: ' . $e->getMessage(),
        ], 404);
    }
}

public function scanReleve(Request $request)
{
    try {
        $request->validate([
            'files'   => 'required|array|min:1|max:10',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
        ]);

        $allDebit  = [];
        $allCredit = [];

        $prompt = <<<PROMPT
Tu es un expert en analyse de relevés bancaires marocains, notamment Attijariwafa bank.

Analyse ce relevé bancaire et retourne UNIQUEMENT un objet JSON valide, sans texte avant ou après, sans balises markdown.

STRUCTURE DU TABLEAU :
- Colonne "CODE" = code opération
- Colonne "DATE" = date partielle jour+mois (ex: "04 01" = 4 janvier)
- Colonne "LIBELLE" = description de l'opération
- Colonne "VALEUR" = date de valeur COMPLÈTE au format "DD MM YYYY" → utilise cette colonne en priorité
- Colonne "DEBIT" = montant débit (sortie d'argent)
- Colonne "CAPITAUX" = montant crédit (entrée d'argent)
- Colonne "CREDIT" = aussi des crédits

RÈGLES DATE :
- Utilise la colonne VALEUR en priorité (format "03 01 2023" → "2023-01-03")
- Si VALEUR absent, combine DATE + année du relevé (dans "SOLDE DEPART AU DD MM YYYY")

RÈGLES MONTANTS :
- "25 500,00" → 25500.00 (supprimer espaces, virgule → point)
- Nombre décimal pur, sans symbole

CLASSIFICATION :
- Valeur dans DEBIT → tableau "debit"
- Valeur dans CAPITAUX ou CREDIT → tableau "credit"
- IGNORER : SOLDE DEPART, TOTAL MOUVEMENTS, SOLDE FINAL

Format JSON :
{
  "debit": [{"date":"YYYY-MM-DD","libelle":"...","montant":0.00,"reference":"CODE"}],
  "credit": [{"date":"YYYY-MM-DD","libelle":"...","montant":0.00,"reference":"CODE"}]
}

Retourne UNIQUEMENT le JSON.
PROMPT;

        $pageIndex = 0;

        foreach ($request->file('files') as $fileIndex => $file) {
            $mime = $file->getMimeType();
            $ext  = strtolower($file->getClientOriginalExtension());

            // ── CAS PDF : envoyer directement à l'API OpenAI Files ──
            if ($ext === 'pdf' || $mime === 'application/pdf') {
                $apiKey = config('openai.api_key');

                // Copier avec extension .pdf
                $tempPdfPath = sys_get_temp_dir() . '/' . uniqid('releve_') . '.pdf';
                copy($file->path(), $tempPdfPath);

                // Étape 1 : Upload du PDF
                $ch = curl_init('https://api.openai.com/v1/files');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey],
                    CURLOPT_POSTFIELDS     => [
                        'purpose' => 'assistants',
                        'file'    => new \CURLFile($tempPdfPath, 'application/pdf', 'releve.pdf'),
                    ],
                ]);
                $uploadResult = json_decode(curl_exec($ch), true);
                curl_close($ch);

                if (file_exists($tempPdfPath)) unlink($tempPdfPath);

                $fileId = $uploadResult['id'] ?? null;

                if (!$fileId) {
                    Log::error("scanReleve: upload PDF échoué", ['response' => $uploadResult]);
                    $pageIndex++;
                    continue;
                }

                // Étape 2 : Chat avec le PDF
                $ch = curl_init('https://api.openai.com/v1/chat/completions');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_HTTPHEADER     => [
                        'Authorization: Bearer ' . $apiKey,
                        'Content-Type: application/json',
                    ],
                    CURLOPT_POSTFIELDS => json_encode([
                        'model'      => 'gpt-4o',
                        'max_tokens' => 4000,
                        'messages'   => [[
                            'role'    => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => $prompt],
                                ['type' => 'file', 'file' => ['file_id' => $fileId]],
                            ],
                        ]],
                    ]),
                ]);
                $chatResult = json_decode(curl_exec($ch), true);
                curl_close($ch);

                // Supprimer le fichier sur OpenAI
                $ch = curl_init('https://api.openai.com/v1/files/' . $fileId);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST  => 'DELETE',
                    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey],
                ]);
                curl_exec($ch);
                curl_close($ch);

                $content = $chatResult['choices'][0]['message']['content'] ?? '';
                Log::info("scanReleve PDF page #{$pageIndex}", ['content' => $content]);

                // Nettoyer markdown
                $content = preg_replace('/^```json\s*/i', '', trim($content));
                $content = preg_replace('/^```\s*/i',     '', trim($content));
                $content = preg_replace('/```\s*$/',      '', trim($content));

                if (!preg_match('/\{.*\}/s', $content, $matches)) {
                    Log::warning("scanReleve PDF #{$pageIndex}: pas de JSON valide");
                    $pageIndex++;
                    continue;
                }

                $data = json_decode($matches[0], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::warning("scanReleve PDF #{$pageIndex}: JSON invalide");
                    $pageIndex++;
                    continue;
                }

                // Normaliser et ajouter
                foreach (['debit', 'credit'] as $type) {
                    if (!empty($data[$type]) && is_array($data[$type])) {
                        foreach ($data[$type] as $ligne) {
                            if (isset($ligne['montant'])) {
                                $val   = preg_replace('/[^\d,.]/', '', (string) $ligne['montant']);
                                $val   = str_replace(',', '.', $val);
                                $parts = explode('.', $val);
                                if (count($parts) > 2) {
                                    $val = implode('', array_slice($parts, 0, -1)) . '.' . end($parts);
                                }
                                $ligne['montant'] = is_numeric($val) ? (float) $val : null;
                            }
                            $ligne['_page'] = $pageIndex + 1;
                            if ($type === 'debit') $allDebit[] = $ligne;
                            else $allCredit[] = $ligne;
                        }
                    }
                }

                $pageIndex++;

            } else {
                // ── CAS IMAGE : traitement existant ──
                $pageData = $this->analyzeImageWithGPT($file->path(), $prompt, $pageIndex);
                foreach ($pageData['debit']  as $ligne) $allDebit[]  = $ligne;
                foreach ($pageData['credit'] as $ligne) $allCredit[] = $ligne;
                $pageIndex++;
            }
        }

        return response()->json([
            'success'       => true,
            'data'          => ['debit' => $allDebit, 'credit' => $allCredit],
            'pages_scanned' => $pageIndex,
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['success' => false, 'message' => 'Fichier invalide.', 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        Log::error('scanReleve exception: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Erreur lors du scan : ' . $e->getMessage()], 500);
    }
}

private function analyzeImageWithGPT(string $imagePath, string $prompt, int $pageIndex): array
{
    $result = ['debit' => [], 'credit' => []];

    try {
        $mime = mime_content_type($imagePath);
        $ext  = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        // ── CAS PDF : utiliser l'API Files OpenAI ──
        if ($ext === 'pdf' || $mime === 'application/pdf') {

            $apiKey = config('openai.api_key');

            // Copier avec extension .pdf correcte
            $tempPdfPath = sys_get_temp_dir() . '/' . uniqid('releve_') . '.pdf';
            copy($imagePath, $tempPdfPath);

            // Étape 1 : Upload
            $ch = curl_init('https://api.openai.com/v1/files');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey],
                CURLOPT_POSTFIELDS     => [
                    'purpose' => 'assistants',
                    'file'    => new \CURLFile($tempPdfPath, 'application/pdf', 'releve.pdf'),
                ],
            ]);

            $uploadResult = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (file_exists($tempPdfPath)) unlink($tempPdfPath);

            $fileId = $uploadResult['id'] ?? null;

            if (!$fileId) {
                Log::error("analyzeImageWithGPT page #{$pageIndex} : upload PDF échoué", ['response' => $uploadResult]);
                return $result;
            }

            // Étape 2 : Chat
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'model'      => 'gpt-4o',
                    'max_tokens' => 4000,
                    'messages'   => [[
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            ['type' => 'file', 'file' => ['file_id' => $fileId]],
                        ],
                    ]],
                ]),
            ]);

            $chatResult = json_decode(curl_exec($ch), true);
            curl_close($ch);

            // Supprimer le fichier sur OpenAI
            $ch = curl_init('https://api.openai.com/v1/files/' . $fileId);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'DELETE',
                CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey],
            ]);
            curl_exec($ch);
            curl_close($ch);

            $content = $chatResult['choices'][0]['message']['content'] ?? '';

        } else {
            // ── CAS IMAGE : base64 classique ──
            $base64  = base64_encode(file_get_contents($imagePath));
            $dataUrl = "data:{$mime};base64,{$base64}";

            $response = OpenAI::chat()->create([
                'model'       => 'gpt-4o',
                'max_tokens'  => 4000,
                'temperature' => 0.1,
                'messages'    => [[
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'text',      'text'      => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                    ],
                ]],
            ]);

            $content = $response->choices[0]->message->content ?? '';
        }

        Log::info("analyzeImageWithGPT page #{$pageIndex}", ['content' => $content]);

        // Nettoyer markdown
        $content = preg_replace('/^```json\s*/i', '', trim($content));
        $content = preg_replace('/^```\s*/i',     '', trim($content));
        $content = preg_replace('/```\s*$/',      '', trim($content));

        if (!preg_match('/\{.*\}/s', $content, $matches)) {
            Log::warning("Page #{$pageIndex} : pas de JSON valide");
            return $result;
        }

        $data = json_decode($matches[0], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning("Page #{$pageIndex} : JSON invalide");
            return $result;
        }

        // Normaliser et ajouter le numéro de page
        foreach (['debit', 'credit'] as $type) {
            if (!empty($data[$type]) && is_array($data[$type])) {
                foreach ($data[$type] as $ligne) {
                    if (isset($ligne['montant'])) {
                        $val   = preg_replace('/[^\d,.]/', '', (string) $ligne['montant']);
                        $val   = str_replace(',', '.', $val);
                        $parts = explode('.', $val);
                        if (count($parts) > 2) {
                            $val = implode('', array_slice($parts, 0, -1)) . '.' . end($parts);
                        }
                        $ligne['montant'] = is_numeric($val) ? (float) $val : null;
                    }
                    $ligne['_page'] = $pageIndex + 1;
                    $result[$type][] = $ligne;
                }
            }
        }

    } catch (\Exception $e) {
        Log::error("analyzeImageWithGPT page #{$pageIndex} : " . $e->getMessage());
    }

    return $result;
}

public function getDeclaration(Request $request)
{
    try {
        $type = $request->query('type', 'achat');
        
        if ($type === 'achat') {
            $data = FactureAchat::where('relve_ex', 1)
                ->orderBy('date_paiement')
                ->get(['id', 'numero_facture', 'date_facture', 'montant_ttc', 'date_paiement']);
        } else {
            $data = FactureVente::where('relve_ex', 1)
                ->orderBy('date_encaissement')
                ->get(['id', 'numero_facture', 'date_facture', 'montant_ttc', 'date_encaissement']);
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    } catch (\Exception $e) {
        Log::error('getDeclaration error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

private function convertPdfToImages($file): array
{
    $pages = [];
    
    try {
        $tempDir = sys_get_temp_dir() . '/pdf_scan_' . uniqid();
        mkdir($tempDir, 0755, true);
        
        // Copier le PDF dans le dossier temp
        $pdfPath = $tempDir . '/document.pdf';
        
        if (is_string($file)) {
            // Si c'est déjà un chemin
            copy($file, $pdfPath);
        } else {
            // Si c'est un UploadedFile Laravel
            $file->move($tempDir, 'document.pdf');
        }
        
        // Méthode 1 : Utiliser Imagick (extension PHP)
        if (extension_loaded('imagick')) {
            $imagick = new \Imagick();
            $imagick->setResolution(200, 200);
            $imagick->readImage($pdfPath);
            $imagick->setImageFormat('png');
            
            $pageCount = $imagick->getNumberImages();
            
            for ($i = 0; $i < $pageCount; $i++) {
                $imagick->setIteratorIndex($i);
                $pageImagePath = $tempDir . '/page_' . $i . '.png';
                $imagick->writeImage($pageImagePath);
                $pages[] = $pageImagePath;
            }
            
            $imagick->destroy();
            
        } else {
            // Méthode 2 : Utiliser Ghostscript (commande système)
            $outputPattern = $tempDir . '/page_%d.png';
            $command = sprintf(
                'gs -dNOPAUSE -dBATCH -sDEVICE=png16m -r200 -sOutputFile=%s %s 2>&1',
                escapeshellarg($outputPattern),
                escapeshellarg($pdfPath)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                // Méthode 3 : pdftoppm (poppler-utils)
                $outputPrefix = $tempDir . '/page';
                $command = sprintf(
                    'pdftoppm -png -r 200 %s %s 2>&1',
                    escapeshellarg($pdfPath),
                    escapeshellarg($outputPrefix)
                );
                exec($command, $output, $returnCode);
            }
            
            // Récupérer les fichiers générés
            $generatedFiles = glob($tempDir . '/page*.png');
            sort($generatedFiles);
            $pages = $generatedFiles;
        }
        
        // Supprimer le PDF temporaire
        if (file_exists($pdfPath)) {
            unlink($pdfPath);
        }
        
        Log::info('convertPdfToImages: ' . count($pages) . ' page(s) générée(s)', [
            'tempDir' => $tempDir,
            'pages'   => $pages,
        ]);
        
    } catch (\Exception $e) {
        Log::error('convertPdfToImages error: ' . $e->getMessage());
    }
    
    return $pages;
}
// ===============================================
// GESTION DES RELEVÉS
// ===============================================
public function storeReleve(Request $request)
{
    try {
        $validated = $request->validate([
            'mois'   => 'required|string|in:Janvier,Février,Mars,Avril,Mai,Juin,Juillet,Août,Septembre,Octobre,Novembre,Décembre',
            'annee'  => 'required|integer|min:2000|max:2100',
            'file'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'debit'  => 'nullable|string',
            'credit' => 'nullable|string',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $file     = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('releves', $fileName, 'public');
        }

        $debitData  = $this->decodeJson($request->input('debit'));
        $creditData = $this->decodeJson($request->input('credit'));

        // Création sans les champs date et date_fin
        $releve = Releve::create([
            'mois'      => $validated['mois'],
            'annee'     => $validated['annee'],
            'file_path' => $filePath,
            'debit'     => $debitData,
            'credit'    => $creditData,
        ]);

        

       

        return response()->json([
            'success' => true,
            'message' => 'Relevé ajouté avec succès',
            'releve'  => $releve,
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['success' => false, 'message' => 'Erreur de validation', 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        Log::error('Erreur lors de l\'ajout du relevé: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Une erreur inattendue : ' . $e->getMessage()], 500);
    }
}

// Méthodes helper
private function decodeJson($input)
{
    if (empty($input)) return null;
    $decoded = json_decode($input, true);
    return (json_last_error() === JSON_ERROR_NONE && !empty($decoded)) ? $decoded : null;
}

private function getMonthNumber($moisFr)
{
    $mois = [
        'Janvier' => 1, 'Février' => 2, 'Mars' => 3, 'Avril' => 4,
        'Mai' => 5, 'Juin' => 6, 'Juillet' => 7, 'Août' => 8,
        'Septembre' => 9, 'Octobre' => 10, 'Novembre' => 11, 'Décembre' => 12,
    ];
    return $mois[$moisFr] ?? 1;
}

public function destroyReleve($id)
{
    try {
        $releve = Releve::findOrFail($id);

        if ($releve->file_path && Storage::disk('public')->exists($releve->file_path)) {
            Storage::disk('public')->delete($releve->file_path);
        }

        $releve->delete();

        return response()->json([
            'success' => true,
            'message' => 'Relevé supprimé avec succès',
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Relevé non trouvé',
        ], 404);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la suppression du relevé: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression',
        ], 500);
    }
}

public function downloadReleve($id)
{
    try {
        $releve = Releve::findOrFail($id);

        if (!$releve->file_path) {
            return response()->json(['message' => 'Aucun fichier associé à ce relevé'], 404);
        }

        $fullPath = storage_path('app/public/' . $releve->file_path);

        if (!file_exists($fullPath)) {
            $publicPath = public_path('storage/' . $releve->file_path);
            if (file_exists($publicPath)) {
                $fullPath = $publicPath;
            } else {
                return response()->json(['message' => 'Fichier non trouvé sur le serveur'], 404);
            }
        }

        $filename = basename($releve->file_path);
        $mimeType = Storage::disk('public')->mimeType($releve->file_path);

        return response()->download($fullPath, $filename, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['message' => 'Relevé non trouvé'], 404);
    } catch (\Exception $e) {
        Log::error('Erreur lors du téléchargement du relevé: ' . $e->getMessage());
        return response()->json(['message' => 'Erreur lors du téléchargement'], 500);
    }
}
public function updatePaiement(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'payee'         => 'required|boolean',
            'date_paiement' => 'nullable|date',
        ]);

        $facture = FactureAchat::findOrFail($id);

        $facture->update([
            'payee'         => $validated['payee'],
            'date_paiement' => $validated['payee'] 
                ? ($validated['date_paiement'] ?? now()->toDateString()) 
                : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $validated['payee'] ? 'Facture marquée comme payée.' : 'Facture marquée comme non payée.',
            'facture' => $facture,
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['success' => false, 'message' => 'Facture non trouvée'], 404);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['success' => false, 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        Log::error('Erreur paiement: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
    }
}

// FactureController.php
public function updateEncaissement(Request $request, $id)
{
    $validated = $request->validate([
        'payee'         => 'required|boolean',
        'date_paiement' => 'nullable|date',
    ]);

    $facture = FactureVente::findOrFail($id);

    $facture->update([
        'encaisser'         => $validated['payee'],
        'date_encaissement' => $validated['payee'] 
            ? ($validated['date_paiement'] ?? now()->toDateString()) 
            : null,
    ]);

    return response()->json([
        'success' => true,
        'message' => $validated['payee'] ? 'Facture marquée comme encaissée.' : 'Facture marquée comme non encaissée.',
    ]);
}

public function store(Request $request)
{
    try {
        $type = $request->input('type', 'achat');
        $tableName = $type === 'achat' ? 'factures_achat' : 'factures_vente';

        // Validation rules
        $rules = [
            'numero_facture' => ['required', 'string', 
            //"unique:{$tableName},numero_facture"
            ],
            'date_facture' => 'required|date',
            'raison_sociale' => 'required|string',
            'ice' => 'required|string',
            'taux_tva' => 'required|numeric|min:0|max:100',
            'montant_tva' => 'required|numeric|min:0',
            'montant_ttc' => 'required|numeric|min:0',
            'montant_htt' => 'required|numeric|min:0',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'type' => 'required|in:achat,vente',
            'manual_mode' => 'nullable|boolean'
        ];

        // Add objet validation for sales invoices only
        if ($type === 'vente') {
            $rules['objet'] = 'nullable|string|max:255';
        }

        // Validate input
        $validated = $request->validate($rules, [
            'numero_facture.unique' => 'Ce numéro de facture existe déjà.',
            'numero_facture.required' => 'Le numéro de facture est requis.',
            'date_facture.required' => 'La date de facture est requise.',
            'raison_sociale.required' => 'La raison sociale est requise.',
            'ice.required' => 'L\'ICE est requis.',
            'taux_tva.required' => 'Le taux TVA est requis.',
            'montant_tva.required' => 'Le montant TVA est requis.',
            'montant_ttc.required' => 'Le montant TTC est requis.',
            'montant_htt.required' => 'Le montant HT est requis.',
            'type.required' => 'Le type de facture est requis.',
            'file.mimes' => 'Le fichier doit être de type PDF, JPG, JPEG ou PNG.',
            'file.max' => 'Le fichier ne doit pas dépasser 5 Mo.',
            'manual_mode.boolean' => 'Le champ manual_mode doit être vrai ou faux.',
            'objet.max' => 'L\'objet ne peut pas dépasser 255 caractères.'
        ]);

        // Vérification ICE + numéro de facture pour les factures d'achat
    if ($type === 'achat') {
        $exists = FactureAchat::where('ice', $validated['ice'])
            ->where('numero_facture', $validated['numero_facture'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => [
                    'numero_facture' => [
                        'Ce fournisseur (ICE: ' . $validated['ice'] . ') possède déjà une facture avec le numéro "' . $validated['numero_facture'] . '".'
                    ]
                ]
            ], 422);
        }
    } else {
        // Pour les ventes, on garde l'unicité globale
        $exists = FactureVente::where('numero_facture', $validated['numero_facture'])->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => [
                    'numero_facture' => ['Ce numéro de facture existe déjà.']
                ]
            ], 422);
        }
    }

        // Create or retrieve entity
        $entite = Entite::firstOrCreate(
            ['ice' => $validated['ice']],
            [
                'raison_sociale' => $validated['raison_sociale'],
                'numero' => $request->input('numero'),
                'email' => $request->input('email')
            ]
        );

        // Handle file upload
        $filePath = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('factures', $fileName, 'public');
        }

        // Convert inputs to float
        $tauxTVA = floatval($validated['taux_tva']);
        $montantHT = floatval($validated['montant_htt']);
        $montantTVA = floatval($validated['montant_tva']);
        $montantTTC = floatval($validated['montant_ttc']);
        $manualMode = filter_var($request->input('manual_mode', false), FILTER_VALIDATE_BOOLEAN);

        // Validate calculations only in automatic mode
        if (!$manualMode) {
            $calculatedTVA = round(($montantHT * $tauxTVA) / 100, 2);
            $calculatedTTC = round($montantHT + $calculatedTVA, 2);

            if (abs($montantTVA - $calculatedTVA) > 0.1 || abs($montantTTC - $calculatedTTC) > 0.1) {
                return response()->json([
                    'message' => 'Erreur de calcul. Veuillez vérifier les montants.',
                    'debug' => [
                        'montant_ht' => $montantHT,
                        'taux_tva' => $tauxTVA,
                        'montant_tva_calcule' => $calculatedTVA,
                        'montant_tva_saisi' => $montantTVA,
                        'montant_ttc_calcule' => $calculatedTTC,
                        'montant_ttc_saisi' => $montantTTC
                    ]
                ], 422);
            }
        }

        // Create facture
        $model = $type === 'achat' ? FactureAchat::class : FactureVente::class;
        $factureData = [
            'numero_facture' => $validated['numero_facture'],
            'date_facture' => $validated['date_facture'],
            'raison_sociale' => $validated['raison_sociale'],
            'ice' => $validated['ice'],
            'taux_tva' => $tauxTVA,
            'montant_tva' => $montantTVA,
            'montant_ttc' => $montantTTC,
            'montant_ht' => $montantHT,
            'file_path' => $filePath,
            'encaisser' => 0,              
            'date_encaissement' => null,
            'nm_jours' => Carbon::parse($validated['date_facture'])->diffInDays(Carbon::now()),
        ];

        // Add objet for sales invoices
        if ($type === 'vente') {
            $factureData['objet'] = $validated['objet'];
        }

        $facture = $model::create($factureData);

        return response()->json([
            'success' => true,
            'message' => 'Facture ajoutée avec succès',
            'facture' => $facture
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation error in store facture: ' . json_encode($e->errors()));
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Erreur lors de l\'ajout de la facture: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Une erreur inattendue s\'est produite: ' . $e->getMessage()
        ], 500);
    }
}
public function update(Request $request, $id)
    {
        try {
            Log::info('Update request received:', ['id' => $id, 'data' => $request->all()]);

            // Validation rules
            $type = $request->input('type', 'achat');
            $tableName = $type === 'achat' ? 'factures_achat' : 'factures_vente';
            $rules = [
                'numero_facture' => [
                    'required',
                    'string',
                   // Rule::unique($tableName, 'numero_facture')->ignore($id)
                ],
                'date_facture' => 'required|date',
                'raison_sociale' => 'required|string',
                'ice' => 'required|string',
                'taux_tva' => 'required|numeric|min:0',
                'montant_htt' => 'required|numeric|min:0',
                'montant_tva' => 'required|numeric|min:0',
                'montant_ttc' => 'required|numeric|min:0',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'type' => 'required|in:achat,vente',
                'manual_mode' => 'nullable|boolean'
            ];

            // Add objet validation for sales invoices
            if ($type === 'vente') {
                $rules['objet'] = 'nullable|string|max:255';
            }

            // Validate input
            $validated = $request->validate($rules, [
                'numero_facture.unique' => 'Ce numéro de facture existe déjà.',
                'numero_facture.required' => 'Le numéro de facture est requis.',
                'date_facture.required' => 'La date de facture est requise.',
                'raison_sociale.required' => 'La raison sociale est requise.',
                'ice.required' => 'L\'ICE est requis.',
                'taux_tva.required' => 'Le taux TVA est requis.',
                'montant_tva.required' => 'Le montant TVA est requis.',
                'montant_ttc.required' => 'Le montant TTC est requis.',
                'montant_htt.required' => 'Le montant HT est requis.',
                'type.required' => 'Le type de facture est requis.',
                'file.mimes' => 'Le fichier doit être de type PDF, JPG, JPEG ou PNG.',
                'file.max' => 'Le fichier ne doit pas dépasser 5 Mo.',
                'manual_mode.boolean' => 'Le champ manual_mode doit être vrai ou faux.',
                'objet.max' => 'L\'objet ne peut pas dépasser 255 caractères.'
            ]);

            $model = $type === 'achat' ? FactureAchat::class : FactureVente::class;
            
            // Find the invoice
            $facture = $model::findOrFail($id);
            
            // Handle file upload if present
            $filePath = $facture->file_path;
            if ($request->hasFile('file')) {
                // Delete old file if exists
                if ($facture->file_path && Storage::disk('public')->exists($facture->file_path)) {
                    Log::info('Deleting old file:', ['file_path' => $facture->file_path]);
                    Storage::disk('public')->delete($facture->file_path);
                }
                
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('factures', $fileName, 'public');
                Log::info('New file uploaded:', ['file_path' => $filePath]);
            }
            
            // Prepare update data
            $updateData = [
                'numero_facture' => $validated['numero_facture'],
                'date_facture' => $validated['date_facture'],
                'raison_sociale' => $validated['raison_sociale'],
                'ice' => $validated['ice'],
                'taux_tva' => floatval($validated['taux_tva']),
                'montant_tva' => floatval($validated['montant_tva']),
                'montant_ttc' => floatval($validated['montant_ttc']),
                'montant_ht' => floatval($validated['montant_htt']),
                'file_path' => $filePath
            ];

            // Include objet for sales invoices
            if ($type === 'vente') {
                $updateData['objet'] = $validated['objet'] ?? null;
            }

            // Validate calculations in automatic mode
            $manualMode = filter_var($request->input('manual_mode', false), FILTER_VALIDATE_BOOLEAN);
            if (!$manualMode) {
                $montantHT = floatval($validated['montant_htt']);
                $tauxTVA = floatval($validated['taux_tva']);
                $montantTVA = floatval($validated['montant_tva']);
                $montantTTC = floatval($validated['montant_ttc']);
                $calculatedTVA = round(($montantHT * $tauxTVA) / 100, 2);
                $calculatedTTC = round($montantHT + $calculatedTVA, 2);

                if (abs($montantTVA - $calculatedTVA) > 0.1 || abs($montantTTC - $calculatedTTC) > 0.1) {
                    Log::warning('Calculation mismatch in update:', [
                        'montant_ht' => $montantHT,
                        'taux_tva' => $tauxTVA,
                        'montant_tva_calcule' => $calculatedTVA,
                        'montant_tva_saisi' => $montantTVA,
                        'montant_ttc_calcule' => $calculatedTTC,
                        'montant_ttc_saisi' => $montantTTC
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur de calcul. Veuillez vérifier les montants.',
                        'debug' => [
                            'montant_ht' => $montantHT,
                            'taux_tva' => $tauxTVA,
                            'montant_tva_calcule' => $calculatedTVA,
                            'montant_tva_saisi' => $montantTVA,
                            'montant_ttc_calcule' => $calculatedTTC,
                            'montant_ttc_saisi' => $montantTTC
                        ]
                    ], 422);
                }
            }
            
            // Update the invoice
            $facture->update($updateData);
            Log::info('Invoice updated successfully:', ['id' => $id, 'data' => $updateData]);
            
            return response()->json([
                'success' => true,
                'message' => 'Facture modifiée avec succès'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Invoice not found:', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Facture non trouvée'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in update facture:', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error in update facture:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue s\'est produite: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id, Request $request)
    {
        $type = $request->input('type');
        $model = $type === 'achat' ? FactureAchat::class : FactureVente::class;

        $facture = $model::find($id);
        if (!$facture) {
            return response()->json(['message' => 'Facture introuvable'], 404);
        }

        if ($facture->file_path) {
            Storage::disk('public')->delete($facture->file_path);
        }

        $facture->delete();
        return response()->json(['message' => 'Facture supprimée avec succès']);
    }

public function storeEntity(Request $request)
    {
        $validated = $request->validate([
            'new_raison_sociale' => 'required|string',
            'new_ice' => 'required|string|unique:entites,ice',
            'new_numero' => 'nullable|string',
            'new_email' => 'nullable|email'
        ]);

        $entite = Entite::create([
            'raison_sociale' => $validated['new_raison_sociale'],
            'ice' => $validated['new_ice'],
            'numero' => $validated['new_numero'],
            'email' => $validated['new_email']
        ]);

        return response()->json(['message' => 'Entité ajoutée avec succès', 'raison_sociale' => $entite->raison_sociale, 'ice' => $entite->ice]);
    }
public function downloadFile($id, Request $request)
{
    try {
        $type = $request->input('type');
        
        if (!in_array($type, ['achat', 'vente'])) {
          
            return response()->json(['message' => 'Type de facture invalide'], 400);
        }

        $model = $type === 'achat' ? FactureAchat::class : FactureVente::class;
        $facture = $model::findOrFail($id);

        if (!$facture->file_path) {
      
            return response()->json(['message' => 'Aucun fichier associé à cette facture'], 404);
        }

        $relativePath = $facture->file_path; // e.g., factures/1751039543_Bulletin de paie.pdf
        $fullPath = storage_path('app/public/' . $relativePath);

      

        if (!file_exists($fullPath)) {
        
            // Fallback: Try public/storage path
            $publicPath = public_path('storage/' . $relativePath);
       
            if (file_exists($publicPath)) {
                $fullPath = $publicPath;
            } else {
                return response()->json(['message' => 'Fichier non trouvé sur le serveur'], 404);
            }
        }

        $filename = basename($relativePath);
        $mimeType = Storage::disk('public')->mimeType($relativePath);

    

        return response()->download($fullPath, $filename, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      
        return response()->json(['message' => 'Facture non trouvée'], 404);
    } catch (\Exception $e) {
        
        return response()->json(['message' => 'Erreur lors du téléchargement'], 500);
    }
}
public function generateVatReport(Request $request)
{
    try {
        $companySettings = CompanySettings::first();
        if (!$companySettings) {
            return response()->json([
                'success' => false,
                'message' => "Aucun paramètre d'entreprise trouvé. Veuillez configurer les paramètres."
            ], 404);
        }
 
        $tvaDeclaration = $companySettings->tva_declaration ?? 'mensuelle';
 
        // ── Période ──────────────────────────────────────────────────────────
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date',   now()->endOfMonth()->toDateString());
 
        if ($tvaDeclaration === 'trimestrielle') {
            $startDate = $request->input('start_date', now()->startOfQuarter()->toDateString());
            $endDate   = $request->input('end_date',   now()->endOfQuarter()->toDateString());
        }
 
        // ── Type de filtre (nouveau paramètre) ───────────────────────────────
        // Valeurs attendues : 'releve_existe' | 'releve_non_existe' | 'toutes'
        $filterType = $request->input('filter_type', 'toutes');
 
        // ── Construction des requêtes de base (filtre par date) ──────────────
        $queryAchat = FactureAchat::whereBetween('date_facture', [$startDate, $endDate]);
        $queryVente = FactureVente::whereBetween('date_facture', [$startDate, $endDate]);
 
        // ── Application du filtre métier ─────────────────────────────────────
        switch ($filterType) {
 
            case 'releve_existe':
                // Factures ACHAT : payées ET rapprochées avec le relevé
                $queryAchat->where('payee',     1)
                           ->where('releve_ex', 1);
 
                // Factures VENTE : encaissées ET rapprochées avec le relevé
                $queryVente->where('encaisser', 1)
                           ->where('relve_ex',  1);   // note : colonne 'relve_ex' (sans 'e')
                break;
 
            case 'releve_non_existe':
                // Factures ACHAT : non payées ET non rapprochées
                $queryAchat->where('payee',     0)
                           ->where('releve_ex', 0);
 
                // Factures VENTE : non encaissées ET non rapprochées
                $queryVente->where('encaisser', 0)
                           ->where('relve_ex',  0);   // note : colonne 'relve_ex' (sans 'e')
                break;
 
            case 'toutes':
            default:
                // Pas de filtre supplémentaire — uniquement le filtre par date
                break;
        }
 
        // ── Récupération des données ─────────────────────────────────────────
        $facturesAchat = $queryAchat->get();
        $facturesVente = $queryVente->get();
 
        // ── Calcul des totaux ────────────────────────────────────────────────
        $totalHtAchat  = $facturesAchat->sum('montant_ht');
        $totalTvaAchat = $facturesAchat->sum('montant_tva');
        $totalTtcAchat = $facturesAchat->sum('montant_ttc');
 
        $totalHtVente  = $facturesVente->sum('montant_ht');
        $totalTvaVente = $facturesVente->sum('montant_tva');
        $totalTtcVente = $facturesVente->sum('montant_ttc');
 
        $netTva = $totalTvaVente - $totalTvaAchat;
 
        return response()->json([
            'success'         => true,
            'tva_declaration' => $tvaDeclaration,
            'filter_type'     => $filterType,          // renvoyé pour info côté JS
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'achat' => [
                'total_ht'  => number_format($totalHtAchat,  2, '.', ''),
                'total_tva' => number_format($totalTvaAchat, 2, '.', ''),
                'total_ttc' => number_format($totalTtcAchat, 2, '.', ''),
                'count'     => $facturesAchat->count(),
            ],
            'vente' => [
                'total_ht'  => number_format($totalHtVente,  2, '.', ''),
                'total_tva' => number_format($totalTvaVente, 2, '.', ''),
                'total_ttc' => number_format($totalTtcVente, 2, '.', ''),
                'count'     => $facturesVente->count(),
            ],
            'net_tva' => number_format($netTva, 2, '.', ''),
        ]);
 
    } catch (\Exception $e) {
        Log::error('Erreur lors de la génération du rapport TVA: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Une erreur inattendue : ' . $e->getMessage(),
        ], 500);
    }
}

public function importExcel(Request $reques)
{
    try {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:5120',
            'type' => 'required|in:achat,vente',
        ]);

        $type = $request->input('type');
        Excel::import(new FacturesImport($type), $request->file('excel_file'));

        return response()->json([
            'success' => true,
            'message' => 'Factures importées avec succès',
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Erreur d\'importation Excel: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Une erreur s\'est produite: ' . $e->getMessage(),
        ], 500);
    }
}
public function downloadTemplate($type)
{
    try {
        // Validation du type
        if (!in_array($type, ['achat', 'vente'])) {
            return response()->json(['message' => 'Type de facture invalide'], 400);
        }

        // Création du fichier Excel avec les en-têtes appropriés
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Définition des en-têtes selon le type
        $headers = [
            'numero_facture',
            'date_facture', 
            'raison_sociale',
            'ice',
            'taux_tva',
            'montant_tva',
            'montant_ht',
            'montant_ttc'
        ];

        if ($type === 'vente') {
            $headers[] = 'objet';
        }

        // Ajout des en-têtes dans la première ligne
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '1', $header);
            $sheet->getStyle($column . '1')->getFont()->setBold(true);
            $sheet->getColumnDimension($column)->setAutoSize(true);
            $column++;
        }

        // Ajout d'une ligne d'exemple
        $exampleData = [
            'F001',
            '2024-01-15',
            'Entreprise Exemple',
            'ICE123456789',
            '20',
            '200.00',
            '1000.00',
            '1200.00'
        ];

        if ($type === 'vente') {
            $exampleData[] = 'Description de la vente';
        }

        $column = 'A';
        foreach ($exampleData as $data) {
            $sheet->setCellValue($column . '2', $data);
            $sheet->getStyle($column . '2')->getFont()->setItalic(true);
            $column++;
        }

        // Nom du fichier
        $fileName = 'template_' . $type . '.xlsx';

        // Création du writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Définition des headers pour le téléchargement
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ];

        // Création d'un fichier temporaire
        $temp_file = tempnam(sys_get_temp_dir(), 'excel_template_');
        $writer->save($temp_file);

        return response()->download($temp_file, $fileName, $headers)->deleteFileAfterSend(true);

    } catch (\Exception $e) {
        Log::error('Erreur lors de la génération du template Excel: ' . $e->getMessage());
        return response()->json(['message' => 'Erreur lors de la génération du template: ' . $e->getMessage()], 500);
    }
}
public function storeClient(Request $request)
{
    try {
        $validated = $request->validate([
            'new_nom_complet' => 'required|string|max:255',
            'new_ice' => 'required|string|unique:clients,ice',
            'new_telephone' => 'nullable|string|max:20',
            'new_email' => 'nullable|email|max:255',
            'new_adresse' => 'nullable|string|max:500',
            'new_ville' => 'nullable|string|max:100',
            'new_type_client' => 'nullable|string|max:50'
        ]);

        $client = Client::create([
            'nom_complet' => $validated['new_nom_complet'],
            'ice' => $validated['new_ice'],
            'telephone' => $validated['new_telephone'] ?? null,
            'email' => $validated['new_email'] ?? null,
            'adresse' => $validated['new_adresse'] ?? null,
            'ville' => $validated['new_ville'] ?? null,
            'type_client' => $validated['new_type_client'] ?? null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client ajouté avec succès',
            'nom_complet' => $client->nom_complet,
            'ice' => $client->ice
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Erreur lors de l\'ajout du client: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Une erreur inattendue s\'est produite: ' . $e->getMessage()
        ], 500);
    }
}

 public function scan(Request $request)
{
    try {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
        ]);

        $file     = $request->file('file');
        $mime     = $file->getMimeType();
        $ext      = strtolower($file->getClientOriginalExtension());
        $tempPath = $file->path();

        $prompt = <<<PROMPT
Tu es un expert en extraction de données de factures marocaines.
Analyse cette facture et retourne UNIQUEMENT un objet JSON valide, sans texte avant ou après, sans balises markdown, sans explication.

Format strict à respecter :
{
  "numero_facture": "numéro de la facture tel qu'il apparaît (ex: FN25/002362)",
  "date_facture": "date au format YYYY-MM-DD (ex: 2025-12-30)",
  "raison_sociale": "nom complet de l'émetteur de la facture (celui qui vend/émet, pas le destinataire)",
  "ice": "numéro ICE de l'émetteur de la facture (15 chiffres, ex: 001881524000080)",
  "montant_ht": "montant hors taxes en nombre décimal sans symbole monétaire (ex: 33875.01)",
  "taux_tva": "taux de TVA en pourcentage, nombre seul sans % (ex: 20)",
  "montant_tva": "montant de la TVA en nombre décimal sans symbole monétaire (ex: 6774.99)",
  "montant_ttc": "montant total TTC en nombre décimal sans symbole monétaire (ex: 40650.00)"
}

Règles importantes :
- Retourne UNIQUEMENT le JSON, rien d'autre.
- Les montants doivent être des nombres décimaux purs, sans "DH", "MAD", espaces ou virgules de milliers.
- Pour l'ICE : cherche le champ "ICE" dans le bas de la facture de l'émetteur. C'est un nombre de 15 chiffres.
- Pour la raison sociale : prends le nom du vendeur/émetteur (en haut de la facture), pas le nom du client destinataire.
- Pour le numéro de facture : cherche "Facture N°", "N° Facture", ou le titre principal de la facture.
- Pour la date : cherche "Date de la facture", "Date :", etc.
- Si une valeur est absente ou illisible, mets null.
- Ne devine rien, utilise uniquement ce qui est clairement visible sur le document.
PROMPT;

        // ── CAS PDF : utiliser l'API Files OpenAI ──
       if ($ext === 'pdf' || $mime === 'application/pdf') {

    $apiKey = config('openai.api_key');

    // Créer une copie temporaire avec la bonne extension .pdf
    $tempPdfPath = sys_get_temp_dir() . '/' . uniqid('facture_') . '.pdf';
    copy($tempPath, $tempPdfPath);

    // Étape 1 : Upload du fichier avec extension correcte
    $ch = curl_init('https://api.openai.com/v1/files');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => [
            'purpose' => 'assistants',
            'file'    => new \CURLFile($tempPdfPath, 'application/pdf', 'facture.pdf'),
        ],
    ]);

    $uploadResult = json_decode(curl_exec($ch), true);
    curl_close($ch);

    // Supprimer la copie temporaire
    if (file_exists($tempPdfPath)) {
        unlink($tempPdfPath);
    }

    $fileId = $uploadResult['id'] ?? null;

    if (!$fileId) {
        Log::error('OpenAI file upload failed', ['response' => $uploadResult]);
        return response()->json([
            'success' => false,
            'message' => 'Impossible d\'uploader le PDF : ' . ($uploadResult['error']['message'] ?? 'Erreur inconnue'),
        ], 500);
    }

    Log::info('PDF uploaded to OpenAI', ['file_id' => $fileId]);

    // Étape 2 : Chat avec le file_id
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model'      => 'gpt-4o',
            'max_tokens' => 1000,
            'messages'   => [[
                'role'    => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    ['type' => 'file', 'file' => ['file_id' => $fileId]],
                ],
            ]],
        ]),
    ]);

    $chatResult = json_decode(curl_exec($ch), true);
    curl_close($ch);

    Log::info('ScanFacture PDF chat result', ['result' => $chatResult]);

    // Supprimer le fichier uploadé sur OpenAI
    $ch = curl_init('https://api.openai.com/v1/files/' . $fileId);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'DELETE',
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey],
    ]);
    curl_exec($ch);
    curl_close($ch);

    $content = $chatResult['choices'][0]['message']['content'] ?? '';

    if (empty($content)) {
        $errorMsg = $chatResult['error']['message'] ?? 'Réponse vide de OpenAI';
        return response()->json([
            'success' => false,
            'message' => 'Erreur OpenAI : ' . $errorMsg,
            'debug'   => $chatResult,
        ], 422);
    }

    Log::info('ScanFacture PDF response', ['content' => $content]);

    // Nettoyer markdown
    $content = preg_replace('/^```json\s*/i', '', trim($content));
    $content = preg_replace('/^```\s*/i',     '', trim($content));
    $content = preg_replace('/```\s*$/',      '', trim($content));

    if (!preg_match('/\{.*\}/s', $content, $matches)) {
        return response()->json(['success' => false, 'message' => "Pas de JSON valide.", 'debug' => $content], 422);
    }

    $data = json_decode($matches[0], true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return response()->json(['success' => false, 'message' => 'JSON invalide.', 'debug' => $matches[0]], 422);
    }

    // Normaliser les montants
    $numericFields = ['montant_ht', 'taux_tva', 'montant_tva', 'montant_ttc'];
    foreach ($numericFields as $field) {
        if (!empty($data[$field])) {
            $val   = (string) $data[$field];
            $val   = preg_replace('/[^\d,.]/', '', $val);
            $val   = str_replace(',', '.', $val);
            $parts = explode('.', $val);
            if (count($parts) > 2) {
                $val = implode('', array_slice($parts, 0, -1)) . '.' . end($parts);
            }
            $data[$field] = is_numeric($val) ? (float) $val : null;
        }
    }

    // Nettoyer l'ICE
    if (!empty($data['ice'])) {
        $data['ice'] = preg_replace('/\D/', '', (string) $data['ice']);
    }

    return response()->json([
        'success' => true,
        'data'    => $data,
    ]);
}

// ── CAS IMAGE : base64 classique (code existant inchangé) ──
$base64  = base64_encode(file_get_contents($tempPath));
$dataUrl = "data:{$mime};base64,{$base64}";

$response = OpenAI::chat()->create([
    'model'       => 'gpt-4o',
    'max_tokens'  => 1000,
    'temperature' => 0.1,
    'messages'    => [[
        'role'    => 'user',
        'content' => [
            ['type' => 'text',      'text'      => $prompt],
            ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
        ],
    ]],
]);

$content = $response->choices[0]->message->content ?? '';
Log::info('ScanFacture OpenAI response', ['content' => $content]);

// Nettoyer les balises markdown
$content = preg_replace('/^```json\s*/i', '', trim($content));
$content = preg_replace('/^```\s*/i',     '', trim($content));
$content = preg_replace('/```\s*$/',      '', trim($content));

if (!preg_match('/\{.*\}/s', $content, $matches)) {
    return response()->json([
        'success' => false,
        'message' => "OpenAI n'a pas retourné de JSON valide.",
        'debug'   => $content,
    ], 422);
}

$data = json_decode($matches[0], true);

if (json_last_error() !== JSON_ERROR_NONE) {
    return response()->json([
        'success' => false,
        'message' => 'JSON invalide : ' . json_last_error_msg(),
        'debug'   => $matches[0],
    ], 422);
}

// Normaliser les montants
$numericFields = ['montant_ht', 'taux_tva', 'montant_tva', 'montant_ttc'];
foreach ($numericFields as $field) {
    if (!empty($data[$field])) {
        $val   = (string) $data[$field];
        $val   = preg_replace('/[^\d,.]/', '', $val);
        $val   = str_replace(',', '.', $val);
        $parts = explode('.', $val);
        if (count($parts) > 2) {
            $val = implode('', array_slice($parts, 0, -1)) . '.' . end($parts);
        }
        $data[$field] = is_numeric($val) ? (float) $val : null;
    }
}

// Nettoyer l'ICE
if (!empty($data['ice'])) {
    $data['ice'] = preg_replace('/\D/', '', (string) $data['ice']);
}

return response()->json([
    'success' => true,
    'data'    => $data,
]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Fichier invalide.',
            'errors'  => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('ScanFacture exception: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du scan : ' . $e->getMessage(),
        ], 500);
    }
}

private function extractFactureDataFromImage(string $imagePath, string $prompt): ?array
{
    try {
        $mime    = mime_content_type($imagePath);
        $base64  = base64_encode(file_get_contents($imagePath));
        $dataUrl = "data:{$mime};base64,{$base64}";

        $response = OpenAI::chat()->create([
            'model'       => 'gpt-4o',
            'max_tokens'  => 1000,
            'temperature' => 0.1,
            'messages'    => [[
                'role'    => 'user',
                'content' => [
                    ['type' => 'text',      'text'      => $prompt],
                    ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                ],
            ]],
        ]);

        $content = $response->choices[0]->message->content ?? '';
        Log::info('ScanFacture OpenAI response', ['content' => $content]);

        // Nettoyer les balises markdown
        $content = preg_replace('/^```json\s*/i', '', trim($content));
        $content = preg_replace('/^```\s*/i',     '', trim($content));
        $content = preg_replace('/```\s*$/',      '', trim($content));

        if (!preg_match('/\{.*\}/s', $content, $matches)) {
            return null;
        }

        $data = json_decode($matches[0], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        // Normaliser les montants
        $numericFields = ['montant_ht', 'taux_tva', 'montant_tva', 'montant_ttc'];
        foreach ($numericFields as $field) {
            if (!empty($data[$field])) {
                $val   = (string) $data[$field];
                $val   = preg_replace('/[^\d,.]/', '', $val);
                $val   = str_replace(',', '.', $val);
                $parts = explode('.', $val);
                if (count($parts) > 2) {
                    $val = implode('', array_slice($parts, 0, -1)) . '.' . end($parts);
                }
                $data[$field] = is_numeric($val) ? (float) $val : null;
            }
        }

        // Nettoyer l'ICE
        if (!empty($data['ice'])) {
            $data['ice'] = preg_replace('/\D/', '', (string) $data['ice']);
        }

        return $data;

    } catch (\Exception $e) {
        Log::error('extractFactureDataFromImage: ' . $e->getMessage());
        return null;
    }
}
}