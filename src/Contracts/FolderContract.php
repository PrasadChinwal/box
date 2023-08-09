<?php

namespace PrasadChinwal\Box\Contracts;

use Illuminate\Support\Collection;

interface FolderContract
{
    public function info() : Collection;

    public function items() : Collection;

    public function copy(array $attributes) : Collection;

    public function create(array $attributes) : Collection;

    public function update(array $attributes) : Collection;

    public function createSharedLink(array $attributes) : Collection;
}
