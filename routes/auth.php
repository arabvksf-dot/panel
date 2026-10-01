<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Auth;










 
 
Route::get('/login', [Auth\LoginController::class, 'index'])->name('auth.login');
Route::get('/password', [Auth\LoginController::class, 'index'])->name('auth.forgot-password');
Route::get('/password/reset/{token}', [Auth\LoginController::class, 'index'])->name('auth.reset');

 
 
 
// @see \Pterodactyl\Providers\RouteServiceProvider
Route::middleware(['throttle:authentication'])->group(function () {
     
    Route::post('/login', [Auth\LoginController::class, 'login'])->middleware('recaptcha');
    Route::post('/login/checkpoint', Auth\LoginCheckpointController::class)->name('auth.login-checkpoint');

     
     
    Route::post('/password', [Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->name('auth.post.forgot-password')
        ->middleware('recaptcha');
});

 
 
 
Route::post('/password/reset', Auth\ResetPasswordController::class)->name('auth.reset-password');

Route::get('/discord', [Auth\DiscordOAuthController::class, 'redirectToDiscord'])
    ->middleware('throttle:authentication')
    ->name('auth.discord.redirect');
Route::get('/discord/callback', [Auth\DiscordOAuthController::class, 'callback'])
    ->withoutMiddleware('guest')
    ->middleware('throttle:authentication')
    ->name('auth.discord.callback');

 
 
Route::post('/logout', [Auth\LoginController::class, 'logout'])
    ->withoutMiddleware('guest')
    ->middleware('auth')
    ->name('auth.logout');

// Catch any other combinations of routes and pass them off to the React component.
Route::fallback([Auth\LoginController::class, 'index']);
