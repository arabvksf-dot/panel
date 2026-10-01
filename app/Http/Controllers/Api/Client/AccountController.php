<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Pterodactyl\Facades\Activity;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Pterodactyl\Services\Users\UserDeletionService;
use Pterodactyl\Exceptions\Http\Base\InvalidPasswordProvidedException;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Transformers\Api\Client\AccountTransformer;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdateEmailRequest;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdatePasswordRequest;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class AccountController extends ClientApiController
{
    /**
     * The number of seconds that must elapse before the email change throttle resets.
     */
    private const EMAIL_UPDATE_THROTTLE = 60 * 60 * 24;

    /**
     * AccountController constructor.
     */
    public function __construct(private AuthManager $manager, private UserUpdateService $updateService)
    {
        parent::__construct();
    }

    public function index(Request $request): array
    {
        return $this->fractal->item($request->user())
            ->transformWith($this->getTransformer(AccountTransformer::class))
            ->toArray();
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $rules = [
            'language' => ['required', 'string', Rule::in(array_keys($request->user()->getAvailableLanguages()))],
            'onboarding_completed' => ['required', 'boolean'],
        ];

        if (config('features.appearance', true)) {
            $rules += [
                'appearance' => ['required', 'array:theme,accent,motion,font_size'],
                'appearance.theme' => ['required', Rule::in(config('site.appearance.themes'))],
                'appearance.accent' => ['required', Rule::in(config('site.appearance.accents'))],
                'appearance.motion' => ['required', 'boolean'],
                'appearance.font_size' => [
                'required',
                'integer',
                'between:' . config('site.appearance.font_sizes.min') . ',' . config('site.appearance.font_sizes.max'),
                ],
            ];
        }

        $validated = $request->validate($rules);

        $updates = [
            'language' => $validated['language'],
            'onboarding_completed' => $validated['onboarding_completed'],
        ];
        if (config('features.appearance', true)) {
            $updates['appearance'] = $validated['appearance'];
        }

        $user = $request->user();
        $user->forceFill($updates)->save();
        Activity::event('user:account.preferences-updated')->subject($user)->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    public function hostingStatus(Request $request): JsonResponse
    {
        $hasActiveHosting = $request->user()->servers()
            ->whereNotNull('installed_at')
            ->where(function ($query) {
                $query->whereNull('status')->orWhereNotIn('status', [
                    \Pterodactyl\Models\Server::STATUS_INSTALLING,
                    \Pterodactyl\Models\Server::STATUS_INSTALL_FAILED,
                    \Pterodactyl\Models\Server::STATUS_SUSPENDED,
                    \Pterodactyl\Models\Server::STATUS_RESTORING_BACKUP,
                ]);
            })
            ->exists();

        return new JsonResponse(['has_active_hosting' => $hasActiveHosting]);
    }

    public function deleteAccount(Request $request, UserDeletionService $deletionService): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'max:4096'],
        ]);
        $user = $request->user();

        if (!Hash::check($validated['password'], $user->password)) {
            throw new InvalidPasswordProvidedException(trans('validation.internal.invalid_password'));
        }

        Activity::event('user:account.deleted')->subject($user)->transaction(function () use ($user, $deletionService) {
            $user->tokens()->delete();
            $deletionService->handle($user);
        });

        $this->manager->guard()->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Update the authenticated user's email address.
     */
    public function updateEmail(UpdateEmailRequest $request): JsonResponse
    {
        $user = $request->user();
         
         
         
        if (RateLimiter::tooManyAttempts($key = "user:update-email:{$user->uuid}", 3)) {
            throw new TooManyRequestsHttpException(message: 'Your email address has been changed too many times today. Please try again later.');
        }

        $original = $user->email;
        if (mb_strtolower($original) !== mb_strtolower($request->validated('email'))) {
            RateLimiter::hit($key, self::EMAIL_UPDATE_THROTTLE);

            $this->updateService->handle($user, $request->validated());

            Activity::event('user:account.email-changed')
                ->property(['old' => $original, 'new' => $request->validated('email')])
                ->log();
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Update the authenticated user's password. All existing sessions will be logged
     * out immediately.
     *
     * @throws \Throwable
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = Activity::event('user:account.password-changed')->transaction(function () use ($request) {
            return $this->updateService->handle($request->user(), $request->validated());
        });

        $guard = $this->manager->guard();
        // If you do not update the user in the session you'll end up working with a
         
         
         
        $guard->setUser($user);

         
        if (method_exists($guard, 'logoutOtherDevices')) { // @phpstan-ignore function.alreadyNarrowedType
            $guard->logoutOtherDevices($request->input('password'));
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
