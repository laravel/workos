<?php

use Laravel\WorkOS\Http\Requests\AuthKitAuthenticationRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

function authenticationRequest(array $query, ?string $sessionState): AuthKitAuthenticationRequest
{
    $request = AuthKitAuthenticationRequest::create('/authenticate', 'GET', $query);
    $request->setLaravelSession(app('session.store'));

    if (! is_null($sessionState)) {
        $request->session()->put('state', json_encode([
            'state' => $sessionState,
            'previous_url' => base64_encode('/'),
        ]));
    }

    return $request;
}

it('rejects the callback when the state is invalid', function (array $query, ?string $sessionState) {
    $request = authenticationRequest(['code' => 'code_123', ...$query], $sessionState);

    expect(fn () => $request->authenticate())->toThrow(
        fn (HttpException $e) => expect($e->getStatusCode())->toBe(403),
    );
})->with([
    'state missing from the request and the session' => [[], null],
    'null state and no session state' => [['state' => 'null'], null],
    'state present but no session state' => [['state' => json_encode(['state' => 'abc'])], null],
    'state missing from the request' => [[], 'abc'],
    'mismatched state' => [['state' => json_encode(['state' => 'xyz'])], 'abc'],
    'state passed as an array' => [['state' => ['state' => 'abc']], 'abc'],
]);
