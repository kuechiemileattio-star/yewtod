<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\Toasts;
use App\Models\Book;
use App\Models\Content;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * File de modération (cahier § 4.6) : avis de publications (manage_articles)
 * et/ou de livres (manage_books). Seul le statut change, jamais le texte.
 */
#[Layout('layouts::dashboard')]
class Moderation extends Component
{
    use Toasts, WithPagination;

    #[Url(as: 'statut')]
    public string $status = 'pending';

    public function mount(): void
    {
        abort_unless($this->me()->hasPermission('manage_articles') || $this->me()->hasPermission('manage_books'), 403);
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function moderate(int $id, string $status): void
    {
        $review = ReviewService::visibleTo($this->me())->findOrFail($id);
        $labels = ['approved' => 'Avis approuvé.', 'rejected' => 'Avis rejeté.', 'pending' => 'Avis remis en attente.'];
        $this->attempt(fn () => app(ReviewService::class)->moderate($this->me(), $review, $status), $labels[$status] ?? 'Avis mis à jour.');
    }

    public function approve(int $id): void
    {
        $this->moderate($id, 'approved');
    }

    public function reject(int $id): void
    {
        $this->moderate($id, 'rejected');
    }

    public function delete(int $id): void
    {
        $review = ReviewService::visibleTo($this->me())->findOrFail($id);
        $this->attempt(fn () => app(ReviewService::class)->delete($this->me(), $review), 'Avis supprimé.');
    }

    public function render(): View
    {
        $status = array_key_exists($this->status, Review::STATUSES) ? $this->status : 'pending';
        $base = ReviewService::visibleTo($this->me());

        return view('livewire.dashboard.moderation', [
            'reviews' => (clone $base)->where('status', $status)->with(['reviewable', 'moderator'])->latest()->paginate(20),
            'counts' => (clone $base)->selectRaw('status, COUNT(*) AS aggregate')->groupBy('status')->pluck('aggregate', 'status')->all(),
            'current' => $status,
        ])->title('Modération');
    }

    /** Nombre d'avis en attente visibles par un membre (pastille du menu). */
    public static function pendingFor(User $user): int
    {
        return ($user->hasPermission('manage_articles') || $user->hasPermission('manage_books'))
            ? ReviewService::visibleTo($user)->where('status', 'pending')->count() : 0;
    }

    /** Libellé du contenu concerné. */
    public static function targetLabel(Review $review): string
    {
        $target = $review->reviewable;

        return match (true) {
            $target instanceof Content => 'Article · '.$target->title,
            $target instanceof Book => 'Livre · '.$target->title,
            default => 'Contenu supprimé',
        };
    }

    private function me(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
