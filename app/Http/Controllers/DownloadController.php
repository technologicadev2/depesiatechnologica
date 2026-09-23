<?php

namespace App\Http\Controllers;

use App\Models\Projet;
use App\Models\Decompte;
use App\Models\DossiersPdf;
use App\Models\OrdreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DownloadController extends Controller
{
    public function downloadDecompteDossier($id)
    {
        try {
            // Find the project
            $projet = Projet::findOrFail($id);
            $projectNum = $projet->num_p ?: 'PROJ-' . $projet->id;
            $folderPath = "documents/{$projectNum}/decompte";

            // Get all decompte documents
            $decomptes = Decompte::where('projet_id', $id)
                ->whereNotNull('document_path')
                ->get();

            // Initialize file paths and missing files arrays
            $filePaths = [];
            $missingFiles = [];

            // Collect valid file paths
            foreach ($decomptes as $decompte) {
                $relativePath = $decompte->document_path;
                if (is_string($relativePath) && !empty($relativePath)) {
                    // Normalize path to remove potential 'public/storage/' prefix
                    $relativePath = str_replace('public/storage/', '', $relativePath);
                    $filePath = public_path('storage/' . $relativePath);

                    if (file_exists($filePath) && is_readable($filePath)) {
                        $filePaths[] = [
                            'path' => $filePath,
                            'name' => "decompte_{$decompte->id}.pdf"
                        ];
                        Log::info("Decompte file found for ID {$decompte->id}: {$filePath}");
                    } else {
                        $missingFiles[] = $relativePath;
                        Log::warning("Decompte file not found or not readable for ID {$decompte->id}: {$filePath}");
                    }
                } else {
                    Log::warning("Invalid document path for decompte ID {$decompte->id}: {$relativePath}");
                }
            }

            // Create ZIP file
            $zipFileName = storage_path("app/public/decompte_projet_{$id}_" . time() . ".zip");
            $zip = new ZipArchive();

            if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                Log::error("Failed to create decompte ZIP file: {$zipFileName}");
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création du fichier ZIP'
                ], 500);
            }

            // If no valid files, add a README
            if (empty($filePaths)) {
                $zip->addFromString('README.txt', 'Aucun document de décompte valide trouvé pour ce projet.');
                Log::info("No valid decompte files found, added README.txt to ZIP for project ID: {$id}");

                if (!empty($missingFiles)) {
                    $zip->addFromString('missing_files.txt', "Fichiers manquants :\n" . implode("\n", $missingFiles));
                    Log::info("Added missing_files.txt with missing paths: " . implode(", ", $missingFiles));
                }
            } else {
                // Add valid files to ZIP
                foreach ($filePaths as $file) {
                    if ($zip->addFile($file['path'], $file['name'])) {
                        Log::info("Added to decompte ZIP: {$file['name']}");
                    } else {
                        Log::warning("Failed to add decompte file to ZIP: {$file['name']}");
                    }
                }
            }

            $zip->close();

            // Verify ZIP file exists
            if (!file_exists($zipFileName)) {
                Log::error("Decompte ZIP file was not created: {$zipFileName}");
                return response()->json([
                    'success' => false,
                    'message' => 'Échec de la création du fichier ZIP'
                ], 500);
            }

            // Return the ZIP file
            return response()->download($zipFileName, "decompte_projet_{$id}.zip", [
                'Content-Type' => 'application/zip',
                'Content-Length' => filesize($zipFileName)
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error("Error in downloadDecompteDossier for project ID: {$id}", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadSingleDocument($projet_id, $document_id)
    {
        try {
            $decompte = Decompte::where('projet_id', $projet_id)
                ->where('id', $document_id)
                ->firstOrFail();

            if (!$decompte->document_path) {
                Log::error("No document path for decompte ID: {$document_id}");
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun document trouvé'
                ], 404);
            }

            $filePath = public_path('storage/' . $decompte->document_path);
            if (!file_exists($filePath) || !is_readable($filePath)) {
                Log::error("Document file not found or not readable: {$filePath}");
                return response()->json([
                    'success' => false,
                    'message' => 'Le fichier n\'existe pas ou n\'est pas accessible'
                ], 404);
            }

            return response()->download($filePath, basename($filePath), [
                'Content-Type' => mime_content_type($filePath),
                'Content-Length' => filesize($filePath)
            ]);
        } catch (\Exception $e) {
            Log::error("Error in downloadSingleDocument for decompte ID: {$document_id}", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage()
            ], 500);
        }
    }
    public function downloadOrdreServiceDossier($id)
    {
        try {
            // Find the project
            $projet = Projet::findOrFail($id);
            $projectNum = $projet->num_p ?: 'PROJ-' . $projet->id;

            // Get all ordre de service records
            $ordres = OrdreService::where('projet_id', $id)
                ->whereNotNull('document_path')
                ->get();

            // Initialize file paths array
            $filePaths = [];
            $missingFiles = [];

            // Collect valid file paths
            foreach ($ordres as $ordre) {
                $relativePath = $ordre->document_path;
                if (is_string($relativePath) && !empty($relativePath)) {
                    // Normalize path to remove potential 'public/storage/' prefix
                    $relativePath = str_replace('public/storage/', '', $relativePath);
                    $filePath = public_path('storage/' . $relativePath);

                    if (file_exists($filePath) && is_readable($filePath)) {
                        $filePaths[] = [
                            'path' => $filePath,
                            'name' => "ordre_service_{$ordre->type}_{$ordre->id}.pdf"
                        ];
                        Log::info("File found for ordre service ID {$ordre->id}: {$filePath}");
                    } else {
                        $missingFiles[] = $relativePath;
                        Log::warning("File not found or not readable for ordre service ID {$ordre->id}: {$filePath}");
                    }
                } else {
                    Log::warning("Invalid document path for ordre service ID {$ordre->id}: {$relativePath}");
                }
            }

            // Create ZIP file
            $zipFileName = storage_path("app/public/ordre_service_projet_{$id}_" . time() . ".zip");
            $zip = new ZipArchive();

            if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                Log::error("Failed to create ZIP file: {$zipFileName}");
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création du fichier ZIP'
                ], 500);
            }

            // If no valid files, add a README
            if (empty($filePaths)) {
                $zip->addFromString('README.txt', 'Aucun document d\'ordre de service valide trouvé pour ce projet.');
                Log::info("No valid files found, added README.txt to ZIP for project ID: {$id}");

                if (!empty($missingFiles)) {
                    $zip->addFromString('missing_files.txt', "Fichiers manquants :\n" . implode("\n", $missingFiles));
                }
            } else {
                // Add valid files to ZIP
                foreach ($filePaths as $file) {
                    if ($zip->addFile($file['path'], $file['name'])) {
                        Log::info("Added to ZIP: {$file['name']}");
                    } else {
                        Log::warning("Failed to add file to ZIP: {$file['name']}");
                    }
                }
            }

            $zip->close();

            // Verify ZIP file exists
            if (!file_exists($zipFileName)) {
                Log::error("ZIP file was not created: {$zipFileName}");
                return responseGrossError();
            }

            // Return the ZIP file
            return response()->download($zipFileName, "ordre_service_projet_{$id}.zip", [
                'Content-Type' => 'application/zip',
                'Content-Length' => filesize($zipFileName)
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error("Error in downloadOrdreServiceDossier for project ID: {$id}", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage()
            ], 500);
        }
    }
}
