<?php

namespace PrasadChinwal\Box\Test\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PrasadChinwal\Box\BoxUser;
use PrasadChinwal\Box\Exceptions\AuthenticationException;
use PrasadChinwal\Box\Exceptions\ConfigurationException;
use PrasadChinwal\Box\Exceptions\OperationException;
use PrasadChinwal\Box\File\BoxFile;
use PrasadChinwal\Box\Test\TestCase;

class BoxExceptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(config('box.token_cache_key'));
    }

    public function test_it_throws_a_configuration_exception_for_an_unsupported_auth_method(): void
    {
        config()->set('box.auth_method', 'legacy');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Unsupported Box auth method [legacy].');

        new BoxFile();
    }

    public function test_it_throws_an_authentication_exception_when_box_authentication_fails(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::response([
                'type' => 'error',
                'status' => 401,
                'code' => 'unauthorized',
            ], 401),
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Unable to authenticate with Box.');

        new BoxFile();
    }

    public function test_it_wraps_deprovision_failures_in_an_operation_exception(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::response([
                'access_token' => 'user-token',
                'expires_in' => 3600,
                'token_type' => 'bearer',
            ], 200),
            'https://api.box.com/2.0/users/111/folders/0' => Http::response([
                'type' => 'error',
                'status' => 500,
                'code' => 'internal_server_error',
            ], 500),
        ]);

        $this->expectException(OperationException::class);
        $this->expectExceptionMessage('Could not deprovision the Box user.');

        (new BoxUser())->deprovision('111', '222');
    }
}
