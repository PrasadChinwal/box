<?php

namespace PrasadChinwal\Box\Test\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PrasadChinwal\Box\File\BoxFile;
use PrasadChinwal\Box\Test\TestCase;

class BoxFileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(config('box.token_cache_key'));
    }

    public function test_it_can_retrieve_file_information(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::response([
                'access_token' => 'abcdefghi123456789',
                'expires_in' => 3600,
                'token_type' => 'bearer',
            ], 200),
            'https://api.box.com/2.0/files/1234' => Http::response($this->fakeBoxFileResponse(), 200),
        ]);

        $file = (new BoxFile)->whereId('1234')->info();

        $this->assertSame('1234', $file->id);
        $this->assertSame('test.pdf', $file->name);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.box.com/2.0/files/1234'
                && $request->hasHeader('Accept', ['application/json'])
                && $request->hasHeader('Authorization', ['Bearer abcdefghi123456789']);
        });
    }

    public function test_it_downloads_file_contents_using_the_box_redirect_url(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::response([
                'access_token' => 'abcdefghi123456789',
                'expires_in' => 3600,
                'token_type' => 'bearer',
            ], 200),
            'https://api.box.com/2.0/files/1234/content' => Http::response('', 302, [
                'Location' => 'https://dl.boxcloud.com/file',
            ]),
            'https://dl.boxcloud.com/file' => Http::response('file-bytes', 200),
        ]);

        $contents = (new BoxFile)->whereId('1234')->contents();

        $this->assertSame('file-bytes', $contents);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.box.com/2.0/files/1234/content'
                && $request->hasHeader('Authorization', ['Bearer abcdefghi123456789'])
                && ! $request->hasHeader('Accept', ['application/json']);
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
