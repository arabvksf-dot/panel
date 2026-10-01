<?php

namespace Pterodactyl\Tests\Integration\Api\Client;

use Pterodactyl\Models\User;

class AccountDeletionControllerTest extends ClientApiIntegrationTestCase
{
    public function testAccountDeletionRequiresTheCurrentPassword(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->deleteJson('/api/client/account', [
            'password' => 'incorrect-password',
        ])->assertBadRequest();

        $this->assertModelExists($user);
    }

    public function testAccountDeletionIsBlockedWhenTheUserOwnsServers(): void
    {
        [$user] = $this->generateTestAccount();

        $this->actingAs($user)->deleteJson('/api/client/account', [
            'password' => 'password',
        ])->assertBadRequest();

        $this->assertModelExists($user);
    }

    public function testAccountDeletionRevokesTokensAndDeletesTheUser(): void
    {
        $user = User::factory()->create();
        $user->createToken('delete-test', []);

        $this->actingAs($user)->deleteJson('/api/client/account', [
            'password' => 'password',
        ])->assertNoContent();

        $this->assertModelMissing($user);
        $this->assertDatabaseMissing('api_keys', ['user_id' => $user->id]);
        $this->assertGuest();
    }
}