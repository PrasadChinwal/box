<?php

use Illuminate\Support\Facades\Http;
use PrasadChinwal\Box\File\BoxFile;

it('can retrieve file information', function () {
    Http::fake([
        'https://api.box.com/oauth2/token' => Http::response([
            'access_token' => 'abcdefghi123456789',
            'expires_in' => 3600,
            'issued_token_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'refresh_token' => '987654321ihgfedcba',
            'token_type' => 'bearer',
        ]),
        'https://api.box.com/2.0/files/1234' => Http::response([
            'id' => '1234',
            'type' => 'file',
            'description' => 'description',
            'extension' => 'pdf',
            'name' => 'test.pdf',
            'shared_link' => [],
            'size' => 1233332,
        ]),
    ]);
    $response = [
        'id' => '1234',
        'type' => 'file',
        'description' => 'description',
        'extension' => 'pdf',
        'name' => 'test.pdf',
        'shared_link' => [],
        'size' => 1233332,
    ];
    $client = Mockery::mock(BoxFile::class);
    $client->shouldReceive('whereId')->with(123);
    $client->shouldReceive('info');
});
