<?php

namespace Laravel\WorkOS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
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
            $state = [
                'state' => Str::random(20),
                'previous_url' => base64_encode(URL::previous()),
            ],
            'authkit',
        );

        $this->session()->put('state', json_encode($state));

        return class_exists(Inertia::class)
            ? Inertia::location($url)
            : redirect($url);
    }
}
