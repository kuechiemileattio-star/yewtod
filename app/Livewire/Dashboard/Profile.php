<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\Toasts;
use App\Models\User;
use App\Notifications\SecurityNotification;
use App\Services\Audit;
use App\Services\ImageProcessor;
use App\Services\PasswordPolicy;
use App\Services\SessionManager;
use App\Support\Theme;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * « Mon profil » (cahier § 4.10, § 8.3) : profil, mot de passe, sessions,
 * sécurité, apparence. L'email, le rôle et le statut sont affichés en
 * lecture seule : ils ne sont pas modifiables ici (R1, R3).
 */
#[Layout('layouts::dashboard', ['title' => 'Mon profil'])]
class Profile extends Component
{
    use Toasts, WithFileUploads;

    #[Url(as: 'onglet')]
    public string $tab = 'profil';

    public string $name = '';

    public string $bio = '';

    /** @var TemporaryUploadedFile|null */
    public $avatar = null;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $sessionPassword = '';

    public function mount(): void
    {
        $this->name = $this->me()->name;
        $this->bio = (string) $this->me()->bio;
    }

    public function saveProfile(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->me()->fill(['name' => $data['name'], 'bio' => $data['bio'] ?: null])->save();
        $this->toast('Profil enregistré.');
    }

    /** Envoi immédiat de la photo (cahier § 4.10) : 2 Mo max, type vérifié sur le contenu. */
    public function updatedAvatar(): void
    {
        $this->validate(['avatar' => ['required', 'file', 'max:2048', 'mimetypes:'.implode(',', ImageProcessor::MIMES)]], [], ['avatar' => 'photo']);

        $images = app(ImageProcessor::class);
        try {
            $stored = $images->store($this->avatar, 'avatars', [256]);
        } catch (ValidationException $e) {
            $this->addError('avatar', collect($e->errors())->flatten()->first());

            return;
        }
        $user = $this->me();
        $images->delete($user->avatar_path);
        $user->forceFill(['avatar_path' => $stored['path']])->save();
        $this->avatar = null;
        $this->toast('Photo mise à jour.');
    }

    public function changePassword(SessionManager $sessions): void
    {
        $user = $this->me();
        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => PasswordPolicy::rules($user),
        ]);

        if (! Hash::check($this->current_password, $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        PasswordPolicy::apply($user, $this->password);
        // Changer son mot de passe déconnecte les autres sessions (cahier § 8.3).
        $sessions->destroyOthers($user, session()->getId());
        Auth::guard('web')->setUser($user->fresh() ?? $user);
        session()->put('password_hash_web', $user->fresh()?->getAuthPassword());
        session()->put('auth.password_confirmed_at', time());

        Audit::record('auth.password_changed', $user);
        $user->notify(new SecurityNotification(SecurityNotification::PASSWORD_CHANGED));
        $this->reset('current_password', 'password', 'password_confirmation');
        $this->toast('Mot de passe modifié. Les autres appareils ont été déconnectés.');
    }

    public function logoutOtherDevices(SessionManager $sessions): void
    {
        $this->validate(['sessionPassword' => ['required', 'string']], [], ['sessionPassword' => 'mot de passe']);
        if (! Hash::check($this->sessionPassword, $this->me()->password)) {
            throw ValidationException::withMessages(['sessionPassword' => 'Le mot de passe est incorrect.']);
        }
        $count = $sessions->destroyOthers($this->me(), session()->getId());
        Audit::record('auth.other_sessions_closed', $this->me(), ['count' => $count]);
        $this->reset('sessionPassword');
        $this->toast("{$count} autre(s) session(s) déconnectée(s).");
    }

    public function setTheme(string $theme): void
    {
        validator(['theme' => $theme], ['theme' => [Rule::in(Theme::CHOICES)]])->validate();
        $this->me()->fill(['theme_preference' => $theme])->save();
        $this->redirectRoute('dashboard.profile', ['onglet' => 'apparence']);
    }

    public function render(SessionManager $sessions): View
    {
        return view('livewire.dashboard.profile', [
            'user' => $this->me(),
            'sessions' => $this->tab === 'sessions' ? $sessions->forUser($this->me(), session()->getId()) : collect(),
        ]);
    }

    private function me(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
