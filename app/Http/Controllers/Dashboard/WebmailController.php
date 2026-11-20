<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Audit;
use App\Support\Webmail;
use Illuminate\Http\RedirectResponse;

/**
 * Redirige vers le webmail de l'équipe (D72). Passer par une route du
 * dashboard plutôt qu'un lien direct garde le contrôle côté serveur :
 * permission `access_webmail`, 2FA du dashboard, trace dans le journal d'audit.
 * Le webmail reste protégé par les identifiants de chaque boîte.
 */
class WebmailController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $url = Webmail::url();
        abort_if($url === null, 404);

        Audit::record('webmail.opened');

        return redirect()->away($url);
    }
}
