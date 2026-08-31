<?php

namespace PrasadChinwal\Box\Test\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PrasadChinwal\Box\File\BoxFile;
use PrasadChinwal\Box\Test\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BoxTokenCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(config('box.token_cache_key'));
    }

    #[Test]
    public function it_reuses_the_cached_access_token_until_it_expires(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::response([
                'access_token' => 'cached-token',
                'expires_in' => 3600,
                'token_type' => 'bearer',
            ], 200),
            'https://api.box.com/2.0/files/1234' => Http::response($this->fakeBoxFileResponse(), 200),
        ]);

        (new BoxFile())->whereId('1234')->info();
        (new BoxFile())->whereId('1234')->info();

        Http::assertSentCount(3);
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.box.com/2.0/files/1234'
                && $request->hasHeader('Authorization', ['Bearer cached-token']);
        });
    }

    #[Test]
    public function it_requests_a_fresh_access_token_after_the_cached_token_expires(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::sequence()
                ->push([
                    'access_token' => 'first-token',
                    'expires_in' => 1,
                    'token_type' => 'bearer',
                ], 200)
                ->push([
                    'access_token' => 'second-token',
                    'expires_in' => 3600,
                    'token_type' => 'bearer',
                ], 200),
            'https://api.box.com/2.0/files/1234' => Http::response($this->fakeBoxFileResponse(), 200),
        ]);

        (new BoxFile())->whereId('1234')->info();

        sleep(2);

        (new BoxFile())->whereId('1234')->info();

        Http::assertSentCount(4);
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.box.com/2.0/files/1234'
                && $request->hasHeader('Authorization', ['Bearer second-token']);
        });
    }

    private function fakeBoxFileResponse(): array
    {
        return [
            'id' => '1234',
            'type' => 'file',
            'file_version' => ['id' => '1', 'type' => 'file_version', 'sha1' => 'abc123'],
            'sequence_id' => '1',
            'etag' => '1',
            'sha1' => 'abc123',
            'name' => 'test.pdf',
            'description' => 'description',
            'size' => 1233332,
            'path_collection' => ['total_count' => 1, 'entries' => []],
            'trashed_at' => null,
            'purged_at' => null,
            'content_created_at' => '2024-01-01T00:00:00Z',
            'content_modified_at' => '2024-01-01T00:00:00Z',
            'created_by' => ['id' => '1', 'type' => 'user', 'name' => 'Test User', 'login' => 'test@example.com'],
            'modified_by' => ['id' => '1', 'type' => 'user', 'name' => 'Test User', 'login' => 'test@example.com'],
            'owned_by' => ['id' => '1', 'type' => 'user', 'name' => 'Test User', 'login' => 'test@example.com'],
            'shared_link' => null,
            'parent' => ['id' => '0', 'type' => 'folder', 'name' => 'All Files'],
            'item_status' => 'active',
        ];
    }
}
