<?php

namespace Pterodactyl\Http\Controllers\Api\Internal;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Http\Controllers\Controller;

class DiscordHostingStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'discord_id' => ['required', 'string', 'regex:/^[0-9]{17,20}$/'],
        ]);

        $user = User::query()->where('discord_id', $validated['discord_id'])->first();
        if (!$user) {
            return response()->json(['linked' => false, 'has_active_hosting' => false]);
        }

        $hasActiveHosting = $user->servers()
            ->whereNotNull('installed_at')
            ->where(function (Builder $query) {
                $query->whereNull('status')->orWhereNotIn('status', [
                    Server::STATUS_INSTALLING,
                    Server::STATUS_INSTALL_FAILED,
                    Server::STATUS_SUSPENDED,
                    Server::STATUS_RESTORING_BACKUP,
                ]);
            })
            ->exists();

        return response()->json([
            'linked' => true,
            'has_active_hosting' => $hasActiveHosting,
        ]);
    }
}