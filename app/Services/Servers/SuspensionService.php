<?php

namespace Pterodactyl\Services\Servers;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SuspensionService
{
    public const ACTION_SUSPEND = 'suspend';
    public const ACTION_UNSUSPEND = 'unsuspend';

    /**
     * SuspensionService constructor.
     */
    public function __construct(
        private DaemonServerRepository $daemonServerRepository,
    ) {
    }

    /**
     * Suspends a server on the system.
     *
     * @throws \Throwable
     */
    public function toggle(Server $server, string $action = self::ACTION_SUSPEND): void
    {
        Assert::oneOf($action, [self::ACTION_SUSPEND, self::ACTION_UNSUSPEND]);

        $isSuspending = $action === self::ACTION_SUSPEND;
         
         
         
        if ($isSuspending === $server->isSuspended()) {
            return;
        }

         
        if (!is_null($server->transfer)) {
            throw new ConflictHttpException('Cannot toggle suspension status on a server that is currently being transferred.');
        }

         
        $server->update([
            'status' => $isSuspending ? Server::STATUS_SUSPENDED : null,
        ]);

        try {
             
            $this->daemonServerRepository->setServer($server)->sync();
        } catch (\Exception $exception) {
             
            $server->update([
                'status' => $isSuspending ? null : Server::STATUS_SUSPENDED,
            ]);
            throw $exception;
        }
    }
}
