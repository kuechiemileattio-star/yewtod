<?php

namespace App\Http\Controllers\Site;

use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Collaboration;
use App\Models\Content;
use App\Services\CollaborationService;
use App\Services\NewsletterService;
use App\Services\ReviewService;
use App\Services\SpamGuard;
use App\Services\Turnstile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Les 4 formulaires publics (cahier § 8.4) : avis (publication), avis
 * (livre), newsletter, collaboration. Chacun passe par : limitation par IP
 * (route), champ piège + Turnstile (SpamGuard), validation stricte et
 * statuts imposés (services).
 */
class PublicFormController extends Controller
{
    public function __construct(private readonly SpamGuard $guard) {}

    public function reviewWork(Request $request, string $workType, string $workSlug, ReviewService $reviews): RedirectResponse
    {
        abort_unless($workType === ContentType::Article->value, 404);
        $content = Content::query()->published()->ofType(ContentType::Article)->where('slug', $workSlug)->firstOrFail();

        return $this->storeReview($request, $content, $reviews);
    }

    public function reviewBook(Request $request, string $bookSlug, ReviewService $reviews): RedirectResponse
    {
        $book = Book::query()->published()->where('slug', $bookSlug)->firstOrFail();

        return $this->storeReview($request, $book, $reviews);
    }

    public function collaborationForm(): View
    {
        return view('site.collaborations', ['kinds' => Collaboration::KINDS, 'turnstile' => Turnstile::enabled()]);
    }

    public function collaborationStore(Request $request, CollaborationService $service): RedirectResponse
    {
        $this->guard->check($request);
        $files = $request->file('attachments', []);
        $service->submit($request->only(['name', 'organization', 'email', 'subject', 'kind', 'description']), is_array($files) ? $files : [$files], $request->ip());

        return redirect()->route('collaborations')->with('collaboration_sent', true);
    }

    public function newsletterSubscribe(Request $request, NewsletterService $newsletter): RedirectResponse
    {
        try {
            $this->guard->check($request);
            $newsletter->subscribe((string) $request->input('email', ''), $request->ip());
        } catch (ValidationException $e) {
            throw $e->errorBag('newsletter');
        }

        // Message identique dans tous les cas (cahier § 5.6 : pas d'énumération).
        return back()->with('newsletter_status', 'Merci ! Si cette adresse n\'est pas déjà inscrite, un email de confirmation vient de lui être envoyé (lien valable 48 heures).');
    }

    public function newsletterConfirm(string $token, NewsletterService $newsletter): View
    {
        $unsubscribe = $newsletter->confirm($token);

        return view('site.newsletter', ['state' => $unsubscribe !== null ? 'confirmed' : 'invalid', 'token' => $unsubscribe]);
    }

    /** Désinscription en un clic : la page affiche un bouton (un clic), le POST désinscrit. */
    public function newsletterUnsubscribeForm(string $token, NewsletterService $newsletter): View
    {
        $sub = $newsletter->findByUnsubscribeToken($token);

        return view('site.newsletter', ['state' => $sub === null ? 'invalid' : ($sub->status === 'unsubscribed' ? 'unsubscribed' : 'unsubscribe'), 'token' => $token]);
    }

    public function newsletterUnsubscribe(string $token, NewsletterService $newsletter): View
    {
        return view('site.newsletter', ['state' => $newsletter->unsubscribe($token) ? 'unsubscribed' : 'invalid', 'token' => null]);
    }

    private function storeReview(Request $request, Content|Book $target, ReviewService $reviews): RedirectResponse
    {
        try {
            $this->guard->check($request);
            $reviews->submit($target, $request->only(['name', 'body']), $request->ip());
        } catch (ValidationException $e) {
            throw $e->errorBag('review')->redirectTo($target->publicUrl().'#avis');
        }

        return redirect()->to($target->publicUrl().'#avis')->with('review_sent', true);
    }
}
