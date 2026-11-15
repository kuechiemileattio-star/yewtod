<?php

use App\Livewire\Dashboard\Profile;
use App\Livewire\Dashboard\Users;
use App\Models\Invitation;
use Database\Factories\UserFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

// Cahier § 4 : menu filtré et protection serveur ; § 4.10 Mon profil ; § 8.3 sessions.

it('protège l\'écran Utilisateurs côté serveur', function () {
    $this->actingAs(member('editeur'))->get('/dashboard/utilisateurs')->assertForbidden();
    $this->actingAs(member('administrateur'))->get('/dashboard/utilisateurs')->assertOk()->assertSee('Membres');
});

it('n\'affiche dans le menu que les modules autorisés', function () {
    $this->actingAs(member('editeur'))->get('/dashboard')->assertDontSee('Utilisateurs &amp; rôles', false)->assertSee('Mon profil');
    $this->actingAs(member('administrateur'))->get('/dashboard')->assertSee('Utilisateurs &amp; rôles', false)->assertDontSee('Journal d\'audit');
});

it('réserve le journal d\'audit au Super Admin', function () {
    $this->actingAs(member('administrateur'))->get('/dashboard/journal')->assertForbidden();
    $this->actingAs(member('super-admin'))->get('/dashboard/journal')->assertOk();
});

it('verrouille sa propre ligne dans la liste des membres', function () {
    $admin = member('administrateur');
    Livewire::actingAs($admin)->test(Users::class)->assertSee('vous')->assertSee('(verrouillé)');
});

it('exige la reconfirmation du mot de passe pour changer un rôle ou supprimer', function () {
    $admin = member('administrateur');
    $target = member('editeur');
    Livewire::actingAs($admin)->test(Users::class)->call('changeRole', $target->id, role('contributeur')->id)->assertRedirect(route('password.confirm'));
    expect($target->fresh()->role->slug)->toBe('editeur');
    session()->put('auth.password_confirmed_at', time());
    Livewire::actingAs($admin)->test(Users::class)->call('changeRole', $target->id, role('contributeur')->id);
    expect($target->fresh()->role->slug)->toBe('contributeur');
});

it('invite depuis l\'écran avec un rôle autorisé', function () {
    Mail::fake();
    Livewire::actingAs(member('administrateur'))->test(Users::class)
        ->set('tab', 'invitations')->set('inviteEmail', 'nouvelle@exemple.test')->set('inviteRole', role('editeur')->id)
        ->call('invite')->assertDispatched('toast', type: 'success');
    expect(Invitation::where('email', 'nouvelle@exemple.test')->exists())->toBeTrue();
});

it('change le mot de passe, déconnecte les autres sessions et notifie', function () {
    Notification::fake();
    $user = member();
    DB::table('sessions')->insert(['id' => 'autre-appareil', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    Livewire::actingAs($user)->test(Profile::class)
        ->set('current_password', UserFactory::PASSWORD)->set('password', 'Nouveau-Bois-84?')->set('password_confirmation', 'Nouveau-Bois-84?')
        ->call('changePassword')->assertHasNoErrors();
    expect(DB::table('sessions')->where('id', 'autre-appareil')->exists())->toBeFalse()
        ->and(Hash::check('Nouveau-Bois-84?', $user->fresh()->password))->toBeTrue();
});

it('refuse le changement si le mot de passe actuel est faux', function () {
    Livewire::actingAs(member())->test(Profile::class)
        ->set('current_password', 'faux')->set('password', 'Nouveau-Bois-84?')->set('password_confirmation', 'Nouveau-Bois-84?')
        ->call('changePassword')->assertHasErrors('current_password');
});

it('liste les sessions actives et déconnecte les autres appareils', function () {
    $user = member();
    DB::table('sessions')->insert(['id' => 'tablette', 'user_id' => $user->id, 'ip_address' => '203.0.113.9', 'user_agent' => 'Mozilla/5.0 (iPad) Safari/605', 'payload' => '', 'last_activity' => time()]);
    Livewire::actingAs($user)->test(Profile::class)->set('tab', 'sessions')->assertSee('Safari sur iOS')
        ->set('sessionPassword', UserFactory::PASSWORD)->call('logoutOtherDevices');
    expect(DB::table('sessions')->where('id', 'tablette')->exists())->toBeFalse();
});

it('enregistre la photo en WebP (nom aléatoire, métadonnées supprimées)', function () {
    Storage::fake('public');
    $user = member();
    Livewire::actingAs($user)->test(Profile::class)->set('avatar', UploadedFile::fake()->image('moi.jpg', 600, 600));
    $path = $user->fresh()->avatar_path;
    expect($path)->toEndWith('.webp')->not->toContain('moi');
    Storage::disk('public')->assertExists($path);
});

it('refuse un SVG ou un faux fichier image comme photo', function () {
    Storage::fake('public');
    $user = member();
    $svg = UploadedFile::fake()->createWithContent('a.png', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>');
    Livewire::actingAs($user)->test(Profile::class)->set('avatar', $svg)->assertHasErrors('avatar');
    expect($user->fresh()->avatar_path)->toBeNull();
});

it('enregistre la préférence de thème sur le compte', function () {
    $user = member();
    $this->actingAs($user)->putJson('/dashboard/preferences/theme', ['theme' => 'dark'])->assertOk();
    expect($user->fresh()->theme_preference)->toBe('dark');
    $this->get('/dashboard')->assertSee('<html lang="fr" class="dark">', false);
    $this->putJson('/dashboard/preferences/theme', ['theme' => 'violet'])->assertUnprocessable();
});
