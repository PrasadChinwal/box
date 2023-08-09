<?php

namespace PrasadChinwal\Box\Contracts;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface FileContract
{
    public function info() : Collection;

    public function downloadFile() : BinaryFileResponse;

    public function createSharedLink(array $attributes) : Collection;

    public function thumbnail(string $extension, int $width, int $height): Collection;

    public function copy(array $attributes) : Collection;

    public function create(string $filepath, string $filename, array $attributes) : Collection;

    public function update(array $attributes) : Collection;

    public function delete() : Response;
}
