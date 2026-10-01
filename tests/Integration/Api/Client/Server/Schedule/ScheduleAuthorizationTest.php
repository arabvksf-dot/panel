<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Schedule;

use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class ScheduleAuthorizationTest extends ClientApiIntegrationTestCase
{
    /**
     * Tests that a subuser with access to two servers cannot improperly access a resource
     * on Server A when providing a URL that points to Server B. This prevents a regression
     * in the code where controllers didn't properly validate that a resource was assigned
     * to the server that was also present in the URL.
     *
     * The comments within the test code itself are better at explaining exactly what is
     * being tested and protected against.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('methodDataProvider')]
    public function testAccessToAServersSchedulesIsRestrictedProperly(string $method, string $endpoint)
    {
         
        [$user, $server1] = $this->generateTestAccount();
         
        $server2 = $this->createServerModel();
         
        $server3 = $this->createServerModel();

         
         
        Subuser::factory()->create(['server_id' => $server2->id, 'user_id' => $user->id]);

        $schedule1 = Schedule::factory()->create(['server_id' => $server1->id]);
        $schedule2 = Schedule::factory()->create(['server_id' => $server2->id]);
        $schedule3 = Schedule::factory()->create(['server_id' => $server3->id]);

         
         
        $response = $this->actingAs($user)->json($method, $this->link($server1, '/schedules/' . $schedule1->id . $endpoint));
        $this->assertTrue($response->status() <= 204 || $response->status() === 400 || $response->status() === 422);

         
         
        $this->actingAs($user)->json($method, $this->link($server2, '/schedules/' . $schedule2->id . $endpoint))->assertForbidden();

         
         
         
        $this->actingAs($user)->json($method, $this->link($server1, '/schedules/' . $schedule2->id . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server1, '/schedules/' . $schedule3->id . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server2, '/schedules/' . $schedule3->id . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server3, '/schedules/' . $schedule3->id . $endpoint))->assertNotFound();
    }

    public static function methodDataProvider(): array
    {
        return [
            ['GET', ''],
            ['POST', ''],
            ['DELETE', ''],
            ['POST', '/execute'],
            ['POST', '/tasks'],
        ];
    }
}
