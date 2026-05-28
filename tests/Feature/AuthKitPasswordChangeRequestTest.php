<?php

use Laravel\WorkOS\Http\Requests\AuthKitPasswordChangeRequest;
use Tests\Fixtures\FakeUser;
use Tests\Fixtures\WorkOSResourceFactory;
use WorkOS\Resource\PasswordReset;
use WorkOS\Resource\User;
use WorkOS\Service\UserManagement;

beforeEach(function () {
    $this->userManagement = Mockery::mock(UserManagement::class);

    $this->user = new FakeUser(email: 'stale@example.com');

    $this->request = new class extends AuthKitPasswordChangeRequest
    {
        public UserManagement $userManagement;

        protected function userManagement(): UserManagement
        {
            return $this->userManagement;
        }
    };

    $this->request->userManagement = $this->userManagement;
    $this->request->setUserResolver(fn () => $this->user);
});

afterEach(function () {
    Mockery::close();
});

it('sends a password reset email to the current WorkOS email address', function () {
    $userFromWorkOS = User::fromArray(WorkOSResourceFactory::userArray([
        'email' => 'current@example.com',
    ]));

    $passwordReset = PasswordReset::fromArray([
        'object' => 'password_reset',
        'id' => 'password_reset_123',
        'user_id' => 'user_123',
        'email' => 'current@example.com',
        'expires_at' => '2026-05-19T12:15:00.000Z',
        'created_at' => '2026-05-19T12:00:00.000Z',
        'password_reset_token' => 'token_123',
        'password_reset_url' => 'https://authkit.example.com/reset-password?token=token_123',
    ]);

    $this->userManagement
        ->shouldReceive('getUser')
        ->once()
        ->with('user_123')
        ->andReturn($userFromWorkOS);

    $this->userManagement
        ->shouldReceive('resetPassword')
        ->once()
        ->with('current@example.com')
        ->andReturn($passwordReset);

    expect($this->request->send())->toBe($passwordReset);
});

it('requires the authenticated user to have a WorkOS ID', function () {
    $this->request->setUserResolver(fn () => new FakeUser(workos_id: null));

    $this->request->send();
})->throws(RuntimeException::class, 'The authenticated user does not have a WorkOS ID.');
