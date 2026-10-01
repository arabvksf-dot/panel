<?php

namespace Pterodactyl\Tests\Integration\Http\Controllers\Auth;

use Pterodactyl\Tests\Integration\Http\HttpTestCase;

class LoginControllerTest extends HttpTestCase
{
    public function testNonScalarCredentialsAreRejectedBeforeAuthentication(): void
    {
        $this->postJson('/auth/login', [
            'user' => ['admin'],
            'password' => ['password'],
        ])->assertUnprocessable();

        $this->assertGuest();
    }

    public function testMissingCredentialsAreRejected(): void
    {
        $this->postJson('/auth/login', [])->assertUnprocessable();

        $this->assertGuest();
    }
}