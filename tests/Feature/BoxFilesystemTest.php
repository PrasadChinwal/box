<?php

namespace PrasadChinwal\Box\Test\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PrasadChinwal\Box\BoxFileAdapter;
use PrasadChinwal\Box\Test\TestCase;

class BoxFilesystemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(config('box.token_cache_key'));

        config()->set('filesystems.disks.box', [
            'driver' => 'box',
            'folder_id' => '4321',
        ]);
    }

    public function test_it_registers_the_box_storage_driver(): void
    {
        $disk = Storage::disk('box');

        $this->assertSame('box', $disk->getConfig()['driver']);
        $this->assertInstanceOf(BoxFileAdapter::class, $disk->getAdapter());
    }

    public function test_it_can_write_and_read_a_file(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::response($this->fakeTokenResponse(), 200),
            'https://api.box.com/2.0/folders/4321/items' => Http::response([
                'entries' => [[
                    'id' => '5555',
                    'type' => 'folder',
                    'name' => 'directory',
                ]],
            ], 200),
            'https://api.box.com/2.0/folders/5555/items' => Http::response([
                'entries' => [[
                    'id' => '1234',
                    'type' => 'file',
                    'name' => 'file.txt',
                    'size' => 8,
                ]],
            ], 200),
            'https://upload.box.com/api/2.0/files/content' => Http::response([
                'entries' => [[
                    'id' => '1234',
                    'type' => 'file',
                    'name' => 'file.txt',
                    'size' => 8,
                ]],
            ], 201),
            'https://api.box.com/2.0/files/1234/content' => Http::response('Contents', 200),
        ]);

        Storage::disk('box')->put('directory/file.txt', 'Contents');

        $this->assertSame('Contents', Storage::disk('box')->get('directory/file.txt'));
    }

    public function test_it_can_list_files_in_a_directory(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::response($this->fakeTokenResponse(), 200),
            'https://api.box.com/2.0/folders/4321/items' => Http::response([
                'entries' => [[
                    'id' => '5555',
                    'type' => 'folder',
                    'name' => 'directory',
                ]],
            ], 200),
            'https://api.box.com/2.0/folders/5555/items' => Http::response([
                'entries' => [
                    [
                        'id' => '1234',
                        'type' => 'file',
                        'name' => 'file.txt',
                        'size' => 8,
                    ],
                    [
                        'id' => '6666',
                        'type' => 'folder',
                        'name' => 'nested',
                    ],
                ],
            ], 200),
        ]);

        $files = Storage::disk('box')->files('directory');

        $this->assertSame(['directory/file.txt'], $files);
    }

    public function test_it_can_delete_a_file(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::response($this->fakeTokenResponse(), 200),
            'https://api.box.com/2.0/folders/4321/items' => Http::response([
                'entries' => [[
                    'id' => '1234',
                    'type' => 'file',
                    'name' => 'file.txt',
                    'size' => 8,
                ]],
            ], 200),
            'https://api.box.com/2.0/files/1234' => Http::response('', 204),
        ]);

        Storage::disk('box')->delete('file.txt');

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && $request->url() === 'https://api.box.com/2.0/files/1234';
        });
    }

    public function test_it_can_copy_a_file(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::response($this->fakeTokenResponse(), 200),
            'https://api.box.com/2.0/folders/4321/items' => Http::response([
                'entries' => [[
                    'id' => '1234',
                    'type' => 'file',
                    'name' => 'old.txt',
                    'size' => 8,
                ]],
            ], 200),
            'https://api.box.com/2.0/files/1234/copy' => Http::response([
                'id' => '5678',
                'type' => 'file',
                'name' => 'new.txt',
            ], 201),
        ]);

        Storage::disk('box')->copy('old.txt', 'new.txt');

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://api.box.com/2.0/files/1234/copy';
        });
    }

    public function test_it_can_move_a_file(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::response($this->fakeTokenResponse(), 200),
            'https://api.box.com/2.0/folders/4321/items' => Http::sequence()
                ->push(['entries' => [[
                    'id' => '1234',
                    'type' => 'file',
                    'name' => 'old.txt',
                    'size' => 8,
                ]]], 200)
                ->push(['entries' => [[
                    'id' => '7777',
                    'type' => 'folder',
                    'name' => 'new-location',
                ]]], 200)
                ->push(['entries' => []], 200),
            'https://api.box.com/2.0/files/1234' => Http::response([
                'id' => '1234',
                'type' => 'file',
                'name' => 'file.txt',
            ], 200),
        ]);

        Storage::disk('box')->move('old.txt', 'new-location/file.txt');

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && $request->url() === 'https://api.box.com/2.0/files/1234';
        });
    }

    private function fakeTokenResponse(): array
    {
        return [
            'access_token' => 'filesystem-token',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ];
    }
}
