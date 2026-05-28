<?php

namespace Laravel\WorkOS\Http\Requests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\FormRequest;
use Laravel\WorkOS\User;
use Laravel\WorkOS\WorkOS;
use RuntimeException;
use WorkOS\Service\UserManagement;

class AuthKitEmailChangeRequest extends FormRequest
{
    /**
     * Send a WorkOS email change code to the given email address.
     */
    public function send(string $email): mixed
    {
        $user = $this->workOsUser();

        return $this->userManagement()->sendEmailChange(
            id: $user->workos_id,
            newEmail: $email,
        );
    }

    /**
     * Confirm a WorkOS email change and update the application user.
     */
    public function confirm(string $code, ?callable $updateUsing = null): mixed
    {
        $user = $this->workOsUser();

        $response = $this->userManagement()->confirmEmailChange(
            id: $user->workos_id,
            code: $code,
        );

        $updateUsing ??= $this->updateUsing(...);

        return $updateUsing($user, $this->userFromWorkOS($response->user));
    }

    /**
     * Update the application user from the confirmed WorkOS email change.
     */
    protected function updateUsing(Authenticatable $user, User $userFromWorkOS): Authenticatable
    {
        return tap($user)->update([
            'email' => $userFromWorkOS->email,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Create a Laravel WorkOS user DTO from a WorkOS SDK user resource.
     */
    protected function userFromWorkOS(object $user): User
    {
        return new User(
            id: $user->id,
            organizationId: null,
            firstName: $user->firstName,
            lastName: $user->lastName,
            email: $user->email,
            avatar: $user->profilePictureUrl,
        );
    }

    /**
     * Get the authenticated user with a WorkOS ID.
     */
    protected function workOsUser(): Authenticatable
    {
        $user = $this->user();

        if (! $user || empty($user->workos_id)) {
            throw new RuntimeException('The authenticated user does not have a WorkOS ID.');
        }

        return $user;
    }

    /**
     * Get the WorkOS user management client.
     */
    protected function userManagement(): UserManagement
    {
        return WorkOS::client()->userManagement();
    }
}
