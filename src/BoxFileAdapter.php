<?php

namespace PrasadChinwal\Box;

use Generator;
use League\Flysystem\ChecksumProvider;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use PrasadChinwal\Box\Exceptions\OperationException;
use PrasadChinwal\Box\Facades\Box;

class BoxFileAdapter implements ChecksumProvider, FilesystemAdapter
{
    protected PathPrefixer $prefixer;

    protected MimeTypeDetector $mimeTypeDetector;

    public function __construct(
        protected string $folderId = '0',
        string $prefix = '',
        ?MimeTypeDetector $mimeTypeDetector = null,
    ) {
        $this->prefixer = new PathPrefixer($prefix);
        $this->mimeTypeDetector = $mimeTypeDetector ?: new FinfoMimeTypeDetector;
    }

    public function fileExists(string $path): bool
    {
        try {
            return $this->findFileEntry($path) !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            if ($this->normalizePath($path) === '') {
                return true;
            }

            return $this->resolveFolderIdForPath($this->normalizePath($path)) !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            ['directory' => $directory, 'filename' => $filename] = $this->parsePath($path);
            $folderId = $this->resolveFolderIdForPath($directory, create: true);

            Box::file()
                ->inFolder($folderId)
                ->write(filepath: $filename, contents: $contents);
        } catch (\Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents), $config);
    }

    public function read(string $path): string
    {
        try {
            $file = $this->findFileEntry($path);

            if ($file === null) {
                throw UnableToReadFile::fromLocation($path, 'File not found.');
            }

            return Box::file()->whereId($file['id'])->contents();
        } catch (UnableToReadFile $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function readStream(string $path)
    {
        $contents = $this->read($path);
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw UnableToReadFile::fromLocation($path, 'Unable to open stream.');
        }

        fwrite($stream, $contents);
        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            $file = $this->findFileEntry($path);

            if ($file === null) {
                throw UnableToDeleteFile::atLocation($path, 'File not found.');
            }

            Box::file()->whereId($file['id'])->delete();
        } catch (UnableToDeleteFile $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $folderId = $this->resolveFolderIdForPath($this->normalizePath($path));

            if ($folderId === null) {
                throw UnableToDeleteDirectory::atLocation($path, 'Directory not found.');
            }

            Box::folder()->whereId($folderId)->delete(recursive: true);
        } catch (UnableToDeleteDirectory $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw UnableToDeleteDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->resolveFolderIdForPath($this->normalizePath($path), create: true);
        } catch (\Throwable $exception) {
            throw UnableToCreateDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToRetrieveMetadata::visibility($path, 'Setting visibility is not supported.');
    }

    public function visibility(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::visibility($path, 'Retrieving visibility is not supported.');
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            $file = $this->findFileEntry($path);

            if ($file === null) {
                throw UnableToRetrieveMetadata::fileSize($path, 'File not found.');
            }

            $size = $file['size'] ?? $this->resolveFileSize((string) $file['id']);

            return new FileAttributes(
                $this->normalizePath($path),
                $size,
            );
        } catch (UnableToRetrieveMetadata $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw UnableToRetrieveMetadata::fileSize($path, $exception->getMessage(), $exception);
        }
    }

    public function mimeType(string $path): FileAttributes
    {
        try {
            $file = $this->findFileEntry($path);

            if ($file === null) {
                throw UnableToRetrieveMetadata::mimeType($path, 'File not found.');
            }

            return new FileAttributes(
                $this->normalizePath($path),
                null,
                null,
                null,
                $this->mimeTypeDetector->detectMimeTypeFromPath($file['name']),
            );
        } catch (UnableToRetrieveMetadata $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw UnableToRetrieveMetadata::mimeType($path, $exception->getMessage(), $exception);
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            $file = $this->findFileEntry($path);

            if ($file === null) {
                throw UnableToRetrieveMetadata::lastModified($path, 'File not found.');
            }

            $timestamp = isset($file['modified_at']) ? strtotime($file['modified_at']) : null;

            return new FileAttributes(
                $this->normalizePath($path),
                $file['size'] ?? null,
                null,
                $timestamp,
            );
        } catch (UnableToRetrieveMetadata $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw UnableToRetrieveMetadata::lastModified($path, $exception->getMessage(), $exception);
        }
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $directory = $this->normalizePath($path);
        $folderId = $directory === ''
            ? $this->rootFolderId()
            : $this->resolveFolderIdForPath($directory);

        if ($folderId === null) {
            return;
        }

        yield from $this->iterateFolderContents($folderId, $directory, $deep);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $sourceFile = $this->findFileEntry($source);

            if ($sourceFile === null) {
                throw UnableToMoveFile::because('Source file not found.', $source, $destination);
            }

            ['directory' => $destinationDirectory, 'filename' => $destinationFilename] = $this->parsePath($destination);
            $destinationFolderId = $this->resolveFolderIdForPath($destinationDirectory, create: true);

            Box::file()->whereId($sourceFile['id'])->update([
                'name' => $destinationFilename,
                'parent' => ['id' => $destinationFolderId],
            ]);
        } catch (UnableToMoveFile $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $sourceFile = $this->findFileEntry($source);

            if ($sourceFile === null) {
                throw UnableToCopyFile::because('Source file not found.', $source, $destination);
            }

            ['directory' => $destinationDirectory, 'filename' => $destinationFilename] = $this->parsePath($destination);
            $destinationFolderId = $this->resolveFolderIdForPath($destinationDirectory, create: true);

            Box::file()->whereId($sourceFile['id'])->copy([
                'name' => $destinationFilename,
                'parent' => ['id' => $destinationFolderId],
            ]);
        } catch (UnableToCopyFile $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function checksum(string $path, Config $config): string
    {
        throw new OperationException('Checksums are not supported for Box files.');
    }

    protected function iterateFolderContents(string $folderId, string $prefix, bool $deep): Generator
    {
        $items = $this->folderEntries($folderId);

        foreach ($items as $entry) {
            $entryPath = ltrim($prefix === '' ? $entry['name'] : "{$prefix}/{$entry['name']}", '/');

            yield $this->normalizeResponse($entry, $entryPath);

            if ($deep && $entry['type'] === 'folder') {
                yield from $this->iterateFolderContents((string) $entry['id'], $entryPath, true);
            }
        }
    }

    protected function normalizeResponse(array $response, string $path): DirectoryAttributes|FileAttributes
    {
        $timestamp = isset($response['modified_at']) ? strtotime($response['modified_at']) : null;

        if ($response['type'] === 'folder') {
            return new DirectoryAttributes($path, null, $timestamp);
        }

        return new FileAttributes(
            $path,
            $response['size'] ?? null,
            null,
            $timestamp,
            $this->mimeTypeDetector->detectMimeTypeFromPath($response['name']),
        );
    }

    protected function parsePath(string $path): array
    {
        $path = $this->normalizePath($path);
        $segments = $path === '' ? [] : explode('/', $path);
        $filename = (string) array_pop($segments);

        return [
            'directory' => implode('/', $segments),
            'filename' => $filename,
        ];
    }

    protected function normalizePath(string $path): string
    {
        return ltrim($this->prefixer->prefixPath($path), '/');
    }

    protected function rootFolderId(): string
    {
        return (string) $this->folderId;
    }

    protected function findFileEntry(string $path): ?array
    {
        ['directory' => $directory, 'filename' => $filename] = $this->parsePath($path);

        if ($filename === '') {
            return null;
        }

        $folderId = $directory === ''
            ? $this->rootFolderId()
            : $this->resolveFolderIdForPath($directory);

        if ($folderId !== null) {
            $entry = $this->findEntryInFolder($folderId, $filename, 'file');

            if ($entry !== null) {
                return $entry;
            }
        }

        return $this->findFileEntryBySearch($directory, $filename);
    }

    protected function findFileEntryBySearch(string $directory, string $filename): ?array
    {
        try {
            $file = Box::file()->inFolder($this->rootFolderId())->search($filename);
        } catch (\Throwable) {
            return null;
        }

        if ($directory !== '' && ! $this->pathMatchesDirectory($file->path_collection, $directory)) {
            return null;
        }

        return [
            'id' => $file->id,
            'name' => $file->name,
            'type' => 'file',
            'size' => $file->size,
            'modified_at' => $file->content_modified_at,
        ];
    }

    protected function pathMatchesDirectory(array $pathCollection, string $directory): bool
    {
        $expectedSegments = explode('/', trim($directory, '/'));
        $folderNames = collect($pathCollection['entries'] ?? [])
            ->filter(fn (array $entry) => ($entry['type'] ?? '') === 'folder' && (string) ($entry['id'] ?? '0') !== '0')
            ->pluck('name')
            ->values()
            ->all();

        return $folderNames === $expectedSegments;
    }

    protected function resolveFolderIdForPath(string $directory, bool $create = false): ?string
    {
        $directory = trim($directory, '/');

        if ($directory === '') {
            return $this->rootFolderId();
        }

        $folderId = $this->rootFolderId();

        foreach (explode('/', $directory) as $segment) {
            $entry = $this->findEntryInFolder($folderId, $segment, 'folder');

            if ($entry === null) {
                if (! $create) {
                    return null;
                }

                $created = Box::folder()->create([
                    'name' => $segment,
                    'parent' => ['id' => $folderId],
                ]);

                $folderId = (string) $created->get('id');

                continue;
            }

            $folderId = (string) $entry['id'];
        }

        return $folderId;
    }

    protected function findEntryInFolder(string $folderId, string $name, ?string $type = null): ?array
    {
        foreach ($this->folderEntries($folderId) as $entry) {
            if ($entry['name'] === $name && ($type === null || $entry['type'] === $type)) {
                return $entry;
            }
        }

        return null;
    }

    protected function folderEntries(string $folderId): array
    {
        return Box::folder()->whereId($folderId)->allEntries();
    }

    protected function resolveFileSize(string $fileId): int
    {
        return Box::file()->whereId($fileId)->info()->size;
    }
}
