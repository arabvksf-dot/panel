<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class DiscordInternalSignature
{
    public function handle(Request $request, Closure $next): mixed
    {
        $secret = (string) config('discord.bot.shared_secret');
        $timestamp = $request->header('X-Discord-Timestamp', '');
        $nonce = $request->header('X-Discord-Nonce', '');
        $signature = $request->header('X-Discord-Signature', '');
        $ttl = config('security.discord_signature_ttl');

        if (!config('discord.bot.enabled') || $secret === '' || !ctype_digit($timestamp) || !preg_match('/^[A-Za-z0-9-]{16,64}$/', $nonce) || !preg_match('/^[a-f0-9]{64}$/i', $signature)) {
            throw new UnauthorizedHttpException('Discord');
        }

        if (abs(time() - (int) $timestamp) > $ttl) {
            throw new UnauthorizedHttpException('Discord');
        }

        $message = implode('.', [$timestamp, $nonce, $request->method(), $request->getPathInfo(), $request->getContent()]);
        $expected = hash_hmac('sha256', $message, $secret);
        if (!hash_equals($expected, strtolower($signature))) {
            throw new UnauthorizedHttpException('Discord');
        }

        if (!Cache::add('discord-signature-nonce:' . $nonce, true, now()->addSeconds($ttl * 2))) {
            throw new UnauthorizedHttpException('Discord');
        }

        return $next($request);
    }
}