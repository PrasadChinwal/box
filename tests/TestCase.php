<?php

namespace PrasadChinwal\Box\Test;

use Orchestra\Testbench\TestCase as BaseTestCase;
use PrasadChinwal\Box\BoxServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            BoxServiceProvider::class,
            LaravelDataServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('box.auth_method', 'client_credentials');
        $app['config']->set('box.client_id', '123');
        $app['config']->set('box.client_secret', '456');
        $app['config']->set('box.enterprise_id', '1234');
        $app['config']->set('box.public_key_id', '1111');
        $app['config']->set('box.private_key', 'xyz');
        $app['config']->set('box.passphrase', 'test@1234');
        $app['config']->set('box.token_cache_key', 'box.testing.access_token');
        $app['config']->set('box.token_expiry_buffer', 0);
        $app['config']->set('box.request_timeout', 30);
        $app['config']->set('box.request_retry_times', 0);
        $app['config']->set('box.request_retry_sleep', 100);
    }
}
