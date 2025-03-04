<?php

namespace Laravel\WorkOS\Http\Requests;

use App\Models\User as AppUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Laravel\WorkOS\User;
use Laravel\WorkOS\WorkOS;
use WorkOS\UserManagement;

class AuthKitAuthenticationRequest extends FormRequest
{
    /**
     * Redirect the user to WorkOS for authentication.
     */
    public function authenticate(?callable $findUsing = null, ?callable $createUsing = null, ?callable $updateUsing = null): mixed
    {
        WorkOS::configure();

        $this->ensureStateIsValid();

        $findUsing ??= $this->findUsing(...);
        $createUsing ??= $this->createUsing(...);
        $updateUsing ??= $this->updateUsing(...);

        $user = (new UserManagement)->authenticateWithCode(
            config('services.workos.client_id'),
            $this->query('code'),
        );

        [$user, $accessToken, $refreshToken, $organizationId] = [
            $user->user,
            $user->access_token,
            $user->refresh_token,
            $user->organizationId,
        ];

        $user = new User(
            id: $user->id,
            organizationId: $organizationId,
            firstName: $user->firstName,
            lastName: $user->lastName,
            email: $user->email,
            avatar: $user->profilePictureUrl,
        );

        $existingUser = $findUsing($user->id);

        if (! $existingUser) {
            $existingUser = $createUsing($user);

            event(new Registered($existingUser));
        } elseif (! is_null($updateUsing)) {
            $existingUser = $updateUsing($existingUser, $user);
        }

        Auth::guard('web')->login($existingUser);

        $this->session()->put('workos_access_token', $accessToken);
        $this->session()->put('workos_refresh_token', $refreshToken);

        $this->session()->regenerate();

        return $existingUser;
    }

    /**
     * Find the user with the given WorkOS ID.
     */
    protected function findUsing(string $id): ?AppUser
    {
        return AppUser::where('workos_id', $id)->first();
    }

    /**
     * Create a user from the given WorkOS user.
     */
    protected function createUsing(User $user): AppUser
    {
        return AppUser::create([
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
    protected function updateUsing(AppUser $user, User $userFromWorkOS): AppUser
    {
        return tap($user)->update([
            // 'name' => $userFromWorkOS->firstName.' '.$userFromWorkOS->lastName,
            'avatar' => $userFromWorkOS->avatar ?? '',
        ]);
    }

    /**
     * Ensure the request state is valid.
     */
    protected function ensureStateIsValid(): void
    {
        $state = json_decode($this->query('state'), true)['state'] ?? false;

        if ($state !== $this->session()->get('state')) {
            abort(403);
        }

        $this->session()->forget('state');
    }
}
