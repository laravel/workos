<?php

use Laravel\WorkOS\Http\Requests\AuthKitEmailChangeRequest;
use Laravel\WorkOS\User as LaravelWorkOSUser;
use Tests\Fixtures\FakeUser;
use Tests\Fixtures\WorkOSResourceFactory;
use WorkOS\Resource\EmailChange;
use WorkOS\Resource\EmailChangeConfirmation;
use WorkOS\Service\UserManagement;

beforeEach(function () {
    $this->userManagement = Mockery::mock(UserManagement::class);

    $this->user = new FakeUser;

    $this->request = new class extends AuthKitEmailChangeRequest
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

it('sends a WorkOS email change code', function () {
    $emailChange = EmailChange::fromArray([
        'object' => 'email_change',
        'user' => WorkOSResourceFactory::userArray(),
        'new_email' => 'new@example.com',
        'expires_at' => '2026-05-19T12:15:00.000Z',
        'created_at' => '2026-05-19T12:00:00.000Z',
    ]);

    $this->userManagement
        ->shouldReceive('sendEmailChange')
        ->once()
        ->with('user_123', 'new@example.com')
        ->andReturn($emailChange);

    expect($this->request->send('new@example.com'))->toBe($emailChange);
});

it('confirms a WorkOS email change and updates the authenticated user', function () {
    $emailChangeConfirmation = EmailChangeConfirmation::fromArray([
        'object' => 'email_change_confirmation',
        'user' => WorkOSResourceFactory::userArray(['email' => 'new@example.com']),
    ]);

    $this->userManagement
        ->shouldReceive('confirmEmailChange')
        ->once()
        ->with('user_123', '123456')
        ->andReturn($emailChangeConfirmation);

    $user = $this->request->confirm('123456');

    expect($user)->toBe($this->user)
        ->and($this->user->email)->toBe('new@example.com')
        ->and($this->user->updated)->toHaveKey('email')
        ->and($this->user->updated)->toHaveKey('email_verified_at');
});

it('allows customizing how confirmed email changes update the authenticated user', function () {
    $emailChangeConfirmation = EmailChangeConfirmation::fromArray([
        'object' => 'email_change_confirmation',
        'user' => WorkOSResourceFactory::userArray([
            'first_name' => 'Taylor',
            'last_name' => 'Otwell',
            'email' => 'new@example.com',
        ]),
    ]);

    $this->userManagement
        ->shouldReceive('confirmEmailChange')
        ->once()
        ->with('user_123', '123456')
        ->andReturn($emailChangeConfirmation);

    $result = $this->request->confirm('123456', function (FakeUser $user, LaravelWorkOSUser $userFromWorkOS) {
        $user->update([
            'email' => $userFromWorkOS->email,
            'name' => $userFromWorkOS->firstName.' '.$userFromWorkOS->lastName,
        ]);

        return 'updated';
    });

    expect($result)->toBe('updated')
        ->and($this->user->email)->toBe('new@example.com')
        ->and($this->user->name)->toBe('Taylor Otwell');
});

it('requires the authenticated user to have a WorkOS ID', function () {
    $this->request->setUserResolver(fn () => new FakeUser(workos_id: null));

    $this->request->send('new@example.com');
})->throws(RuntimeException::class, 'The authenticated user does not have a WorkOS ID.');
