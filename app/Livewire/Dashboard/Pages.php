<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\Toasts;
use App\Models\User;
use App\Services\SiteAdmin;
use App\Services\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/** Pages éditables (cahier § 4.8, § 10 P2, bug n° 7) : Meet, Accueil, pied de page, FAQ. */
#[Layout('layouts::dashboard')]
class Pages extends Component
{
    use Toasts, WithFileUploads;

    public const TABS = ['meet' => 'Meet Yewtod', 'home' => 'Accueil', 'footer' => 'Pied de page'];

    #[Url(as: 'onglet')]
    public string $tab = 'meet';

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var TemporaryUploadedFile|null */
    public $portrait = null;

    public bool $removePortrait = false;

    public function mount(): void
    {
        abort_unless($this->me()->hasPermission('manage_pages'), 403);
        $this->tab = array_key_exists($this->tab, self::TABS) ? $this->tab : 'meet';
        $this->load();
    }

    public function updatedTab(): void
    {
        $this->tab = array_key_exists($this->tab, self::TABS) ? $this->tab : 'meet';
        $this->load();
    }

    public function addFaq(): void
    {
        $this->form['faq'][] = ['q' => '', 'a' => ''];
    }

    public function removeFaq(int $i): void
    {
        unset($this->form['faq'][$i]);
        $this->form['faq'] = array_values($this->form['faq']);
    }

    public function save(): void
    {
        $ok = $this->attempt(fn () => app(SiteAdmin::class)->savePage($this->me(), $this->tab, $this->form, $this->portrait, $this->removePortrait), 'Page « '.self::TABS[$this->tab].' » enregistrée.');
        if ($ok) {
            $this->reset(['portrait', 'removePortrait']);
            $this->resetErrorBag();
            $this->load();
        }
    }

    public function render(): View
    {
        $portrait = $this->tab === 'meet' && ! empty($this->form['portrait_path']) ? Storage::disk('public')->url((string) $this->form['portrait_path']) : null;

        return view('livewire.dashboard.pages', ['portraitUrl' => $portrait, 'publicUrl' => ['meet' => url('/meet'), 'home' => url('/'), 'footer' => url('/')][$this->tab]])->title('Pages');
    }

    private function load(): void
    {
        $content = app(SiteSettings::class)->page($this->tab);
        if ($this->tab === 'meet') {
            $content['expertise'] = implode("\n", (array) ($content['expertise'] ?? []));
        }
        $this->form = $content;
    }

    private function me(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
