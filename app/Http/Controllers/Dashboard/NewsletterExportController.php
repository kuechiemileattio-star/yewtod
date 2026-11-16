<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Services\Audit;
use App\Support\SafeCsv;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Export CSV sûr des abonnés (cahier § 13.1.2, bug n° 11). */
class NewsletterExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $status = is_string($request->query('statut')) && array_key_exists($request->query('statut'), NewsletterSubscriber::STATUSES) ? $request->query('statut') : null;
        Audit::record('newsletter.exported', null, ['status' => $status]);

        return response()->streamDownload(function () use ($status) {
            echo SafeCsv::BOM.SafeCsv::row(['Email', 'Statut', 'Inscription', 'Confirmation', 'Désinscription']);
            $tz = config('app.display_timezone');
            NewsletterSubscriber::query()->when($status, fn ($q) => $q->where('status', $status))->orderBy('email')->chunk(500, function ($rows) use ($tz) {
                foreach ($rows as $s) {
                    echo SafeCsv::row([$s->email, $s->statusLabel(), $s->created_at->setTimezone($tz)->format('d/m/Y'),
                        $s->confirmed_at?->setTimezone($tz)->format('d/m/Y'), $s->unsubscribed_at?->setTimezone($tz)->format('d/m/Y')]);
                }
            });
        }, 'newsletter-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
