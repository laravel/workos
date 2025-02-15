<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Laravel\WorkOS\WorkOSServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [WorkOSServiceProvider::class];
    }
}
