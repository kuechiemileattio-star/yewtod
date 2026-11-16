<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\Toasts;
use App\Models\Media;
use App\Models\User;
use App\Services\MediaLibrary;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/** Médiathèque (cahier § 10 P2, `manage_media`). */
#[Layout('layouts::dashboard')]
class MediaManager extends Component
{
    use Toasts, WithFileUploads, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $kind = '';

    /** @var TemporaryUploadedFile|null */
    public $upload = null;

    public ?int $selectedId = null;

    public string $alt = '';

    public function mount(): void
    {
        abort_unless($this->me()->hasPermission('manage_media'), 403);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'kind'], true)) {
            $this->resetPage();
        }
    }

    public function updatedUpload(): void
    {
        if ($this->upload === null) {
            return;
        }
        $ok = $this->attempt(fn () => $this->selectedId = app(MediaLibrary::class)->upload($this->me(), $this->upload)->id, 'Fichier ajouté à la médiathèque.');
        $this->upload = null;
        if ($ok && $this->selectedId) {
            $this->select($this->selectedId);
        }
    }

    public function select(int $id): void
    {
        $media = Media::query()->findOrFail($id);
        $this->selectedId = $media->id;
        $this->alt = (string) $media->alt;
    }

    public function closeModal(): void
    {
        $this->selectedId = null;
    }

    public function saveAlt(): void
    {
        $media = Media::query()->findOrFail($this->selectedId);
        $this->attempt(fn () => app(MediaLibrary::class)->updateAlt($this->me(), $media, $this->alt), 'Texte alternatif enregistré.');
    }

    public function delete(int $id): void
    {
        $media = Media::query()->findOrFail($id);
        $this->selectedId = null;
        $this->attempt(fn () => app(MediaLibrary::class)->delete($this->me(), $media), 'Fichier supprimé.');
    }

    public function deleteOrphans(): void
    {
        $count = 0;
        $this->attempt(function () use (&$count) {
            $count = app(MediaLibrary::class)->deleteOrphans($this->me());
        }, '');
        $this->toast($count.' fichier(s) inutilisé(s) supprimé(s).');
    }

    public function render(): View
    {
        $term = trim(mb_substr($this->search, 0, 100));
        $like = '%'.addcslashes($term, '%_\\').'%';
        $selected = $this->selectedId ? Media::query()->with('uploader')->find($this->selectedId) : null;

        return view('livewire.dashboard.media', [
            'items' => Media::query()->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('original_name', 'like', $like)->orWhere('alt', 'like', $like)))
                ->when(in_array($this->kind, ['image', 'document'], true), fn ($q) => $q->where('kind', $this->kind))
                ->latest()->paginate(24),
            'selected' => $selected,
            'selectedUsed' => $selected ? MediaLibrary::isUsed($selected) : false,
        ])->title('Médiathèque');
    }

    private function me(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
