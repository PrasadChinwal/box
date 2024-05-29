<?php

namespace PrasadChinwal\Box\Responses\File;

use Spatie\LaravelData\Data;

class FileResponse extends Data
{
    public string $id;

    public string $type;

    public ?array $allowed_invitee_roles;

    public string $name;


    public FileVersion $file_version;

    public ?bool $has_collaborations;

    public ?bool $is_accessible_via_shared_link;

    public ?bool $is_externally_owned;

    public ?bool $is_package;

    public string $item_status;

    public string $sequence_id;

    public string $etag;

    public string $sha1;

    public string $description;

    public int $size;

    public array $path_collection;

    public string $created_at;

    public ?string $modified_at;

    public ?string $trashed_at;

    public ?string $purged_at;

    public string $content_created_at;

    public string $content_modified_at;

    public array $created_by;

    public array $modified_by;

    public array $owned_by;

    public ?string $shared_link;

    public ?array $parent;

}
