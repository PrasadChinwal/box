<?php

namespace PrasadChinwal\Box\Test\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PrasadChinwal\Box\Dto\User;
use PrasadChinwal\Box\Test\TestCase;

class BoxUserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(config('box.token_cache_key'));
    }

    public function test_it_returns_a_user_dto_for_the_current_user(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::response($this->fakeTokenResponse(), 200),
            'https://api.box.com/2.0/users/me' => Http::response($this->fakeUserResponse('123'), 200),
        ]);

        $user = app('box')->user()->get();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('123', $user->id);
    }

    public function test_it_returns_user_dtos_when_listing_users(): void
    {
        Http::fake([
            'https://api.box.com/oauth2/token' => Http::response($this->fakeTokenResponse(), 200),
            'https://api.box.com/2.0/users/' => Http::response([
                'entries' => [
                    $this->fakeUserResponse('123'),
                    $this->fakeUserResponse('456'),
                ],
            ], 200),
        ]);

        $users = app('box')->user()->all();

        $this->assertInstanceOf(Collection::class, $users);
        $this->assertCount(2, $users);
        $this->assertContainsOnlyInstancesOf(User::class, $users);
    }

    private function fakeTokenResponse(): array
    {
        return [
            'access_token' => 'user-token',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ];
    }

    private function fakeUserResponse(string $id): array
    {
        return [
            'id' => $id,
            'type' => 'user',
            'name' => 'Test User',
            'login' => 'test@example.com',
            'created_at' => '2024-01-01T00:00:00Z',
            'modified_at' => '2024-01-01T00:00:00Z',
            'language' => 'en',
            'timezone' => 'America/Chicago',
            'space_amount' => 100,
            'space_used' => 10,
            'max_upload_size' => 50,
            'status' => 'active',
            'job_title' => 'Engineer',
            'phone' => '555-5555',
            'address' => '123 Test St',
            'avatar_url' => 'https://example.com/avatar.png',
            'notification_email' => null,
            'role' => 'user',
            'tracking_codes' => [],
            'can_see_managed_users' => false,
            'is_sync_enabled' => true,
            'enterprise' => [],
            'my_tags' => [],
            'hostname' => 'example.box.com',
            'is_platform_access_only' => false,
            'external_app_user_id' => 'ext-'.$id,
        ];
    }
}
