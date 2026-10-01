<?php

namespace Pterodactyl\Tests\Integration\Api\Client;

use Pterodactyl\Models\User;

class AccountPreferencesControllerTest extends ClientApiIntegrationTestCase
{
    public function testPreferencesAreSavedOnlyForTheAuthenticatedUser(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $appearance = [
            'theme' => 'light',
            'accent' => 'emerald',
            'motion' => false,
            'font_size' => 18,
        ];

        $this->actingAs($user)->putJson('/api/client/account/preferences', [
            'language' => 'ar',
            'appearance' => $appearance,
            'onboarding_completed' => true,
            'user_id' => $otherUser->id,
        ])->assertNoContent();

        $this->assertSame($appearance, $user->fresh()->appearance);
        $this->assertSame('ar', $user->fresh()->language);
        $this->assertTrue($user->fresh()->onboarding_completed);
        $this->assertSame('en', $otherUser->fresh()->language);
    }

    public function testUnsupportedAppearanceValuesAreRejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/client/account/preferences', [
            'language' => 'en',
            'appearance' => [
                'theme' => 'system',
                'accent' => 'custom-css',
                'motion' => true,
                'font_size' => 100,
            ],
            'onboarding_completed' => false,
        ])->assertUnprocessable();
    }

    public function testDisablingAppearancePreventsAppearanceChangesButKeepsLanguageUpdates(): void
    {
        config()->set('features.appearance', false);
        $user = User::factory()->create([
            'language' => 'en',
            'appearance' => [
                'theme' => 'dark',
                'accent' => 'violet',
                'motion' => true,
                'font_size' => 16,
            ],
        ]);

        $this->actingAs($user)->putJson('/api/client/account/preferences', [
            'language' => 'ar',
            'appearance' => [
                'theme' => 'light',
                'accent' => 'rose',
                'motion' => false,
                'font_size' => 20,
            ],
            'onboarding_completed' => true,
        ])->assertNoContent();

        $this->assertSame('ar', $user->fresh()->language);
        $this->assertSame([
            'theme' => 'dark',
            'accent' => 'violet',
            'motion' => true,
            'font_size' => 16,
        ], $user->fresh()->appearance);
    }
}