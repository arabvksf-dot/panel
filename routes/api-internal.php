<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Api\Internal\DiscordHostingStatusController;

Route::post('/hosting-status', DiscordHostingStatusController::class);