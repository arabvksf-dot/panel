<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\EggVariable;

class EnvironmentService
{
    private array $additional = [];

    /**
     * Dynamically configure additional environment variables to be assigned
     * with a specific server.
     */
    public function setEnvironmentKey(string $key, callable $closure): void
    {
        $this->additional[$key] = $closure;
    }

    /**
     * Return the dynamically added additional keys.
     */
    public function getEnvironmentKeys(): array
    {
        return $this->additional;
    }

    /**
     * Take all of the environment variables configured for this server and return
     * them in an easy to process format.
     */
    public function handle(Server $server): array
    {
        $variables = $server->variables->toBase()->mapWithKeys(function (EggVariable $variable) {
            return [$variable->env_variable => $variable->server_value ?? $variable->default_value];
        });

         
         
         
        foreach ($this->getEnvironmentMappings() as $key => $object) {
            $variables->put($key, object_get($server, $object));
        }

         
        foreach (config('pterodactyl.environment_variables', []) as $key => $object) {
            $variables->put(
                $key,
                is_callable($object) ? call_user_func($object, $server) : object_get($server, $object)
            );
        }

         
        foreach ($this->additional as $key => $closure) {
            $variables->put($key, call_user_func($closure, $server));
        }

        return $variables->toArray();
    }

    /**
     * Return a mapping of Panel default environment variables.
     */
    private function getEnvironmentMappings(): array
    {
        return [
            'STARTUP' => 'startup',
            'P_SERVER_LOCATION' => 'location.short',
            'P_SERVER_UUID' => 'uuid',
        ];
    }
}
