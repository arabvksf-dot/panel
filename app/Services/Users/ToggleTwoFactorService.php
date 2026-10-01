<?php

namespace Pterodactyl\Services\Users;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Repositories\Eloquent\RecoveryTokenRepository;
use Pterodactyl\Exceptions\Service\User\TwoFactorAuthenticationTokenInvalid;

class ToggleTwoFactorService
{
    /**
     * ToggleTwoFactorService constructor.
     */
    public function __construct(
        private ConnectionInterface $connection,
        private Encrypter $encrypter,
        private Google2FA $google2FA,
        private RecoveryTokenRepository $recoveryTokenRepository,
        private UserRepositoryInterface $repository,
    ) {
    }

    /**
     * Toggle 2FA on an account only if the token provided is valid.
     *
     * @throws \Throwable
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     * @throws TwoFactorAuthenticationTokenInvalid
     */
    public function handle(User $user, string $token, ?bool $toggleState = null): array
    {
        $secret = $this->encrypter->decrypt($user->totp_secret);

        $isValidToken = $this->google2FA->verifyKey($secret, $token, config()->get('pterodactyl.auth.2fa.window'));

        if (!$isValidToken) {
            throw new TwoFactorAuthenticationTokenInvalid();
        }

        return $this->connection->transaction(function () use ($user, $toggleState) {
             
             
             
             
            // If a user is unable to login with a 2FA token they can provide one of these backup codes
             
             
            $tokens = [];
            if ((!$toggleState && !$user->use_totp) || $toggleState) {
                $inserts = [];
                for ($i = 0; $i < 10; ++$i) {
                    $token = Str::random(10);

                    $inserts[] = [
                        'user_id' => $user->id,
                        'token' => password_hash($token, PASSWORD_DEFAULT),
                        // insert() won't actually set the time on the models, so make sure we do this
                         
                        'created_at' => Carbon::now(),
                    ];

                    $tokens[] = $token;
                }

                 
                 
                $this->recoveryTokenRepository->deleteWhere(['user_id' => $user->id]);

                 
                $this->recoveryTokenRepository->insert($inserts);
            }

            $this->repository->withoutFreshModel()->update($user->id, [
                'totp_authenticated_at' => null,
                'use_totp' => (is_null($toggleState) ? !$user->use_totp : $toggleState),
            ]);

            return $tokens;
        });
    }
}
