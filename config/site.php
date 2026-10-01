<?php

return [
    'name' => env('SITE_NAME', env('APP_NAME', 'Pterodactyl Panel')),
    'url' => env('SITE_URL', env('APP_URL')),
    'logo' => env('SITE_LOGO', '/assets/logo/logo.png'),
    'favicon' => env('SITE_FAVICON', '/assets/logo/logo.png'),
    'theme_color' => env('SITE_THEME_COLOR', '#100b18'),
    'appearance' => [
        'themes' => ['dark', 'light'],
        'accents' => ['violet', 'blue', 'emerald', 'rose', 'amber'],
        'font_sizes' => ['min' => 14, 'max' => 20, 'step' => 2],
    ],
    'seo' => [
        'description' => env('SITE_SEO_DESCRIPTION', 'Game server management panel'),
        'robots' => env('SITE_SEO_ROBOTS', 'noindex'),
    ],
];