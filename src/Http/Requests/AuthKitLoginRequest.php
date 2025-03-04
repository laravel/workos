<?php

namespace Laravel\WorkOS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Inertia\Inertia;
use Laravel\WorkOS\WorkOS;
use Symfony\Component\HttpFoundation\Response;
use WorkOS\UserManagement;

class AuthKitLoginRequest extends FormRequest
{
    /**
     * Redirect the user to WorkOS for authentication.
     */
    public function redirect(): Response
    {
        WorkOS::configure();

        $url = (new UserManagement)->getAuthorizationUrl(
            config('services.workos.redirect_url'),
            ['state' => $state = base64_encode(url()->previous())],
            'authkit',
        );

        $this->session()->put('state', $state);

        return class_exists(Inertia::class)
            ? Inertia::location($url)
            : redirect($url);
    }
}
