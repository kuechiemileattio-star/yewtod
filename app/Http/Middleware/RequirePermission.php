<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôle côté serveur de chaque module (cahier § 4) : `permission:manage_books`
 * ou `permission:manage_articles|manage_books` (au moins une).
 */
class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();
        $any = explode('|', $permissions);

        if (! $user instanceof User || ! collect($any)->contains(fn (string $p) => $user->hasPermission($p))) {
            abort(403, 'Vous n\'avez pas la permission d\'accéder à ce module.');
        }

        return $next($request);
    }
}
