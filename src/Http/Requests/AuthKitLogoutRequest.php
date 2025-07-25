<?php

namespace Laravel\WorkOS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\WorkOS\WorkOS;
use Symfony\Component\HttpFoundation\Response;
use WorkOS\UserManagement;

class AuthKitLogoutRequest extends FormRequest
{
    /**
     * Redirect the user to WorkOS for authentication.
     */
    public function logout(?string $returnTo = null): Response
    {
        $accessToken = $this->session()->get('workos_access_token');

        $workOsSession = $accessToken
            ? WorkOS::decodeAccessToken($accessToken)
            : false;

        Auth::guard('web')->logout();

        $this->session()->invalidate();
        $this->session()->regenerateToken();

        if (! $workOsSession) {
            return redirect($returnTo ?? '/');
        }

        $logoutUrl = (new UserManagement)->getLogoutUrl(
            $workOsSession['sid'],
            $returnTo ? url($returnTo) : null,
        );

        return class_exists(Inertia::class)
            ? Inertia::location($logoutUrl)
            : redirect($logoutUrl);
    }
}
