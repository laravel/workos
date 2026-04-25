<?php

namespace Laravel\WorkOS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\WorkOS\WorkOS;
use Symfony\Component\HttpFoundation\Response;

class AuthKitLogoutRequest extends FormRequest
{
    /**
     * Redirect the user to WorkOS for authentication.
     */
    public function logout(?string $redirectTo = null): Response
    {
        $accessToken = $this->session()->get('workos_access_token');

        $workOsSession = $accessToken
            ? WorkOS::decodeAccessToken($accessToken)
            : false;

        Auth::guard('web')->logout();

        $this->session()->invalidate();
        $this->session()->regenerateToken();

        if (! $workOsSession) {
            return redirect($redirectTo ?? '/');
        }

        $params = array_filter([
            'session_id' => $workOsSession['sid'],
            'return_to' => $redirectTo ? url($redirectTo) : null,
        ], fn ($value) => $value !== null);

        $logoutUrl = WorkOS::baseUrl().'/user_management/sessions/logout?'.http_build_query($params);

        return class_exists(Inertia::class)
            ? Inertia::location($logoutUrl)
            : redirect($logoutUrl);
    }
}
