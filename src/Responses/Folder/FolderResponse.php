<?php

namespace PrasadChinwal\Box\Responses\Folder;

use Spatie\LaravelData\Data;

class FolderResponse extends Data
{
    public string $id;

    public string $type;

    public ?array $allowed_invitee_roles;

    public ?bool $can_non_owners_invite;

    public ?bool $can_non_owners_view_collaborators;

    public string $content_created_at;

    public string $content_modified_at;

    public array $created_by;

    public string $created_at;

    public ?string $modified_at;

    public ?string $trashed_at;

    public ?string $purged_at;

    public string $description;

    public string $etag;

    public ?bool $has_collaborations;

    public ?bool $is_accessible_via_shared_link;

    public ?bool $is_collaboration_restricted_to_enterprise;

    public ?bool $is_externally_owned;

    public string $item_status;

    public array $path_collection;

    public ?array $modified_by;

    public ?array $owned_by;

    public ?array $parent;

    public ?array $permissions;

    public ?array $shared_link;

    public ?string $sync_state;

    public ?array $tags;

}
