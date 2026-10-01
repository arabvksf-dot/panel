<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Database;

use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Services\Databases\DatabasePasswordService;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class DatabaseAuthorizationTest extends ClientApiIntegrationTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('methodDataProvider')]
    public function testAccessToAServersDatabasesIsRestrictedProperly(string $method, string $endpoint)
    {
         
        [$user, $server1] = $this->generateTestAccount();
         
        $server2 = $this->createServerModel();
         
        $server3 = $this->createServerModel();

        $host = DatabaseHost::factory()->create([]);

         
         
        Subuser::factory()->create(['server_id' => $server2->id, 'user_id' => $user->id]);

        $database1 = Database::factory()->create(['server_id' => $server1->id, 'database_host_id' => $host->id]);
        $database2 = Database::factory()->create(['server_id' => $server2->id, 'database_host_id' => $host->id]);
        $database3 = Database::factory()->create(['server_id' => $server3->id, 'database_host_id' => $host->id]);

        $this
            ->mock($method === 'POST' ? DatabasePasswordService::class : DatabaseManagementService::class)
            ->expects($method === 'POST' ? 'handle' : 'delete')
            ->andReturn($method === 'POST' ? 'foo' : null);

        $hashids = $this->app->make(HashidsInterface::class);
         
         
        $this->actingAs($user)->json($method, $this->link($server1, '/databases/' . $hashids->encode($database1->id) . $endpoint))
            ->assertStatus($method === 'DELETE' ? 204 : 200);

         
         
        $this->actingAs($user)->json($method, $this->link($server2, '/databases/' . $hashids->encode($database2->id) . $endpoint))->assertForbidden();

         
         
         
        $this->actingAs($user)->json($method, $this->link($server1, '/databases/' . $hashids->encode($database2->id) . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server1, '/databases/' . $hashids->encode($database3->id) . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server2, '/databases/' . $hashids->encode($database3->id) . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server3, '/databases/' . $hashids->encode($database3->id) . $endpoint))->assertNotFound();
    }

    public static function methodDataProvider(): array
    {
        return [
            ['POST', '/rotate-password'],
            ['DELETE', ''],
        ];
    }
}
