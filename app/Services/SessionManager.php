<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sessions côté serveur (cahier § 8.3) : liste, déconnexion des autres appareils.
 */
class SessionManager
{
    /**
     * @return Collection<int, \stdClass>
     */
    public function forUser(User $user, ?string $currentId = null): Collection
    {
        /** @var Collection<int, \stdClass> $sessions */
        $sessions = DB::table('sessions')->where('user_id', $user->id)->orderByDesc('last_activity')->limit(50)->get()
            ->map(fn ($row) => (object) [
                'id' => (string) $row->id,
                'ip' => $row->ip_address,
                'agent' => (string) $row->user_agent,
                'device' => $this->describe((string) $row->user_agent),
                'last_active' => now()->setTimestamp((int) $row->last_activity),
                'current' => $currentId !== null && hash_equals((string) $row->id, $currentId),
            ]);

        return $sessions;
    }

    public function destroyOthers(User $user, string $currentId): int
    {
        return DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $currentId)->delete();
    }

    public function destroyAll(User $user): int
    {
        return DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    private function describe(string $agent): string
    {
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Navigateur inconnu',
        };
        $os = match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'système inconnu',
        };

        return $browser.' sur '.$os;
    }
}
