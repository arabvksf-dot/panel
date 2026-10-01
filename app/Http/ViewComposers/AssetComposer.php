<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;
use Pterodactyl\Services\Helpers\AssetHashService;

class AssetComposer
{
    /**
     * AssetComposer constructor.
     */
    public function __construct(private AssetHashService $assetHashService)
    {
    }

    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view): void
    {
        $view->with('asset', $this->assetHashService);
        $view->with('siteConfiguration', [
            'name' => config('site.name'),
            'logo' => config('site.logo'),
            'favicon' => config('site.favicon'),
            'discordOAuthEnabled' => config('discord.oauth.enabled', false),
            'aiAssistantEnabled' => config('features.ai_assistant', false) && config('ai.enabled', false),
            'aiErrorAnalysisEnabled' => config('features.ai_error_analysis', false) && config('ai.enabled', false),
            'aiMaxHistory' => config('ai.max_history'),
            'appearanceOptions' => config('site.appearance'),
            'appearanceEnabled' => config('features.appearance', true),
            'locale' => config('app.locale') ?? 'en',
            'recaptcha' => [
                'enabled' => config('recaptcha.enabled', false),
                'siteKey' => config('recaptcha.website_key') ?? '',
            ],
        ]);
    }
}
