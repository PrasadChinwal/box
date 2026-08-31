<?php

namespace PrasadChinwal\Box\Test\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PrasadChinwal\Box\Test\TestCase;

class BoxFileDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(config('box.token_cache_key'));
    }

    public function test_it_accepts_a_204_response_when_deleting_a_file(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::response([
                'access_token' => 'delete-token',
                'expires_in' => 3600,
                'token_type' => 'bearer',
            ], 200),
            'https://api.box.com/2.0/files/1234' => Http::response('', 204),
        ]);

        $response = app('box')->file()->whereId('1234')->delete();

        $this->assertSame('File has been deleted successfully', $response->getContent());
    }
}
