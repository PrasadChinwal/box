<?php

namespace PrasadChinwal\Box\Test;

use Closure;
use Orchestra\Testbench\TestCase as Orchestra;
use PrasadChinwal\Box\BoxServiceProvider;
use UisIts\Oidc\ShibbolethServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            BoxServiceProvider::class
        ];
    }

    /**
     * @param $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('box.client_id', '123');
        $app['config']->set('box.client_secret', '456');
        $app['config']->set('box.enterprise_id', '1234');
        $app['config']->set('box.public_key_id', '1111');
        $app['config']->set('box.private_key_file', 'xyz');
        $app['config']->set('box.passphrase', 'test@1234');
    }
}
