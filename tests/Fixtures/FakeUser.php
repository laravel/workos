<?php

namespace Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

class FakeUser extends Authenticatable
{
    public array $updated = [];

    public function __construct(
        public ?string $workos_id = 'user_123',
        public string $email = 'old@example.com',
    ) {
        parent::__construct();
    }

    public function update(array $attributes = [], array $options = [])
    {
        $this->updated = array_merge($this->updated, $attributes);

        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }

        return true;
    }
}
