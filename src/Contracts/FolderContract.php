<?php

namespace PrasadChinwal\Box\Contracts;

use Illuminate\Support\Collection;
use PrasadChinwal\Box\Responses\Folder\FolderResponse;

interface FolderContract
{
    public function info(): FolderResponse;

    public function items(): Collection;

    public function copy(array $attributes): FolderResponse;

    public function create(array $attributes): FolderResponse;

    public function update(array $attributes): FolderResponse;

    public function createSharedLink(array $attributes): Collection;
}
