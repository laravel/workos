<?php

namespace Laravel\WorkOS\Http\Requests;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Laravel\WorkOS\User;
use Laravel\WorkOS\WorkOS;
use Symfony\Component\HttpFoundation\Response;

class AuthKitAuthenticationRequest extends FormRequest
{
    /**
     * Redirect the user to WorkOS for authentication.
     */
    public function authenticate(?callable $findUsing = null, ?callable $createUsing = null, ?callable $updateUsing = null): mixed
    {
        $this->ensureStateIsValid();

        $findUsing ??= $this->findUsing(...);
        $createUsing ??= $this->createUsing(...);
        $updateUsing ??= $this->updateUsing(...);

        $response = WorkOS::client()->userManagement()->authenticateWithCode(
            code: $this->query('code'),
        );

        $user = new User(
            id: $response->user->id,
            organizationId: $response->organizationId,
            firstName: $response->user->firstName,
            lastName: $response->user->lastName,
            email: $response->user->email,
            avatar: $response->user->profilePictureUrl,
        );

        $existingUser = $findUsing($user);

        if (! $existingUser) {
            $existingUser = $createUsing($user);

            event(new Registered($existingUser));
        } elseif (! is_null($updateUsing)) {
            $existingUser = $updateUsing($existingUser, $user);
        }

        Auth::guard('web')->login($existingUser);

        $this->session()->put('workos_access_token', $response->accessToken);
        $this->session()->put('workos_refresh_token', $response->refreshToken);

        $this->session()->regenerate();

        return $existingUser;
    }

    /**
     * Find the user with the given WorkOS ID.
     */
    protected function findUsing(User $user): ?Authenticatable
    {
        $userModelClass = Config::get('auth.providers.users.model');

        return $userModelClass::where('workos_id', $user->id)->first();
    }

    /**
     * Create a user from the given WorkOS user.
     */
    protected function createUsing(User $user): Authenticatable
    {
        $userModelClass = Config::get('auth.providers.users.model');

        return $userModelClass::create([
            'name' => $user->firstName.' '.$user->lastName,
            'email' => $user->email,
            'email_verified_at' => now(),
            'workos_id' => $user->id,
            'avatar' => $user->avatar ?? '',
        ]);
    }

    /**
     * Update a user from the given WorkOS user.
     */
    protected function updateUsing(Authenticatable $user, User $userFromWorkOS): Authenticatable
    {
        return tap($user)->update([
            // 'name' => $userFromWorkOS->firstName.' '.$userFromWorkOS->lastName,
            'avatar' => $userFromWorkOS->avatar ?? '',
        ]);
    }

    /**
     * Redirect the user to the previous URL or a default URL if no previous URL is available.
     */
    public function redirect(string $default = '/'): Response
    {
        $previousUrl = rtrim(base64_decode($this->sessionState()['previous_url'] ?? '/')) ?: null;

        $to = ! is_null($previousUrl) && $previousUrl !== URL::to('/')
            ? $previousUrl
            : $default;

        return class_exists(Inertia::class)
            ? Inertia::location($to)
            : redirect($to);
    }

    /**
     * Ensure the request state is valid.
     */
    protected function ensureStateIsValid(): void
    {
        $state = json_decode($this->query('state'), true)['state'] ?? false;

        if ($state !== ($this->sessionState()['state'] ?? false)) {
            abort(403);
        }

        $this->session()->forget('state');
    }

    /**
     * Get the session state.
     */
    protected function sessionState(): array
    {
        return json_decode($this->session()->get('state'), true) ?: [];
    }
}
