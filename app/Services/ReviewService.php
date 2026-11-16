<?php

namespace App\Services;

use App\Enums\ContentType;
use App\Enums\Permission;
use App\Models\Book;
use App\Models\Content;
use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

/**
 * Avis des lecteurs (cahier § 5.4, décision 6) : uniquement sur un article
 * ou un livre publié ; toujours créés « en attente » avec la date du serveur ;
 * modération = changement de statut tracé (qui, quand), jamais du texte.
 */
class ReviewService
{
    /** @param array<string, mixed> $input */
    public function submit(Model $target, array $input, ?string $ip): Review
    {
        if (! self::accepts($target)) {
            throw new InvalidArgumentException('Les avis ne sont pas ouverts sur ce contenu.');
        }
        $data = Validator::make([
            'name' => trim((string) ($input['name'] ?? '')),
            'body' => trim((string) ($input['body'] ?? '')),
        ], [
            'name' => ['required', 'string', 'min:1', 'max:80'],
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ], [], ['name' => 'nom', 'body' => 'avis'])->validate();

        $review = new Review($data);
        $review->forceFill([
            'reviewable_type' => $target->getMorphClass(),
            'reviewable_id' => $target->getKey(),
            'status' => 'pending',
            'ip_hash' => Audit::hashIp($ip),
        ])->save();

        return $review;
    }

    /** Avis ouverts : articles et livres publiquement visibles. */
    public static function accepts(Model $target): bool
    {
        return ($target instanceof Content && $target->type === ContentType::Article && $target->isPubliclyVisible())
            || ($target instanceof Book && $target->isPubliclyVisible());
    }

    public function canModerate(User $user, Review $review): bool
    {
        return $user->isActive() && $user->hasPermission($review->reviewable_type === Book::class ? Permission::ManageBooks : Permission::ManageArticles);
    }

    public function moderate(User $user, Review $review, string $status): void
    {
        if (! in_array($status, ['approved', 'rejected', 'pending'], true)) {
            throw new InvalidArgumentException('Statut de modération inconnu.');
        }
        if (! $this->canModerate($user, $review)) {
            throw new AuthorizationException('Vous ne pouvez pas modérer cet avis.');
        }
        $review->forceFill(['status' => $status, 'moderated_by' => $user->id, 'moderated_at' => now()])->save();
        Audit::record('review.'.$status, $review, ['reviewable' => class_basename($review->reviewable_type), 'id' => $review->reviewable_id]);
    }

    public function delete(User $user, Review $review): void
    {
        if (! $this->canModerate($user, $review)) {
            throw new AuthorizationException('Vous ne pouvez pas supprimer cet avis.');
        }
        $review->delete();
        Audit::record('review.deleted', $review, ['reviewable' => class_basename($review->reviewable_type), 'id' => $review->reviewable_id]);
    }

    /**
     * Avis qu'un membre a le droit de voir dans la file de modération.
     *
     * @return Builder<Review>
     */
    public static function visibleTo(User $user): Builder
    {
        $types = [];
        if ($user->hasPermission(Permission::ManageArticles)) {
            $types[] = Content::class;
        }
        if ($user->hasPermission(Permission::ManageBooks)) {
            $types[] = Book::class;
        }

        return Review::query()->whereIn('reviewable_type', $types === [] ? ['-'] : $types);
    }
}
