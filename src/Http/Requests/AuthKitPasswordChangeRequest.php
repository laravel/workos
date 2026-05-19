<?php

namespace Laravel\WorkOS\Http\Requests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\FormRequest;
use Laravel\WorkOS\WorkOS;
use RuntimeException;
use WorkOS\Service\UserManagement;

class AuthKitPasswordChangeRequest extends FormRequest
{
    /**
     * Send a WorkOS password reset email to the authenticated user.
     */
    public function send(): mixed
    {
        $user = $this->workOsUser();
        $userManagement = $this->userManagement();

        $userFromWorkOS = $userManagement->getUser(
            id: $user->workos_id,
        );

        return $userManagement->resetPassword(
            email: $userFromWorkOS->email,
        );
    }

    /**
     * Get the WorkOS user management client.
     */
    protected function userManagement(): UserManagement
    {
        return WorkOS::client()->userManagement();
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
}
