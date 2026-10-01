<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Http\RedirectResponse;
use Carbon\CarbonImmutable;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Events\Auth\DirectLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Pterodactyl\Http\Controllers\Controller;

class DiscordOAuthController extends Controller
{
    public function redirectToDiscord(Request $request): RedirectResponse
    {
        return $this->startOAuth($request, null);
    }

    public function linkAccount(Request $request): RedirectResponse
    {
        return $this->startOAuth($request, $request->user());
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(config('discord.oauth.enabled') && $this->hasSecureOAuthConfiguration(), 404);

        $expectedState = $request->session()->pull('discord_oauth_state');
        $linkUserId = $request->session()->pull('discord_oauth_link_user_id');
        $codeVerifier = $request->session()->pull('discord_oauth_code_verifier');
        $state = $request->query('state');

        if (!is_string($expectedState) || !is_string($state) || !is_string($codeVerifier) || !hash_equals($expectedState, $state)) {
            abort(400, 'Invalid Discord authorization state.');
        }

        if ($request->has('error') || !$request->filled('code')) {
            return redirect('/auth/login?discord=cancelled');
        }

        $tokenResponse = Http::asForm()->acceptJson()->timeout(config('discord.oauth.timeout'))
            ->withOptions(['allow_redirects' => false])
            ->post(config('discord.oauth.token_url'), [
                'client_id' => config('discord.oauth.client_id'),
                'client_secret' => config('discord.oauth.client_secret'),
                'grant_type' => 'authorization_code',
                'code' => $request->string('code')->toString(),
                'code_verifier' => $codeVerifier,
                'redirect_uri' => config('discord.oauth.redirect_uri'),
            ]);

        $accessToken = $tokenResponse->json('access_token');
        if (!$tokenResponse->successful() || !is_string($accessToken) || $accessToken === '') {
            return redirect('/auth/login?discord=failed');
        }

        $profileResponse = Http::acceptJson()->withToken($accessToken)->timeout(config('discord.oauth.timeout'))
            ->withOptions(['allow_redirects' => false])
            ->get(config('discord.oauth.user_url'));
        $profile = $profileResponse->json();
        if (!$profileResponse->successful() || !is_array($profile) || !is_string($profile['id'] ?? null)) {
            return redirect('/auth/login?discord=failed');
        }

        $discordId = $profile['id'];
        if ($linkUserId !== null) {
            if (!$request->user() || $request->user()->id !== $linkUserId) {
                return redirect('/auth/login?discord=link-session-expired');
            }

            $user = User::query()->findOrFail($linkUserId);
            $linkedUser = User::query()->where('discord_id', $discordId)->first();

            if (($linkedUser && $linkedUser->id !== $user->id) || ($user->discord_id && $user->discord_id !== $discordId)) {
                return redirect('/account?discord=already-linked');
            }

            $user->forceFill(['discord_id' => $discordId])->save();
            Activity::event('user:account.discord-linked')->withRequestMetadata()->subject($user)->log();

            return redirect('/account?discord=linked');
        }

        $user = User::query()->where('discord_id', $discordId)->first();
        if (!$user && ($profile['verified'] ?? false) === true && is_string($profile['email'] ?? null)) {
            $user = User::query()->where('email', mb_strtolower($profile['email']))->first();
            if ($user) {
                if ($user->discord_id && !hash_equals($user->discord_id, $discordId)) {
                    $user = null;
                } else {
                    $user->forceFill(['discord_id' => $discordId])->save();
                    Activity::event('user:account.discord-linked')->withRequestMetadata()->subject($user)->log();
                }
            }
        }

        if (!$user) {
            return redirect('/auth/login?discord=unlinked');
        }

        if ($user->use_totp) {
            Activity::event('auth:checkpoint')->withRequestMetadata()->subject($user)->log();
            $request->session()->put('auth_confirmation_token', [
                'user_id' => $user->id,
                'token_value' => Str::random(64),
                'expires_at' => CarbonImmutable::now()->addMinutes(5),
            ]);

            return redirect('/auth/login/checkpoint');
        }

        $request->session()->remove('auth_confirmation_token');
        $request->session()->regenerate();
        Auth::guard()->login($user, true);
        Event::dispatch(new DirectLogin($user, true));

        return redirect()->intended('/');
    }

    private function startOAuth(Request $request, ?User $linkUser): RedirectResponse
    {
        if (!config('discord.oauth.enabled') || !config('discord.oauth.client_id') || !config('discord.oauth.client_secret') || !$this->hasSecureOAuthConfiguration()) {
            abort(404);
        }

        $state = Str::random(64);
        $codeVerifier = Str::random(96);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
        $request->session()->put('discord_oauth_state', $state);
        $request->session()->put('discord_oauth_link_user_id', $linkUser?->id);
        $request->session()->put('discord_oauth_code_verifier', $codeVerifier);

        $query = http_build_query([
            'client_id' => config('discord.oauth.client_id'),
            'redirect_uri' => config('discord.oauth.redirect_uri'),
            'response_type' => 'code',
            'scope' => implode(' ', config('discord.oauth.scopes')),
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return redirect()->away(config('discord.oauth.authorize_url') . '?' . $query);
    }

    private function hasSecureOAuthConfiguration(): bool
    {
        foreach ([
            config('discord.oauth.authorize_url'),
            config('discord.oauth.token_url'),
            config('discord.oauth.user_url'),
            config('discord.oauth.redirect_uri'),
        ] as $url) {
            if (!is_string($url) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
                return false;
            }
        }

        return true;
    }
}