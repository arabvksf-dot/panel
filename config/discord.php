<?php

return [
    'oauth' => [
        'enabled' => (bool) env('DISCORD_OAUTH_ENABLED', false),
        'client_id' => env('DISCORD_CLIENT_ID'),
        'client_secret' => env('DISCORD_CLIENT_SECRET'),
        'redirect_uri' => env('DISCORD_REDIRECT_URI'),
        'authorize_url' => env('DISCORD_AUTHORIZE_URL', 'https://discord.com/oauth2/authorize'),
        'token_url' => env('DISCORD_TOKEN_URL', 'https://discord.com/api/oauth2/token'),
        'user_url' => env('DISCORD_USER_URL', 'https://discord.com/api/users/@me'),
        'timeout' => (int) env('DISCORD_OAUTH_TIMEOUT', 10),
        'scopes' => ['identify', 'email'],
    ],
    'bot' => [
        'enabled' => (bool) env('DISCORD_BOT_ENABLED', false),
        'token' => env('DISCORD_BOT_TOKEN'),
        'guild_id' => env('DISCORD_GUILD_ID'),
        'hosting_role_id' => env('DISCORD_HOSTING_ROLE_ID'),
        'no_hosting_role_id' => env('DISCORD_NO_HOSTING_ROLE_ID'),
        'shared_secret' => env('DISCORD_BOT_SHARED_SECRET'),
    ],
];