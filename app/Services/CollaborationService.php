<?php

namespace App\Services;

use App\Enums\Permission;
use App\Models\Collaboration;
use App\Models\CollaborationFile;
use App\Models\User;
use App\Support\SafeCsv;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Demandes de collaboration (cahier § 2.7, § 4.5, § 5.5, § 8.5).
 */
class CollaborationService
{
    public const MAX_FILES = 3;

    public function __construct(private readonly UploadService $uploads) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  array<int, mixed>  $files
     */
    public function submit(array $input, array $files, ?string $ip): Collaboration
    {
        $input = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $input);
        $input['email'] = mb_strtolower((string) ($input['email'] ?? ''));
        $data = Validator::make($input, [
            'name' => ['required', 'string', 'min:1', 'max:120'],
            'organization' => ['nullable', 'string', 'max:200'],
            'email' => ['required', 'string', 'max:254', 'email:rfc'],
            'subject' => ['required', 'string', 'min:1', 'max:200'],
            'kind' => ['required', Rule::in(array_keys(Collaboration::KINDS))],
            'description' => ['required', 'string', 'max:5000'],
        ], [], ['name' => 'nom', 'organization' => 'organisation', 'subject' => 'sujet', 'kind' => 'type de demande'])->validate();

        $files = array_values(array_filter($files, fn ($f) => $f instanceof UploadedFile));
        if (count($files) > self::MAX_FILES) {
            throw ValidationException::withMessages(['attachments' => 'Trois pièces jointes au maximum.']);
        }
        // Les fichiers sont contrôlés et stockés AVANT la transaction ; en cas d'échec ils sont supprimés.
        $stored = [];
        try {
            foreach ($files as $file) {
                $stored[] = $this->uploads->attachment($file);
            }

            return DB::transaction(function () use ($data, $stored, $ip) {
                $collab = new Collaboration($data);
                // Statut et date imposés par le serveur (cahier § 5.5).
                $collab->forceFill(['status' => 'new', 'ip_hash' => Audit::hashIp($ip)])->save();
                foreach ($stored as $s) {
                    (new CollaborationFile)->forceFill(['collaboration_id' => $collab->id, 'path' => $s['path'], 'original_name' => $s['name'], 'mime' => $s['mime'], 'size' => $s['size']])->save();
                }

                return $collab;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete(array_column($stored, 'path'));
            throw $e;
        }
    }

    public function can(User $user): bool
    {
        return $user->isActive() && $user->hasPermission(Permission::ManageCollaborations);
    }

    public function setStatus(User $user, Collaboration $collab, string $status): void
    {
        if (! $this->can($user)) {
            throw new AuthorizationException('Vous ne pouvez pas traiter les demandes de collaboration.');
        }
        if (! array_key_exists($status, Collaboration::STATUSES)) {
            throw ValidationException::withMessages(['status' => 'Statut inconnu.']);
        }
        $collab->forceFill(['status' => $status, 'handled_by' => $user->id])->save();
        Audit::record('collaboration.status_changed', $collab, ['status' => $status]);
    }

    public function delete(User $user, Collaboration $collab): void
    {
        if (! $this->can($user)) {
            throw new AuthorizationException('Vous ne pouvez pas supprimer cette demande.');
        }
        self::destroy($collab);
        Audit::record('collaboration.deleted', $collab);
    }

    /** Export CSV sûr (bug n° 11) : flux ligne à ligne, sans tout charger en mémoire. */
    public function exportCsv(User $user, ?string $status = null): \Closure
    {
        if (! $this->can($user)) {
            throw new AuthorizationException('Export réservé aux gestionnaires des collaborations.');
        }
        Audit::record('collaboration.exported', null, ['status' => $status]);

        return function () use ($status) {
            echo SafeCsv::BOM.SafeCsv::row(['Date', 'Nom', 'Organisation', 'Email', 'Sujet', 'Type', 'Statut', 'Description', 'Pièces jointes']);
            Collaboration::query()->withCount('files')
                ->when(array_key_exists((string) $status, Collaboration::STATUSES), fn ($q) => $q->where('status', $status))
                ->orderByDesc('created_at')->chunk(200, function ($rows) {
                    foreach ($rows as $c) {
                        echo SafeCsv::row([
                            $c->created_at->setTimezone(config('app.display_timezone'))->format('d/m/Y H:i'),
                            $c->name, $c->organization, $c->email, $c->subject, $c->kindLabel(), $c->statusLabel(), $c->description, $c->files_count,
                        ]);
                    }
                });
        };
    }

    /** Purge RGPD (cahier § 8.5) : demandes de plus de N mois, pièces jointes comprises. */
    public static function purge(?int $months = null): int
    {
        $months ??= (int) config('yewtod.retention.collaborations_months', 24);
        $count = 0;
        Collaboration::query()->where('created_at', '<', now()->subMonths($months))->with('files')->chunkById(100, function ($rows) use (&$count) {
            foreach ($rows as $c) {
                self::destroy($c);
                $count++;
            }
        });

        return $count;
    }

    private static function destroy(Collaboration $collab): void
    {
        Storage::disk('local')->delete($collab->files->pluck('path')->all());
        $collab->delete();
    }
}
