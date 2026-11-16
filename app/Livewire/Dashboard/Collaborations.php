<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\Toasts;
use App\Models\Collaboration;
use App\Models\User;
use App\Services\CollaborationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL as SignedUrl;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Demandes de collaboration (cahier § 4.5) : 3 tuiles, filtre, tableau
 * (cartes sur mobile), fiche détaillée, changement de statut, pièces
 * jointes par lien signé de 10 minutes, export CSV sûr.
 */
#[Layout('layouts::dashboard')]
class Collaborations extends Component
{
    use Toasts, WithPagination;

    #[Url(as: 'statut')]
    public string $status = '';

    public ?int $openId = null;

    public function mount(): void
    {
        abort_unless($this->me()->hasPermission('manage_collaborations'), 403);
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function open(int $id): void
    {
        $this->openId = Collaboration::query()->whereKey($id)->value('id');
    }

    public function closeModal(): void
    {
        $this->openId = null;
    }

    public function changeStatus(int $id, string $status): void
    {
        $collab = Collaboration::query()->findOrFail($id);
        $this->attempt(fn () => app(CollaborationService::class)->setStatus($this->me(), $collab, $status), 'Statut mis à jour : '.(Collaboration::STATUSES[$status] ?? $status).'.');
    }

    public function delete(int $id): void
    {
        $collab = Collaboration::query()->with('files')->findOrFail($id);
        $this->openId = null;
        $this->attempt(fn () => app(CollaborationService::class)->delete($this->me(), $collab), 'Demande supprimée.');
    }

    public function render(): View
    {
        $filter = array_key_exists($this->status, Collaboration::STATUSES) ? $this->status : null;
        $opened = $this->openId ? Collaboration::query()->with('files')->find($this->openId) : null;

        return view('livewire.dashboard.collaborations', [
            'items' => Collaboration::query()->withCount('files')->when($filter, fn ($q) => $q->where('status', $filter))->latest()->paginate(20),
            'counts' => Collaboration::query()->selectRaw('status, COUNT(*) AS aggregate')->groupBy('status')->pluck('aggregate', 'status')->all(),
            'opened' => $opened,
            // Liens signés générés à l'affichage : valables 10 minutes (cahier § 4.5).
            'fileLinks' => $opened ? $opened->files->mapWithKeys(fn ($f) => [$f->id => SignedUrl::temporarySignedRoute('dashboard.collaborations.file', now()->addMinutes(10), ['file' => $f->id])])->all() : [],
            'filter' => $filter,
        ])->title('Collaborations');
    }

    public static function newCount(User $user): int
    {
        return $user->hasPermission('manage_collaborations') ? Collaboration::query()->where('status', 'new')->count() : 0;
    }

    private function me(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
