<?php

namespace PrasadChinwal\Box\Dto;

use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class User extends Data
{
    public function __construct(
        public string $id,
        public string $type,
        public string $name,
        public string $login,
        public string $created_at,
        public string $modified_at,
        public string                       $language,
        public string                       $timezone,
        public int                          $space_amount,
        public int                          $space_used,
        public int                          $max_upload_size,
        public string                       $status,
        public string                       $job_title,
        public string                       $phone,
        public string                       $address,
        public string                       $avatar_url,
        public array|Nullable|Optional|null $notification_email,
        public string|Optional              $role,
        public array|Optional               $tracking_codes,
        public bool|Optional                $can_see_managed_users,
        public bool|Optional                $is_sync_enabled,
        public array|Optional               $enterprise,
        public array|Optional               $my_tags,
        public string|Optional              $hostname,
        public bool|Optional                $is_platform_access_only,
        public string|Optional              $external_app_user_id,
    )
    {}
}
