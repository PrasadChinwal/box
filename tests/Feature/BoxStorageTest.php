<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Http::fake([
        'https://api.box.com/oauth2/token' => Http::response([
            'access_token' => 'test_access_token_12345',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ]),
    ]);

    config([
        'filesystems.disks.box' => [
            'driver' => 'box',
            'folder_id' => '0',
        ],
    ]);
});

it('can register the box disk driver', function () {
    expect(Storage::disk('box'))->not()->toBeNull();
});

it('can list files in a directory', function () {
    Http::fake([
        'https://api.box.com/oauth2/token' => Http::response([
            'access_token' => 'test_access_token_12345',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ]),
        'https://api.box.com/2.0/folders/0/items' => Http::response([
            'entries' => [
                [
                    'type' => 'file',
                    'id' => '123',
                    'name' => 'test.txt',
                    'size' => 1024,
                    'modified_at' => '2024-01-01T10:00:00-08:00',
                    'content_type' => 'text/plain',
                ],
                [
                    'type' => 'file',
                    'id' => '124',
                    'name' => 'document.pdf',
                    'size' => 2048,
                    'modified_at' => '2024-01-02T11:00:00-08:00',
                    'content_type' => 'application/pdf',
                ],
            ],
        ]),
    ]);

    $files = Storage::disk('box')->files('/');

    expect($files)->toBeArray()
        ->and($files)->toHaveCount(2)
        ->and($files[0])->toBe('test.txt')
        ->and($files[1])->toBe('document.pdf');
});

it('can write a file to box', function () {
    Http::fake([
        'https://api.box.com/oauth2/token' => Http::response([
            'access_token' => 'test_access_token_12345',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ]),
        'https://upload.box.com/api/2.0/files/content' => Http::response([
            'entries' => [
                [
                    'type' => 'file',
                    'id' => '125',
                    'name' => 'new-file.txt',
                    'size' => 13,
                ],
            ],
        ], 201),
    ]);

    $result = Storage::disk('box')->put('new-file.txt', 'Hello, World!');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'upload.box.com');
    });
});

it('can read a file from box', function () {
    Http::fake([
        'https://api.box.com/oauth2/token' => Http::response([
            'access_token' => 'test_access_token_12345',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ]),
        'https://api.box.com/2.0/folders/0/items' => Http::response([
            'entries' => [
                [
                    'type' => 'file',
                    'id' => '123',
                    'name' => 'test.txt',
                ],
            ],
        ]),
        'https://api.box.com/2.0/files/123/content' => Http::response('File contents here'),
    ]);

    $contents = Storage::disk('box')->get('test.txt');

    expect($contents)->toBe('File contents here');
});

it('can delete a file from box', function () {
    Http::fake([
        'https://api.box.com/oauth2/token' => Http::response([
            'access_token' => 'test_access_token_12345',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ]),
        'https://api.box.com/2.0/folders/0/items' => Http::response([
            'entries' => [
                [
                    'type' => 'file',
                    'id' => '123',
                    'name' => 'test.txt',
                ],
            ],
        ]),
        'https://api.box.com/2.0/files/123' => Http::response('', 204),
    ]);

    $result = Storage::disk('box')->delete('test.txt');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->method() === 'DELETE' &&
            str_contains($request->url(), '/files/123');
    });
});

it('can check if a file exists', function () {
    Http::fake([
        'https://api.box.com/oauth2/token' => Http::response([
            'access_token' => 'test_access_token_12345',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ]),
        'https://api.box.com/2.0/folders/0/items' => Http::response([
            'entries' => [
                [
                    'type' => 'file',
                    'id' => '123',
                    'name' => 'exists.txt',
                ],
            ],
        ]),
    ]);

    $exists = Storage::disk('box')->exists('exists.txt');
    $notExists = Storage::disk('box')->exists('not-exists.txt');

    expect($exists)->toBeTrue()
        ->and($notExists)->toBeFalse();
});

it('can get file size', function () {
    Http::fake([
        'https://api.box.com/oauth2/token' => Http::response([
            'access_token' => 'test_access_token_12345',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ]),
        'https://api.box.com/2.0/folders/0/items' => Http::response([
            'entries' => [
                [
                    'type' => 'file',
                    'id' => '123',
                    'name' => 'test.txt',
                ],
            ],
        ]),
        'https://api.box.com/2.0/files/123' => Http::response([
            'id' => '123',
            'name' => 'test.txt',
            'size' => 2048,
            'modified_at' => '2024-01-01T10:00:00-08:00',
        ]),
    ]);

    $size = Storage::disk('box')->size('test.txt');

    expect($size)->toBe(2048);
});

it('can get file last modified timestamp', function () {
    Http::fake([
        'https://api.box.com/oauth2/token' => Http::response([
            'access_token' => 'test_access_token_12345',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ]),
        'https://api.box.com/2.0/folders/0/items' => Http::response([
            'entries' => [
                [
                    'type' => 'file',
                    'id' => '123',
                    'name' => 'test.txt',
                ],
            ],
        ]),
        'https://api.box.com/2.0/files/123' => Http::response([
            'id' => '123',
            'name' => 'test.txt',
            'modified_at' => '2024-01-01T10:00:00-08:00',
        ]),
    ]);

    $timestamp = Storage::disk('box')->lastModified('test.txt');

    expect($timestamp)->toBeInt()
        ->and($timestamp)->toBeGreaterThan(0);
});

it('handles missing file errors gracefully', function () {
    Http::fake([
        'https://api.box.com/oauth2/token' => Http::response([
            'access_token' => 'test_access_token_12345',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ]),
        'https://api.box.com/2.0/folders/0/items' => Http::response([
            'entries' => [],
        ]),
    ]);

    expect(fn () => Storage::disk('box')->get('nonexistent.txt'))
        ->toThrow(League\Flysystem\UnableToReadFile::class);
});

it('can create directories', function () {
    Http::fake([
        'https://api.box.com/oauth2/token' => Http::response([
            'access_token' => 'test_access_token_12345',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ]),
        'https://api.box.com/2.0/folders/0/items' => Http::response([
            'entries' => [],
        ]),
        'https://api.box.com/2.0/folders' => Http::response([
            'id' => '456',
            'type' => 'folder',
            'name' => 'test-directory',
        ], 201),
    ]);

    $result = Storage::disk('box')->makeDirectory('test-directory');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->method() === 'POST' &&
            str_contains($request->url(), '/folders');
    });
});

it('can list files in a nested directory', function () {
    Http::fake([
        'https://api.box.com/oauth2/token' => Http::response([
            'access_token' => 'test_access_token_12345',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ]),
        'https://api.box.com/2.0/folders/0/items' => Http::response([
            'entries' => [
                [
                    'type' => 'folder',
                    'id' => '456',
                    'name' => 'documents',
                ],
            ],
        ]),
        'https://api.box.com/2.0/folders/456/items' => Http::response([
            'entries' => [
                [
                    'type' => 'file',
                    'id' => '789',
                    'name' => 'report.pdf',
                    'size' => 4096,
                ],
            ],
        ]),
    ]);

    $files = Storage::disk('box')->files('documents');

    expect($files)->toBeArray()
        ->and($files)->toHaveCount(1)
        ->and($files[0])->toBe('documents/report.pdf');
});

it('can copy a file', function () {
    Http::fake([
        'https://api.box.com/oauth2/token' => Http::response([
            'access_token' => 'test_access_token_12345',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ]),
        'https://api.box.com/2.0/folders/0/items' => Http::response([
            'entries' => [
                [
                    'type' => 'file',
                    'id' => '123',
                    'name' => 'source.txt',
                ],
            ],
        ]),
        'https://api.box.com/2.0/files/123/copy' => Http::response([
            'id' => '124',
            'name' => 'destination.txt',
        ], 201),
    ]);

    $result = Storage::disk('box')->copy('source.txt', 'destination.txt');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->method() === 'POST' &&
            str_contains($request->url(), '/copy');
    });
});

it('can move a file', function () {
    Http::fake([
        'https://api.box.com/oauth2/token' => Http::response([
            'access_token' => 'test_access_token_12345',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ]),
        'https://api.box.com/2.0/folders/0/items' => Http::response([
            'entries' => [
                [
                    'type' => 'file',
                    'id' => '123',
                    'name' => 'source.txt',
                ],
            ],
        ]),
        'https://api.box.com/2.0/files/123' => Http::response([
            'id' => '123',
            'name' => 'destination.txt',
        ]),
    ]);

    $result = Storage::disk('box')->move('source.txt', 'destination.txt');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->method() === 'PUT' &&
            str_contains($request->url(), '/files/123');
    });
});
