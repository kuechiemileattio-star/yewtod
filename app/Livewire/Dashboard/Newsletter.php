<?php

namespace App\Livewire\Dashboard;

use App\Jobs\SyncNewsletterWithBrevo;
use App\Livewire\Concerns\Toasts;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Services\NewsletterService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Abonnés à la newsletter (cahier § 13.1.2, `manage_newsletter`). */
#[Layout('layouts::dashboard')]
class Newsletter extends Component
{
    use Toasts, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'statut')]
    public string $status = '';

    public function mount(): void
    {
        abort_unless($this->me()->hasPermission('manage_newsletter'), 403);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function delete(int $id): void
    {
        $sub = NewsletterSubscriber::query()->findOrFail($id);
        $this->attempt(fn () => app(NewsletterService::class)->delete($this->me(), $sub), 'Abonné supprimé (droit à l\'effacement).');
    }

    public function render(): View
    {
        $term = trim(mb_substr($this->search, 0, 100));

        return view('livewire.dashboard.newsletter', [
            'items' => NewsletterSubscriber::query()
                ->when($term !== '', fn ($q) => $q->where('email', 'like', '%'.addcslashes(mb_strtolower($term), '%_\\').'%'))
                ->when(array_key_exists($this->status, NewsletterSubscriber::STATUSES), fn ($q) => $q->where('status', $this->status))
                ->latest()->paginate(30),
            'counts' => NewsletterSubscriber::query()->selectRaw('status, COUNT(*) AS aggregate')->groupBy('status')->pluck('aggregate', 'status')->all(),
            'brevo' => SyncNewsletterWithBrevo::enabled(),
        ])->title('Newsletter');
    }

    private function me(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
