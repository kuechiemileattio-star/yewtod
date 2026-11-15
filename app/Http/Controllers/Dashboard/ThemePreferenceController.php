<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Préférence de thème enregistrée sur le compte (cahier § 9.1). */
class ThemePreferenceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['theme' => ['required', Rule::in(Theme::CHOICES)]]);
        /** @var User $user */
        $user = $request->user();
        $user->fill(['theme_preference' => $data['theme']])->save();

        return response()->json(['theme' => $user->theme_preference]);
    }
}
