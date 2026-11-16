<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Base;

/**
 * Protection CSRF de Laravel, sans le cookie XSRF-TOKEN (lisible par
 * JavaScript, utile seulement aux clients axios que le site n'utilise pas).
 * Le jeton reste vérifié sur tous les formulaires et appels Livewire.
 */
class PreventRequestForgery extends Base
{
    protected $addHttpCookie = false;
}
