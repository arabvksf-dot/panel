<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Subuser;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Subuser;
use Illuminate\Support\Facades\Bus;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class SubuserAuthorizationTest extends ClientApiIntegrationTestCase
{
    /**
     * Test that mismatched subusers are not accessible to a server.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('methodDataProvider')]
    public function testUserCannotAccessResourceBelongingToOtherServers(string $method)
    {
        Bus::fake([RevokeSftpAccessJob::class]);

         
        /** @var User $internal */
        $internal = User::factory()->create();

         
        [$user, $server1] = $this->generateTestAccount();
         
        $server2 = $this->createServerModel();
         
        $server3 = $this->createServerModel();

         
         
        Subuser::factory()->create(['server_id' => $server2->id, 'user_id' => $user->id]);

        Subuser::factory()->create(['server_id' => $server1->id, 'user_id' => $internal->id]);
        Subuser::factory()->create(['server_id' => $server2->id, 'user_id' => $internal->id]);
        Subuser::factory()->create(['server_id' => $server3->id, 'user_id' => $internal->id]);

         
        $this->actingAs($user)->json($method, $this->link($server1, '/users/' . $internal->uuid))->assertStatus($method === 'POST' ? 422 : ($method === 'DELETE' ? 204 : 200));

         
         
        $this->actingAs($user)->json($method, $this->link($server2, '/users/' . $internal->uuid))->assertForbidden();
        $this->actingAs($user)->json($method, $this->link($server3, '/users/' . $internal->uuid))->assertNotFound();

        if ($method === 'DELETE') {
            Bus::assertDispatchedTimes(function (RevokeSftpAccessJob $job) use ($server1, $internal) {
                return $job->user === $internal->uuid && $job->target->is($server1);
            });
        } else {
            Bus::assertNotDispatched(RevokeSftpAccessJob::class);
        }
    }

    public static function methodDataProvider(): array
    {
        return [['GET'], ['POST'], ['DELETE']];
    }
}
