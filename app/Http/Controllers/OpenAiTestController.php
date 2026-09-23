<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAiTestController extends Controller
{
    public function index()
    {
        return view('test.openai-import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480', // 20Mo max
            'annee' => 'required|integer',
            'mois' => 'required|integer|min:1|max:12',
            'type_image' => 'required|in:staff_work_sheet,other_type1,other_type2,other_type3',
        ]);

        $file = $request->file('file');
        $annee = $request->annee;
        $mois = str_pad($request->mois, 2, '0', STR_PAD_LEFT);
        $typeImage = $request->type_image;

        // Chemin temporaire valide fourni par Laravel
        $tempPath = $file->path(); // Toujours valide tant que dans la requête

        // Si c'est un PDF → on garde le PDF (gpt-4o gère très bien les PDF directement depuis 2025 !)
        // Plus besoin de conversion Imagick

        // Lecture directe du fichier en base64
        try {
            $base64 = base64_encode(file_get_contents($tempPath));
        } catch (\Exception $e) {
            return back()->with('error', 'Impossible de lire le fichier uploadé.');
        }

        $mime = $file->getMimeType();
        $dataUrl = "data:{$mime};base64,{$base64}";

        // Prompt strict
     $prompt = "Tu es un expert en extraction de fiches de pointage 'LOCAL STAFF WORK TIME SHEET' manuscrites.

Analyse cette photo et retourne UNIQUEMENT un JSON valide, sans aucun texte avant ou après, sans explication.

Format strict :
{
  \"cin\": \"le CIN ou ID du salarié en haut de la fiche (ex: F720182, FH63687, BK526571)\",
  \"annee\": \"{$annee}\",
  \"table\": [
    {
      \"no\": numéro de ligne (1, 2, 3, ...),
      \"date\": \"date exactement comme écrite (ex: 9月1日, 9月7日)\",
      \"normal_hours\": \"heures normales : 
        - 8 si marque manuscrite (croix, trait, signature, etc.) dans la colonne NORMAL HOURS
        - 4 si demi-journée visible
        - le nombre écrit si présent
        - 0 seulement si la case est complètement vide\",
      \"supp_hours\": \"le nombre dans SUPP HOURS ou 0 si vide\",
      \"type_heure_supp\": \"Matin ou Nuit si indiqué, sinon vide\",
      \"localisation\": null,
      \"id_projet\": null
    }
  ]
}

Règles importantes :
- Même si c'est marqué SUNDAY, regarde s'il y a une marque dans NORMAL HOURS → si oui, normal_hours = 8 (ou le nombre)
- Ne force jamais normal_hours = 0 sur dimanche si il y a une signature ou marque
- Si la case NORMAL HOURS est vide → normal_hours = 0
- Si marque manuscrite → normal_hours = 8
- Ne devine rien, utilise seulement ce qui est visible sur l'image

Type de fiche : {$typeImage}.
Si une valeur est illisible ou absente → mets null ou chaîne vide.
Ne devine rien.";          

        try {
           $response = OpenAI::chat()->create([
    'model' => 'gpt-4o',
    'max_tokens' => 3000,
    'temperature' => 0.1,
    'messages' => [
        [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
            ],
        ],
    ],
]);

            $content = $response->choices[0]->message->content ?? '';

            Log::info('OpenAI Raw Response', ['response' => $content]);

            if (!preg_match('/\{.*\}/s', $content, $matches)) {
                return back()
                    ->with('error', 'OpenAI n\'a pas retourné de JSON valide.')
                    ->with('debug', $content);
            }

            $jsonString = $matches[0];
            $data = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()
                    ->with('error', 'JSON invalide reçu d\'OpenAI')
                    ->with('debug', $jsonString);
            }

            return back()
                ->with('success', 'Extraction réussie avec GPT-4o Vision !')
                ->with('data', $data)
                ->with('raw', $content);

        } catch (\Exception $e) {
            Log::error('OpenAI Exception', ['message' => $e->getMessage()]);
            return back()->with('error', 'Erreur OpenAI : ' . $e->getMessage());
        }
    }
}