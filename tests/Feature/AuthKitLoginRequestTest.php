<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Laravel\WorkOS\Http\Requests\AuthKitLoginRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;

beforeEach(function () {
    Config::set('services.workos.client_id', 'test_client_id');
    Config::set('services.workos.secret', 'test_secret');
    Config::set('services.workos.redirect_url', 'https://laravel.com/authenticate');

    $this->request = AuthKitLoginRequest::create('/', 'GET');
    $this->request->setLaravelSession(app('session.store'));
});

it('redirects to WorkOS without screen hint', function () {
    $response = $this->request->redirect();

    expect($response)->toBeInstanceOf(RedirectResponse::class);

    expect($response->headers->get('Location'))->toContain('https://api.workos.com/user_management/authorize')
        ->toContain('client_id=test_client_id')
        ->toContain('response_type=code')
        ->toContain('redirect_uri='.urlencode('https://laravel.com/authenticate'))
        ->toContain('provider=authkit')
        ->not->toContain('screen_hint');
});

it('redirects to WorkOS with sign-in screen hint', function () {
    $response = $this->request->redirect(['screenHint' => 'sign-in']);

    expect($response)->toBeInstanceOf(RedirectResponse::class);

    expect($response->headers->get('Location'))
        ->toContain('https://api.workos.com/user_management/authorize')
        ->toContain('screen_hint=sign-in');
});

it('redirects to WorkOS with sign-up screen hint', function () {
    $response = $this->request->redirect(['screenHint' => 'sign-up']);

    expect($response)->toBeInstanceOf(RedirectResponse::class);

    expect($response->headers->get('Location'))
        ->toContain('https://api.workos.com/user_management/authorize')
        ->toContain('screen_hint=sign-up');
});

it('stores state in session', function () {
    $response = $this->request->redirect();

    $sessionState = Session::get('state');
    expect($sessionState)->not->toBeNull();

    $decodedState = json_decode($sessionState, true);

    expect($decodedState)
        ->toHaveKey('state')
        ->toHaveKey('previous_url')
        ->and($decodedState['state'])->toHaveLength(20);
});

it('includes state in the authorization URL', function () {
    $response = $this->request->redirect();

    $location = $response->headers->get('Location');
    expect($location)->toContain('state=');

    parse_str(parse_url($location, PHP_URL_QUERY), $queryParams);

    expect($queryParams)->toHaveKey('state');

    $sessionState = json_decode(Session::get('state'), true);
    $urlState = json_decode($queryParams['state'], true);

    expect($urlState)->toBe($sessionState);
});

it('passes all parameters correctly to getAuthorizationUrl', function () {
    $response = $this->request->redirect(['screenHint' => 'sign-up']);

    expect($response->headers->get('Location'))->not->toBeNull()
        ->toBeString()
        ->toContain('https://api.workos.com/user_management/authorize')
        ->toContain('client_id=test_client_id')
        ->toContain('response_type=code')
        ->toContain('redirect_uri='.urlencode('https://laravel.com/authenticate'))
        ->toContain('provider=authkit')
        ->toContain('screen_hint=sign-up')
        ->toContain('state=');
});

it('supports domain hint parameter', function () {
    $response = $this->request->redirect(['domainHint' => 'laravel.com']);

    expect($response->headers->get('Location'))
        ->toContain('domain_hint=laravel.com');
});

it('supports login hint parameter', function () {
    $response = $this->request->redirect(['loginHint' => 'francisco@laravel.com']);

    expect($response->headers->get('Location'))
        ->toContain('login_hint='.urlencode('francisco@laravel.com'));
});

it('uses default redirect URL when not specified', function () {
    $response = $this->request->redirect();

    expect($response->headers->get('Location'))
        ->toContain('redirect_uri='.urlencode('https://laravel.com/authenticate'));
});

it('uses custom redirect URL when specified', function () {
    $response = $this->request->redirect(['redirectUrl' => 'https://custom.laravel.com/authenticate']);

    expect($response->headers->get('Location'))
        ->toContain('redirect_uri='.urlencode('https://custom.laravel.com/authenticate'));
});

it('supports multiple parameters at once', function () {
    $response = $this->request->redirect([
        'screenHint' => 'sign-in',
        'domainHint' => 'laravel.com',
        'loginHint' => 'francisco@laravel.com',
        'redirectUrl' => 'https://custom.laravel.com/authenticate',
    ]);

    expect($response->headers->get('Location'))->toContain('screen_hint=sign-in')
        ->toContain('domain_hint=laravel.com')
        ->toContain('login_hint='.urlencode('francisco@laravel.com'))
        ->toContain('redirect_uri='.urlencode('https://custom.laravel.com/authenticate'));
});
