<?php

namespace PrasadChinwal\Box\Test\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PrasadChinwal\Box\Test\TestCase;

class BoxFolderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(config('box.token_cache_key'));
    }

    public function test_it_uses_the_folders_root_endpoint_when_creating_a_folder(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::response($this->fakeTokenResponse(), 200),
            'https://api.box.com/2.0/folders/' => Http::response([
                'id' => '999',
                'type' => 'folder',
            ], 201),
        ]);

        app('box')->folder()->whereId('4321')->create([
            'name' => 'New Folder',
            'parent' => ['id' => '4321'],
        ]);

        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://api.box.com/2.0/folders/';
        });
    }

    public function test_it_uses_the_copy_endpoint_when_copying_a_folder(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::response($this->fakeTokenResponse(), 200),
            'https://api.box.com/2.0/folders/4321/copy' => Http::response([
                'id' => '999',
                'type' => 'folder',
            ], 201),
        ]);

        app('box')->folder()->whereId('4321')->copy([
            'parent' => ['id' => '7654'],
        ]);

        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://api.box.com/2.0/folders/4321/copy';
        });
    }

    private function fakeTokenResponse(): array
    {
        return [
            'access_token' => 'folder-token',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ];
    }
}
