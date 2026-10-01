<?php

namespace Pterodactyl\Tests\Integration\Http\Controllers\Auth;

use Pterodactyl\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Pterodactyl\Events\Auth\DirectLogin;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Pterodactyl\Tests\Integration\Http\HttpTestCase;
use Illuminate\Http\Client\Request as ClientRequest;

class DiscordOAuthControllerTest extends HttpTestCase
{
    private const DISCORD_ID = '123456789012345678';
    private const STATE = 'oauth-state-for-test';
    private const VERIFIER = 'oauth-pkce-verifier-for-test-1234567890';

    public function setUp(): void
    {
        parent::setUp();

        config()->set('discord.oauth.client_id', 'client-id');
        config()->set('discord.oauth.client_secret', 'client-secret');
        config()->set('discord.oauth.redirect_uri', 'https://panel.example.com/auth/discord/callback');
        config()->set('discord.oauth.token_url', 'https://discord.test/oauth2/token');
        config()->set('discord.oauth.user_url', 'https://discord.test/api/users/@me');
        Event::fake([DirectLogin::class]);
    }

    public function testDiscordLoginLinksVerifiedEmailAndUsesPkce(): void
    {
        $user = User::factory()->create(['email' => 'linked@example.com']);
        $this->fakeDiscordProfile(['id' => self::DISCORD_ID, 'email' => $user->email, 'verified' => true]);
        $this->prepareOAuthSession();

        $this->get(route('auth.discord.callback', ['state' => self::STATE, 'code' => 'authorization-code']))
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(self::DISCORD_ID, $user->fresh()->discord_id);
        Event::assertDispatched(fn (DirectLogin $event) => $event->user->is($user) && $event->remember);
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://discord.test/oauth2/token'
            && $request['code_verifier'] === self::VERIFIER);
    }

    public function testDiscordLoginRequiresExistingTotpCheckpoint(): void
    {
        $user = User::factory()->create([
            'email' => 'totp@example.com',
            'discord_id' => self::DISCORD_ID,
            'use_totp' => true,
        ]);
        $this->fakeDiscordProfile(['id' => self::DISCORD_ID, 'email' => $user->email, 'verified' => true]);
        $this->prepareOAuthSession();

        $this->get(route('auth.discord.callback', ['state' => self::STATE, 'code' => 'authorization-code']))
            ->assertRedirect('/auth/login/checkpoint')
            ->assertSessionHas('auth_confirmation_token.user_id', $user->id);

        $this->assertGuest();

        $this->get('/auth/login/checkpoint')->assertOk()->assertSee('AuthConfirmationToken');
    }

    public function testVerifiedEmailCannotLogIntoAnotherDiscordLinkedAccount(): void
    {
        $user = User::factory()->create([
            'email' => 'linked@example.com',
            'discord_id' => '987654321098765432',
        ]);
        $this->fakeDiscordProfile(['id' => self::DISCORD_ID, 'email' => $user->email, 'verified' => true]);
        $this->prepareOAuthSession();

        $this->get(route('auth.discord.callback', ['state' => self::STATE, 'code' => 'authorization-code']))
            ->assertRedirect('/auth/login?discord=unlinked');

        $this->assertGuest();
        $this->assertSame('987654321098765432', $user->fresh()->discord_id);
    }

    public function testAccountLinkingRequiresTheSameAuthenticatedSession(): void
    {
        $user = User::factory()->create();
        $this->fakeDiscordProfile(['id' => self::DISCORD_ID, 'email' => 'other@example.com', 'verified' => true]);
        $this->prepareOAuthSession();
        Session::put('discord_oauth_link_user_id', $user->id);

        $this->get(route('auth.discord.callback', ['state' => self::STATE, 'code' => 'authorization-code']))
            ->assertRedirect('/auth/login?discord=link-session-expired');

        $this->assertGuest();
        $this->assertNull($user->fresh()->discord_id);
    }

    public function testAccountLinkingHonorsPanelWideTwoFactorRequirement(): void
    {
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ALL);
        $user = User::factory()->create(['use_totp' => false]);

        $this->actingAs($user)->post('/account/discord/link')->assertRedirect('/account');
    }

    private function prepareOAuthSession(): void
    {
        Session::put('discord_oauth_state', self::STATE);
        Session::put('discord_oauth_code_verifier', self::VERIFIER);
    }

    private function fakeDiscordProfile(array $profile): void
    {
        Http::fake([
            'https://discord.test/oauth2/token' => Http::response(['access_token' => 'access-token']),
            'https://discord.test/api/users/@me' => Http::response($profile),
        ]);
    }
}