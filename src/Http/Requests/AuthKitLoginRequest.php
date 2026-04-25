<?php

namespace Laravel\WorkOS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\WorkOS\WorkOS;
use Symfony\Component\HttpFoundation\Response;

class AuthKitLoginRequest extends FormRequest
{
    /**
     * Redirect the user to WorkOS for authentication.
     *
     * @param  array{
     *     screenHint?: 'sign-in'|'sign-up',
     *     domainHint?: string,
     *     loginHint?: string,
     *     redirectUrl?: string,
     * }  $options
     */
    public function redirect(array $options = []): Response
    {
        $state = [
            'state' => Str::random(20),
            'previous_url' => base64_encode(URL::previous()),
        ];

        $params = array_filter([
            'client_id' => WorkOS::clientId(),
            'response_type' => 'code',
            'redirect_uri' => $options['redirectUrl'] ?? WorkOS::redirectUrl(),
            'provider' => 'authkit',
            'state' => json_encode($state),
            'domain_hint' => $options['domainHint'] ?? null,
            'login_hint' => $options['loginHint'] ?? null,
            'screen_hint' => $options['screenHint'] ?? null,
        ], fn ($value) => $value !== null);

        $url = WorkOS::baseUrl().'/user_management/authorize?'.http_build_query($params);

        $this->session()->put('state', json_encode($state));

        return class_exists(Inertia::class)
            ? Inertia::location($url)
            : redirect($url);
    }
}
