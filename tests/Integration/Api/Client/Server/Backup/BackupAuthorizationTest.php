<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Backup;

use Carbon\CarbonImmutable;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Services\Backups\DeleteBackupService;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class BackupAuthorizationTest extends ClientApiIntegrationTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('methodDataProvider')]
    public function testAccessToAServersBackupIsRestrictedProperly(string $method, string $endpoint)
    {
         
        [$user, $server1] = $this->generateTestAccount();
         
        $server2 = $this->createServerModel();
         
        $server3 = $this->createServerModel();

         
         
        Subuser::factory()->create(['server_id' => $server2->id, 'user_id' => $user->id]);

        $backup1 = Backup::factory()->create(['server_id' => $server1->id, 'completed_at' => CarbonImmutable::now()]);
        $backup2 = Backup::factory()->create(['server_id' => $server2->id, 'completed_at' => CarbonImmutable::now()]);
        $backup3 = Backup::factory()->create(['server_id' => $server3->id, 'completed_at' => CarbonImmutable::now()]);

        $this->instance(DeleteBackupService::class, $mock = \Mockery::mock(DeleteBackupService::class));

        if ($method === 'DELETE') {
            $mock->expects('handle')->andReturnUndefined();
        }

         
         
        $this->actingAs($user)->json($method, $this->link($server1, '/backups/' . $backup1->uuid . $endpoint))
            ->assertStatus($method === 'DELETE' ? 204 : 200);

         
         
        $this->actingAs($user)->json($method, $this->link($server2, '/backups/' . $backup2->uuid . $endpoint))->assertForbidden();

         
         
         
        $this->actingAs($user)->json($method, $this->link($server1, '/backups/' . $backup2->uuid . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server1, '/backups/' . $backup3->uuid . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server2, '/backups/' . $backup3->uuid . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server3, '/backups/' . $backup3->uuid . $endpoint))->assertNotFound();
    }

    public static function methodDataProvider(): array
    {
        return [
            ['GET', ''],
            ['GET', '/download'],
            ['DELETE', ''],
        ];
    }
}
