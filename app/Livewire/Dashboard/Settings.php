<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\Toasts;
use App\Models\User;
use App\Services\SiteAdmin;
use App\Services\SiteSettings;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/** Paramètres du site (cahier § 4.9) : identité, SEO, logo, favicon, réflexion, réseaux sociaux. */
#[Layout('layouts::dashboard')]
class Settings extends Component
{
    use Toasts, WithFileUploads;

    /** @var array<string, string> */
    public array $form = [];

    /** @var list<array{platform: string, url: string}> */
    public array $socials = [];

    /** @var TemporaryUploadedFile|null */
    public $logo = null;

    /** @var TemporaryUploadedFile|null */
    public $favicon = null;

    public bool $removeLogo = false;

    public bool $removeFavicon = false;

    public function mount(SiteSettings $site): void
    {
        abort_unless($this->me()->hasPermission('manage_settings'), 403);
        $reflection = (array) $site->get('reflection');
        $this->form = [
            'site_name' => $site->siteName(),
            'seo_description' => (string) $site->get('seo_description'),
            'reflection_text' => (string) ($reflection['text'] ?? ''),
            'reflection_author' => (string) ($reflection['author'] ?? ''),
        ];
        $this->socials = $site->socialLinks()->map(fn ($l) => ['platform' => $l->platform, 'url' => $l->url])->values()->all();
    }

    public function save(): void
    {
        $ok = $this->attempt(fn () => app(SiteAdmin::class)->saveSettings($this->me(), $this->form, $this->logo, $this->favicon, $this->removeLogo, $this->removeFavicon), 'Paramètres enregistrés.');
        if ($ok) {
            $this->reset(['logo', 'favicon', 'removeLogo', 'removeFavicon']);
            $this->resetErrorBag();
        }
    }

    public function saveSocials(): void
    {
        $this->attempt(fn () => app(SiteAdmin::class)->saveSocialLinks($this->me(), $this->socials), 'Réseaux sociaux enregistrés.');
    }

    public function addSocial(): void
    {
        if (count($this->socials) < SiteAdmin::MAX_SOCIAL) {
            $this->socials[] = ['platform' => '', 'url' => ''];
        }
    }

    public function removeSocial(int $i): void
    {
        unset($this->socials[$i]);
        $this->socials = array_values($this->socials);
    }

    public function moveSocial(int $i, int $direction): void
    {
        $j = $i + ($direction < 0 ? -1 : 1);
        if (isset($this->socials[$i], $this->socials[$j])) {
            [$this->socials[$i], $this->socials[$j]] = [$this->socials[$j], $this->socials[$i]];
        }
    }

    public function render(SiteSettings $site): View
    {
        $reflection = (array) $site->get('reflection');

        return view('livewire.dashboard.settings', [
            'logoUrl' => $site->logoUrl(),
            'faviconUrl' => $site->faviconUrl(),
            'reflectionDate' => $reflection['date'] ?? null,
        ])->title('Paramètres');
    }

    private function me(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
