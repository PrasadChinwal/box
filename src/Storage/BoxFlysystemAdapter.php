<?php

namespace PrasadChinwal\Box\Storage;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use PrasadChinwal\Box\Box;

class BoxFlysystemAdapter implements FilesystemAdapter
{
    protected string $apiBase = 'https://api.box.com/2.0';

    protected string $uploadUrl = 'https://upload.box.com/api/2.0';

    protected string $rootFolderId;

    public function __construct(
        protected Box $box,
        ?string $rootFolderId = null
    ) {
        $this->rootFolderId = $rootFolderId ?? config('box.folder_id', '0');
    }

    public function fileExists(string $path): bool
    {
        try {
            $this->getFileIdByPath($path);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            $this->getFolderIdByPath($path);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->upload($path, $contents);
        } catch (Exception $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @param  resource  $contents
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $stringContents = stream_get_contents($contents);
            $this->upload($path, $stringContents);
        } catch (Exception $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function read(string $path): string
    {
        try {
            $fileId = $this->getFileIdByPath($path);

            $response = Http::withToken($this->box->getAccessToken())
                ->get("{$this->apiBase}/files/{$fileId}/content");

            if (! $response->successful()) {
                throw new Exception('Could not read file');
            }

            return $response->body();
        } catch (Exception $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @return resource
     */
    public function readStream(string $path)
    {
        $contents = $this->read($path);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            $fileId = $this->getFileIdByPath($path);

            $response = Http::withToken($this->box->getAccessToken())
                ->delete("{$this->apiBase}/files/{$fileId}");

            if (! $response->successful()) {
                throw new Exception('Could not delete file');
            }
        } catch (Exception $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $folderId = $this->getFolderIdByPath($path);

            $response = Http::withToken($this->box->getAccessToken())
                ->delete("{$this->apiBase}/folders/{$folderId}?recursive=true");

            if (! $response->successful()) {
                throw new Exception('Could not delete directory');
            }
        } catch (Exception $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $pathParts = explode('/', trim($path, '/'));
            $parentId = $this->rootFolderId;

            foreach ($pathParts as $folderName) {
                if (empty($folderName)) {
                    continue;
                }

                $parentId = $this->createOrGetFolder($folderName, $parentId);
            }
        } catch (Exception $e) {
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage());
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // Box doesn't have a direct visibility concept like public/private in the same way
        // This would typically be handled through shared links
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, null, Visibility::PRIVATE);
    }

    public function mimeType(string $path): FileAttributes
    {
        try {
            $fileId = $this->getFileIdByPath($path);
            $metadata = $this->getFileMetadata($fileId);

            return new FileAttributes(
                $path,
                null,
                null,
                null,
                $metadata['content_type'] ?? null
            );
        } catch (Exception $e) {
            throw UnableToRetrieveMetadata::mimeType($path, $e->getMessage(), $e);
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            $fileId = $this->getFileIdByPath($path);
            $metadata = $this->getFileMetadata($fileId);

            $timestamp = isset($metadata['modified_at'])
                ? strtotime($metadata['modified_at'])
                : null;

            return new FileAttributes(
                $path,
                null,
                null,
                $timestamp
            );
        } catch (Exception $e) {
            throw UnableToRetrieveMetadata::lastModified($path, $e->getMessage(), $e);
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            $fileId = $this->getFileIdByPath($path);
            $metadata = $this->getFileMetadata($fileId);

            return new FileAttributes(
                $path,
                $metadata['size'] ?? null
            );
        } catch (Exception $e) {
            throw UnableToRetrieveMetadata::fileSize($path, $e->getMessage(), $e);
        }
    }

    /**
     * @return iterable<StorageAttributes>
     */
    public function listContents(string $path, bool $deep): iterable
    {
        try {
            $folderId = $path === '' || $path === '/'
                ? $this->rootFolderId
                : $this->getFolderIdByPath($path);

            $items = $this->getFolderItems($folderId);

            foreach ($items as $item) {
                $itemPath = $path === '' || $path === '/'
                    ? $item['name']
                    : trim($path, '/').'/'.$item['name'];

                if ($item['type'] === 'folder') {
                    yield new DirectoryAttributes($itemPath);

                    if ($deep) {
                        yield from $this->listContents($itemPath, true);
                    }
                } else {
                    yield new FileAttributes(
                        $itemPath,
                        $item['size'] ?? null,
                        null,
                        isset($item['modified_at']) ? strtotime($item['modified_at']) : null,
                        $item['content_type'] ?? null
                    );
                }
            }
        } catch (Exception $e) {
            return;
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $fileId = $this->getFileIdByPath($source);
            $destinationParts = explode('/', trim($destination, '/'));
            $newFileName = array_pop($destinationParts);
            $destinationFolder = implode('/', $destinationParts);

            $parentId = $destinationFolder
                ? $this->getFolderIdByPath($destinationFolder)
                : $this->rootFolderId;

            $response = Http::asJson()
                ->withToken($this->box->getAccessToken())
                ->put("{$this->apiBase}/files/{$fileId}", [
                    'name' => $newFileName,
                    'parent' => ['id' => $parentId],
                ]);

            if (! $response->successful()) {
                throw new Exception('Could not move file');
            }
        } catch (Exception $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $fileId = $this->getFileIdByPath($source);
            $destinationParts = explode('/', trim($destination, '/'));
            $newFileName = array_pop($destinationParts);
            $destinationFolder = implode('/', $destinationParts);

            $parentId = $destinationFolder
                ? $this->getFolderIdByPath($destinationFolder)
                : $this->rootFolderId;

            $response = Http::asJson()
                ->withToken($this->box->getAccessToken())
                ->post("{$this->apiBase}/files/{$fileId}/copy", [
                    'name' => $newFileName,
                    'parent' => ['id' => $parentId],
                ]);

            if (! $response->successful()) {
                throw new Exception('Could not copy file');
            }
        } catch (Exception $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    protected function upload(string $path, string $contents): void
    {
        $pathParts = explode('/', trim($path, '/'));
        $fileName = array_pop($pathParts);
        $folderPath = implode('/', $pathParts);

        $parentId = $this->rootFolderId;

        if ($folderPath) {
            foreach (explode('/', $folderPath) as $folderName) {
                if (empty($folderName)) {
                    continue;
                }
                $parentId = $this->createOrGetFolder($folderName, $parentId);
            }
        }

        $response = Http::asMultipart()
            ->withToken($this->box->getAccessToken())
            ->attach('file', $contents, $fileName)
            ->post("{$this->uploadUrl}/files/content", [
                'attributes' => json_encode([
                    'name' => $fileName,
                    'parent' => ['id' => $parentId],
                ]),
            ]);

        if (! $response->successful()) {
            throw new Exception('Could not upload file: '.$response->body());
        }
    }

    protected function getFileIdByPath(string $path): string
    {
        $pathParts = explode('/', trim($path, '/'));
        $fileName = array_pop($pathParts);
        $folderPath = implode('/', $pathParts);

        $folderId = $folderPath
            ? $this->getFolderIdByPath($folderPath)
            : $this->rootFolderId;

        $items = $this->getFolderItems($folderId);

        foreach ($items as $item) {
            if ($item['type'] === 'file' && $item['name'] === $fileName) {
                return $item['id'];
            }
        }

        throw new Exception("File not found: {$path}");
    }

    protected function getFolderIdByPath(string $path): string
    {
        if (empty($path) || $path === '/') {
            return $this->rootFolderId;
        }

        $pathParts = explode('/', trim($path, '/'));
        $currentFolderId = $this->rootFolderId;

        foreach ($pathParts as $folderName) {
            if (empty($folderName)) {
                continue;
            }

            $items = $this->getFolderItems($currentFolderId);
            $found = false;

            foreach ($items as $item) {
                if ($item['type'] === 'folder' && $item['name'] === $folderName) {
                    $currentFolderId = $item['id'];
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                throw new Exception("Folder not found: {$path}");
            }
        }

        return $currentFolderId;
    }

    protected function getFolderItems(string $folderId): array
    {
        $response = Http::withToken($this->box->getAccessToken())
            ->get("{$this->apiBase}/folders/{$folderId}/items");

        if (! $response->successful()) {
            throw new Exception('Could not list folder items');
        }

        return $response->json('entries', []);
    }

    protected function getFileMetadata(string $fileId): array
    {
        $response = Http::withToken($this->box->getAccessToken())
            ->get("{$this->apiBase}/files/{$fileId}");

        if (! $response->successful()) {
            throw new Exception('Could not retrieve file metadata');
        }

        return $response->json();
    }

    protected function createOrGetFolder(string $folderName, string $parentId): string
    {
        $items = $this->getFolderItems($parentId);

        foreach ($items as $item) {
            if ($item['type'] === 'folder' && $item['name'] === $folderName) {
                return $item['id'];
            }
        }

        $response = Http::asJson()
            ->withToken($this->box->getAccessToken())
            ->post("{$this->apiBase}/folders", [
                'name' => $folderName,
                'parent' => ['id' => $parentId],
            ]);

        if (! $response->successful()) {
            throw new Exception('Could not create folder: '.$response->body());
        }

        return $response->json('id');
    }
}
