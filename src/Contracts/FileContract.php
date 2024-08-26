<?php

namespace PrasadChinwal\Box\Contracts;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use PrasadChinwal\Box\Dto\BoxFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface FileContract
{
    public function info(): BoxFile;

    public function search(string $filename): BoxFile;

    public function downloadFile(): BinaryFileResponse;

    public function getDownloadUrl(): string;

    public function contents(): string;

    public function createSharedLink(array $attributes): Collection;

    public function thumbnail(string $extension, int $width, int $height): Collection;

    public function copy(array $attributes): BoxFile;

    public function create(string $filepath, string $filename, array $attributes): Collection;

    public function update(array $attributes): BoxFile;

    public function delete(): Response;
}
