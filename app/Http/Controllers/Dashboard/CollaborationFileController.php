<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CollaborationFile;
use App\Models\User;
use App\Services\Audit;
use App\Services\CollaborationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Pièces jointes et export CSV des collaborations (cahier § 4.5, bug n° 11). */
class CollaborationFileController extends Controller
{
    /** Lien signé 10 min + membre connecté avec la permission (double verrou). */
    public function download(CollaborationFile $file): StreamedResponse
    {
        abort_unless(str_starts_with($file->path, 'collaborations/') && Storage::disk('local')->exists($file->path), 404);
        Audit::record('collaboration.file_downloaded', $file->collaboration);

        return Storage::disk('local')->download($file->path, $file->original_name, ['Cache-Control' => 'private, no-store', 'Content-Type' => 'application/octet-stream']);
    }

    public function export(Request $request, CollaborationService $service): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $status = is_string($request->query('statut')) ? $request->query('statut') : null;

        return response()->streamDownload($service->exportCsv($user, $status), 'collaborations-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
