<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Allocation;

use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class AllocationAuthorizationTest extends ClientApiIntegrationTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('methodDataProvider')]
    public function testAccessToAServersAllocationsIsRestrictedProperly(string $method, string $endpoint)
    {
         
        [$user, $server1] = $this->generateTestAccount();
         
        $server2 = $this->createServerModel();
         
        $server3 = $this->createServerModel();

         
         
        Subuser::factory()->create(['server_id' => $server2->id, 'user_id' => $user->id]);

        $allocation1 = Allocation::factory()->create(['server_id' => $server1->id, 'node_id' => $server1->node_id]);
        $allocation2 = Allocation::factory()->create(['server_id' => $server2->id, 'node_id' => $server2->node_id]);
        $allocation3 = Allocation::factory()->create(['server_id' => $server3->id, 'node_id' => $server3->node_id]);

         
         
        $response = $this->actingAs($user)->json($method, $this->link($server1, '/network/allocations/' . $allocation1->id . $endpoint));
        $this->assertTrue($response->status() <= 204 || $response->status() === 400 || $response->status() === 422);

         
         
        $this->actingAs($user)->json($method, $this->link($server2, '/network/allocations/' . $allocation2->id . $endpoint))->assertForbidden();

         
         
         
        $this->actingAs($user)->json($method, $this->link($server1, '/network/allocations/' . $allocation2->id . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server1, '/network/allocations/' . $allocation3->id . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server2, '/network/allocations/' . $allocation3->id . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server3, '/network/allocations/' . $allocation3->id . $endpoint))->assertNotFound();
    }

    public static function methodDataProvider(): array
    {
        return [
            ['POST', ''],
            ['DELETE', ''],
            ['POST', '/primary'],
        ];
    }
}
