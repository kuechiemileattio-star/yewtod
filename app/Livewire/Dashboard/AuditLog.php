<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

/** Journal d'audit consultable par le Super Admin (cahier § 8.5). */
#[Layout('layouts::dashboard', ['title' => 'Journal d\'audit'])]
class AuditLog extends Component
{
    use WithPagination;

    #[Url(as: 'evenement')]
    public string $event = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless(Gate::forUser($user)->allows('viewAuditLog', $user), 403);
    }

    public function updatedEvent(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        // Liste blanche des filtres : la valeur n'est utilisée que si elle existe.
        $events = Activity::query()->where('log_name', 'audit')->distinct()->orderBy('event')->pluck('event');
        $filter = $events->contains($this->event) ? $this->event : '';

        return view('livewire.dashboard.audit-log', [
            'events' => $events,
            'entries' => Activity::query()->where('log_name', 'audit')->with('causer')
                ->when($filter !== '', fn ($q) => $q->where('event', $filter))
                ->latest('id')->paginate(30),
        ]);
    }
}
