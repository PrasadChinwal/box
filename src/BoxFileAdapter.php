<?php

namespace PrasadChinwal\Box;

use Generator;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\Flysystem\ChecksumProvider;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use PrasadChinwal\Box\Facades\Box;

class BoxFileAdapter implements ChecksumProvider, FilesystemAdapter
{
    protected ?string $folderId = null;

    protected PathPrefixer $prefixer;

    protected MimeTypeDetector $mimeTypeDetector;

    protected $file = null;

    public function __construct(
        string $prefix = '',
        ?MimeTypeDetector $mimeTypeDetector = null
    ) {
        $this->folderId = config('box.folder_id');
        $this->prefixer = new PathPrefixer($prefix);
        $this->mimeTypeDetector = $mimeTypeDetector ?: new FinfoMimeTypeDetector();
    }

    public function inFolder(string $id): void
    {
        $this->folderId = $id;
    }

    /**
     * Checks if a file exists.
     *
     * @param  string  $id  The ID of the file.
     * @return bool Returns true if the file exists, false otherwise.
     *
     * @throws \Exception Throws an exception if an error occurs while checking the file's existence.
     */
    public function fileExists(string $id): bool
    {
        try {
            $file = Box::file()->search($id);

            return ! empty($file?->id);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Checks if a directory exists.
     *
     * @param  string  $id  The ID of the directory.
     * @return bool Returns true if the directory exists, false otherwise.
     *
     * @throws \Exception Throws an exception if an error occurs while checking the directory's existence.
     */
    public function directoryExists(string $id): bool
    {
        try {
            $folder = Box::folder()
                ->whereId(\config('box.folder_id'))
                ->info();

            return ! empty($folder?->id);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Writes contents to a file stream.
     *
     * @param  string  $path  The path where the file will be written.
     * @param  mixed  $contents  The contents to be written to the file.
     * @param  Config  $config  The configuration object.
     *
     * @throws \Exception Throws an exception if an error occurs while writing the file.
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            Box::file()->create(filepath: $path, contents: $contents);
        } catch (\Exception $exception) {
            throw new \Exception('Could not upload your file!');
        }
    }

    /**
     * Writes contents to a file stream.
     *
     * @throws \Exception
     */
    public function write(string $path, string $contents, Config $config): void
    {
        try {
            Box::file()->write(filepath: $path, contents: $contents);
        } catch (\Exception $exception) {
            throw new \Exception('Could not upload your file!');
        }
    }

    /**
     * Reads the contents of a file.
     *
     * @param  string  $id  The ID of the file.
     * @return string Returns the contents of the file.
     *
     * @throws \Exception Throws an exception if an error occurs while reading the file.
     */
    public function read(string $id): string
    {
        try {
            return Box::file()->whereId($id)->contents();
        } catch (FileNotFoundException $exception) {
            throw new \Exception('Could not find the file!');
        } catch (\Exception $exception) {
            throw new \Exception('Could not read your file!');
        }
    }

    /**
     * Reads the contents of a file as a stream.
     *
     * @param  string  $id  The
     *
     * @throws \Exception
     */
    public function readStream(string $path)
    {
        if (Str::contains($path, '/')) {
            $path = Str::after($path, '/');
        }
        try {
            $file = Box::file()->search($path);

            return Box::file()->whereId($file->id)->contents();
        } catch (\Exception $exception) {
            throw new \Exception("Could not read your file $path!");
        }
    }

    /**
     * Deletes a directory and all its contents recursively.
     *
     * @param  string  $id  The ID of the directory to delete.
     *
     * @throws \Exception Throws an exception if an error occurs while deleting the directory.
     */
    public function deleteDirectory(string $id): void
    {
        try {
            Box::folder()->whereId($id)->delete(recursive: true);
        } catch (\Exception $exception) {
            throw new \Exception('Could not delete your folder!');
        }
    }

    /**
     * Deletes a file.
     *
     * @param  string  $path  The ID of the file to delete.
     *
     * @throws \Exception Throws an exception if an error occurs while deleting the file.
     */
    public function delete(string $path): void
    {
        if (Str::contains($path, '/')) {
            $path = Str::after($path, '/');
        }
        try {
            $file = Box::file()->search($path);
            Box::file()->whereId($file->id)->delete();
        } catch (\Exception $exception) {
            throw new \Exception('Could not delete your file!');
        }
    }

    /**
     * Creates a directory in Box.
     *
     * @param  string  $name  The name of the directory to be created.
     * @param  Config  $config  The configuration object.
     *
     * @throws \Exception Throws an exception if an error occurs while creating the directory.
     */
    public function createDirectory(string $name, Config $config): void
    {
        try {
            $box = Box::folder();
            if ($this->folderId) {
                $box->whereId($this->folderId);
            }
            $box->createDirectory(attributes: $name);
        } catch (\Exception $exception) {
            throw new \Exception('Could not create folder!');
        }
    }

    /**
     * Sets the visibility of a file or directory.
     *
     * @param  string  $path  The path of the file or directory.
     * @param  string  $visibility  The visibility to set ('public', 'private', 'default').
     *
     * @throws \Exception Throws an exception indicating that setting visibility is not supported yet.
     */
    public function setVisibility(string $path, string $visibility): void
    {
        throw new \Exception('Not supported yet!');
    }

    /**
     * @throws \Exception
     */
    public function visibility(string $path): FileAttributes
    {
        throw new \Exception('Not supported yet!');
    }

    /**
     * @throws \Exception
     */
    public function fileSize(string $id): FileAttributes
    {
        try {
            $box = Box::file();
            $file = $box->search($id);
            $download = Box::file()->whereId($file->id)->contents();

            // This is required in order to download the file from box to local storage.
            return new FileAttributes(
                path: $box->storagePath.$id,
                fileSize: $file->size,
                mimeType: $this->getMimeType($box->storagePath.$file->name),
            );
        } catch (\Exception $exception) {
            throw new \Exception('Could not get file size!'.$exception->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    public function mimeType(string $path): FileAttributes
    {
        if (Str::contains($path, '/')) {
            $path = Str::after($path, '/');
        }
        try {
            $box = Box::file();
            $file = $box->search($path);

            return new FileAttributes(
                $box->storagePath.$path,
                null,
                null,
                null,
                $this->mimeTypeDetector->detectMimeTypeFromPath($box->storagePath.$file->name)
            );
        } catch (\Exception $exception) {
            throw new \Exception('Could not get file mimeType!'.$exception->getMessage());
        }
    }

    /**
     * Gets the MIME type of file.
     *
     * @param  string  $filePath  The path of the file.
     * @return string The MIME type of the file.
     */
    private function getMimeType(string $filePath): string
    {
        $box = Box::file();
        $file = $box->search($filePath);

        return $this->mimeTypeDetector->detectMimeTypeFromPath($box->storagePath.$file->name);
    }

    /**
     * @throws \Exception
     */
    public function lastModified(string $path): FileAttributes
    {
        try {
            $box = Box::file();
            $file = $box->whereId($path)->info();
        } catch (\Exception $exception) {
            throw new \Exception('Could not get file last modified! '.$exception->getMessage());
        }

        return new FileAttributes(
            $box->storagePath.$path,
            $file->size,
            null,
            strtotime($file->content_modified_at),
            $this->mimeTypeDetector->detectMimeTypeFromPath($box->storagePath.$file->name)
        );
    }

    /**
     * Lists the contents of a directory.
     *
     * @param  bool  $deep  Determines whether to list the contents recursively or not.
     * @return iterable Returns an iterable collection of directory contents.
     *
     * @throws \Exception Throws an exception indicating that the operation is not supported yet.
     */
    public function listContents(string $id, bool $deep): iterable
    {
        foreach ($this->iterateFolderContents($id, $deep) as $entry) {
            $storageAttrs = $this->normalizeResponse($entry->toArray());

            // Avoid including the base directory itself
            if ($storageAttrs->isDir() && $storageAttrs->path() === $id) {
                continue;
            }
            yield $storageAttrs;
        }
    }

    /**
     * @throws \Exception
     */
    protected function iterateFolderContents(string $id = '', bool $deep = false): Generator
    {
        $location = $this->applyPathPrefix($id);

        try {
            $result = Box::folder()->whereId($this->folderId)->items();
        } catch (\Exception $exception) {
            throw new \Exception('Could not iterate folder contents!');
        }

        yield from $result;
    }

    /**
     * Returns the download url for the file.
     *
     * @throws \Exception
     */
    public function getUrl(string $id): string
    {
        try {
            $file = Box::file()->search($id);

            return Box::file()->whereId($file->id)->getDownloadUrl();
        } catch (\Exception $exception) {
            Log::error('Exception: '.$exception->getMessage());
            throw new \Exception('Could not get file url!');
        }
    }

    /**
     * @return DirectoryAttributes|FileAttributes
     */
    protected function normalizeResponse(array $response)
    {
        $timestamp = (isset($response['server_modified'])) ? strtotime($response['server_modified']) : null;

        if ($response['type'] === 'folder') {
            $normalizedPath = ltrim($this->prefixer->stripDirectoryPrefix($response['path_display']), '/');

            return new DirectoryAttributes(
                $normalizedPath,
                null,
                $timestamp
            );
        }

        $normalizedPath = ltrim($this->prefixer->stripPrefix($response['id']), '/');

        return new FileAttributes(
            $normalizedPath,
            $response['size'] ?? null,
            null,
            $timestamp,
            $this->mimeTypeDetector->detectMimeTypeFromPath($normalizedPath)
        );
    }

    protected function applyPathPrefix($path): string
    {
        return '/'.trim($this->prefixer->prefixPath($path), '/');
    }

    /**
     * @throws \Exception
     */
    public function move(string $source, string $destination, Config $config): void
    {
        throw new \Exception('Not supported yet!');
    }

    /**
     * @throws \Exception
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $attributes = [
                'parent' => [
                    'id' => $destination, // The ID of folder to copy the file to.
                ],
            ];
            Box::file()->whereId($source)->copy($attributes);
        } catch (\Exception $exception) {
            throw new \Exception('Could not get file info!');
        }
    }

    /**
     * Calculates the checksum of a file.
     *
     * @param  string  $path  The path to the file.
     * @param  Config  $config  The configuration instance.
     * @return string The checksum of the file.
     *
     * @throws \Exception Throws an exception indicating that this operation is not supported yet.
     */
    public function checksum(string $path, Config $config): string
    {
        throw new \Exception('Not supported yet!');
    }
}
