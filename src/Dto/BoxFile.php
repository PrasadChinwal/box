<?php

namespace PrasadChinwal\Box\Dto;

use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class BoxFile extends Data
{
    public function __construct(
        public string $id,
        public string $type,
        public array $file_version,
        public string $sequence_id,
        public string $etag,
        public string $sha1,
        public string $name,
        public string $description,
        public int $size,
        public array $path_collection,
        public string|Optional|null $trashed_at,
        public string|Optional|null $purged_at,
        public string $content_created_at,
        public string $content_modified_at,
        public array $created_by,
        public array $modified_by,
        public array $owned_by,
        public string|Optional|null $shared_link,
        public array $parent,
        public string $item_status,
    )
    {}
}
