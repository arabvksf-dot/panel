<?php

namespace Pterodactyl\Tests\Integration\Http\Controllers\Api\Internal;

use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Http\HttpTestCase;

class DiscordHostingStatusControllerTest extends HttpTestCase
{
    private const ENDPOINT = '/api/internal/discord/hosting-status';
    private const SECRET = 'test-discord-shared-secret';

    public function setUp(): void
    {
        parent::setUp();

        config()->set('discord.bot.enabled', true);
        config()->set('discord.bot.shared_secret', self::SECRET);
        config()->set('security.discord_signature_ttl', 60);
    }

    public function testReturnsActiveHostingForLinkedDiscordAccount(): void
    {
        $discordId = '123456789012345678';
        $user = User::factory()->create(['discord_id' => $discordId]);
        $this->createServerModel([
            'owner_id' => $user->id,
            'installed_at' => now(),
            'status' => null,
        ]);

        $this->signedRequest(['discord_id' => $discordId])
            ->assertOk()
            ->assertExactJson(['linked' => true, 'has_active_hosting' => true]);
    }

    public function testUnlinkedDiscordAccountHasNoHosting(): void
    {
        $this->signedRequest(['discord_id' => '123456789012345678'])
            ->assertOk()
            ->assertExactJson(['linked' => false, 'has_active_hosting' => false]);
    }

    public function testSignedRequestNonceCannotBeReplayed(): void
    {
        $payload = ['discord_id' => '123456789012345678'];
        $nonce = 'replay-protection-test-nonce';

        $this->signedRequest($payload, $nonce)->assertOk();
        $this->signedRequest($payload, $nonce)->assertUnauthorized();
    }

    private function signedRequest(array $payload, ?string $nonce = null, ?int $timestamp = null)
    {
        $timestamp = (string) ($timestamp ?? time());
        $nonce ??= bin2hex(random_bytes(16));
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $message = implode('.', [$timestamp, $nonce, 'POST', self::ENDPOINT, $body]);

        return $this->call('POST', self::ENDPOINT, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_DISCORD_TIMESTAMP' => $timestamp,
            'HTTP_X_DISCORD_NONCE' => $nonce,
            'HTTP_X_DISCORD_SIGNATURE' => hash_hmac('sha256', $message, self::SECRET),
        ], $body);
    }
}