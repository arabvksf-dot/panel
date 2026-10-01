<?php

namespace Pterodactyl\Http\Middleware\Api\Client\Server;

use Illuminate\Http\Request;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Allocation;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResourceBelongsToServer
{
    /**
     * Looks at the request parameters to determine if the given resource belongs
     * to the requested server. If not, a 404 error will be returned to the caller.
     *
     * This is critical to ensuring that all subsequent logic is using exactly the
     * server that is expected, and that we're not accessing a resource completely
     * unrelated to the server provided in the request.
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        $params = $request->route()->parameters();
        if (! isset($params['server']) || !$params['server'] instanceof Server) {
            throw new \InvalidArgumentException('This middleware cannot be used in a context that is missing a server in the parameters.');
        }

        /** @var Server $server */
        $server = $request->route()->parameter('server');
        $exception = new NotFoundHttpException('The requested resource was not found for this server.');
        foreach ($params as $key => $model) {
             
             
             
             
            if ($key === 'server' || !$model instanceof Model) {
                continue;
            }

            switch (get_class($model)) {
                 
                 
                case Allocation::class:
                case Backup::class:
                case Database::class:
                case Schedule::class:
                case Subuser::class:
                    if ($model->server_id !== $server->id) {
                        throw $exception;
                    }
                    break;
                     
                     
                case User::class:
                    $subuser = $server->subusers()->where('user_id', $model->id)->first();
                    if (is_null($subuser)) {
                        throw $exception;
                    }
                     
                     
                    $request->attributes->set('subuser', $subuser);
                    break;
                     
                     
                case Task::class:
                    $schedule = $request->route()->parameter('schedule');
                    if (!$schedule instanceof Schedule || $model->schedule_id !== $schedule->id || $schedule->server_id !== $server->id) {
                        throw $exception;
                    }
                    break;
                default:
                     
                     
                    throw new \InvalidArgumentException('There is no handler configured for a resource of this type: ' . get_class($model));
            }
        }

        return $next($request);
    }
}
