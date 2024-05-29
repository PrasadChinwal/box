<?php

namespace PrasadChinwal\Box\Contracts;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use PrasadChinwal\Box\Responses\File\FileResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface FileContract
{
    public function info(): FileResponse;

    public function downloadFile(): BinaryFileResponse;

    public function createSharedLink(array $attributes): Collection;

    public function thumbnail(string $extension, int $width, int $height): Collection;

    public function copy(array $attributes): FileResponse;

    public function create(string $filepath, string $filename, array $attributes): FileResponse;

    public function update(array $attributes): FileResponse;

    public function delete(): Response;
}
