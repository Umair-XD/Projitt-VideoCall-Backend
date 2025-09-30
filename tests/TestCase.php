<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure app key and JWT secret are set without committing secrets
        if (empty(config('app.key'))) {
            $random = base64_encode(random_bytes(32));
            config(['app.key' => 'base64:'.$random]);
        }

        if (empty(config('jwt.secret'))) {
            config(['jwt.secret' => Str::random(64)]);
        }
    }
}
