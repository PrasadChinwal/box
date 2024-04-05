<?php

namespace PrasadChinwal\Box;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use League\Flysystem\ChecksumProvider;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use PrasadChinwal\Box\Facades\Box;

class BoxFileAdapter implements FilesystemAdapter, ChecksumProvider
{
    protected ?string $folderId = null;

    public function inFolder(string $id)
    {
        $this->folderId = $id;
    }

    /**
     * Checks if a file exists.
     *
     * @param string $id The ID of the file.
     * @return bool Returns true if the file exists, false otherwise.
     * @throws \Exception Throws an exception if an error occurs while checking the file's existence.
     */
    public function fileExists(string $id): bool
    {
        try {
            $file = Box::file()->whereId($id)->info();
            if( $file['id']) {
                return true;
            };
            return false;
        }
        catch (\Exception $exception) {
            throw new \Exception('Could not complete your request!');
        }
    }

    /**
     * Checks if a directory exists.
     *
     * @param string $id The ID of the directory.
     * @return bool Returns true if the directory exists, false otherwise.
     * @throws \Exception Throws an exception if an error occurs while checking the directory's existence.
     */
    public function directoryExists(string $id): bool
    {
        try {
            $folder = Box::folder()->whereId($id)->info();
            if( $folder['id']) {
                return true;
            };
            return false;
        }
        catch (\Illuminate\Http\Client\RequestException  $exception) {
            throw new \Exception('Folder does not exist!');
        }
        catch (\Exception $exception) {
            throw new \Exception('Could not complete your request!');
        }
    }

    /**
     * Writes contents to a file stream.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config
     * @return void
     * @throws \Exception
     */
    public function write(string $path, string $contents, Config $config): void
    {
        try {
            Box::file()->write(filepath: $path, contents: $contents);
        }
        catch (\Exception $exception) {
            throw new \Exception('Could not upload your file!');
        }
    }

    /**
     * Writes contents to a file stream.
     *
     * @param string $path The path where the file will be written.
     * @param mixed $contents The contents to be written to the file.
     * @param Config $config The configuration object.
     * @return void
     * @throws \Exception Throws an exception if an error occurs while writing the file.
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            Box::file()->write(filepath: $path, contents: $contents);
        }
        catch (\Exception $exception) {
            throw new \Exception('Could not upload your file!');
        }
    }

    /**
     * Reads the contents of a file.
     *
     * @param string $id The ID of the file.
     * @return string Returns the contents of the file.
     * @throws \Exception Throws an exception if an error occurs while reading the file.
     */
    public function read(string $id): string
    {
        try {
            return Box::file()->whereId($id)->contents();
        }
        catch (FileNotFoundException $exception) {
            throw new \Exception('Could not find the file!');
        }
        catch (\Exception $exception) {
            throw new \Exception('Could not read your file!');
        }
    }

    /**
     * Reads the contents of a file as a stream.
     *
     * @param string $id The
     * @throws \Exception
     */
    public function readStream(string $id)
    {
        try {
            return Box::file()->whereId($id)->contents();
        }
        catch (\Exception $exception) {
            throw new \Exception('Could not read your file!');
        }
    }

    /**
     * Deletes a file.
     *
     * @param string $id The ID of the file to delete.
     * @return void
     * @throws \Exception Throws an exception if an error occurs while deleting the file.
     */
    public function delete(string $id): void
    {
        try {
            Box::file()->whereId($id)->delete();
        }
        catch (\Exception $exception) {
            throw new \Exception('Could not delete your file!');
        }
    }

    /**
     * Deletes a directory and all its contents recursively.
     *
     * @param string $id The ID of the directory to delete.
     * @throws \Exception Throws an exception if an error occurs while deleting the directory.
     */
    public function deleteDirectory(string $id): void
    {
        try {
            Box::folder()->whereId($id)->delete(recursive: true);
        }
        catch (\Exception $exception) {
            throw new \Exception('Could not delete your folder!');
        }
    }

    /**
     * Creates a directory in Box.
     *
     * @param string $name The name of the directory to be created.
     * @param Config $config The configuration object.
     * @throws \Exception Throws an exception if an error occurs while creating the directory.
     */
    public function createDirectory(string $name, Config $config): void
    {
        try {
            $box = Box::folder();
            if($this->folderId) {
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
     * @param string $path The path of the file or directory.
     * @param string $visibility The visibility to set ('public', 'private', 'default').
     * @return void
     * @throws \Exception Throws an exception indicating that setting visibility is not supported yet.
     */
    public function setVisibility(string $path, string $visibility): void
    {
        throw new \Exception('Not supported yet!');
    }

    /**
     * @param string $path
     * @return FileAttributes
     * @throws \Exception
     */
    public function visibility(string $path): FileAttributes
    {
        throw new \Exception('Not supported yet!');
    }

    /**
     * @param string $path
     * @return FileAttributes
     * @throws \Exception
     */
    public function mimeType(string $path): FileAttributes
    {
        throw new \Exception('Not supported yet!');
    }

    /**
     * @param string $path
     * @return FileAttributes
     * @throws \Exception
     */
    public function lastModified(string $path): FileAttributes
    {
        throw new \Exception('Not supported yet!');
    }

    /**
     * @param string $path
     * @return FileAttributes
     * @throws \Exception
     */
    public function fileSize(string $path): FileAttributes
    {
        throw new \Exception('Not supported yet!');
    }

    /**
     * Lists the contents of a directory.
     *
     * @param string $path The path of the directory.
     * @param bool $deep Determines whether to list the contents recursively or not.
     * @return iterable Returns an iterable collection of directory contents.
     * @throws \Exception Throws an exception indicating that the operation is not supported yet.
     */
    public function listContents(string $path, bool $deep): iterable
    {
        throw new \Exception('Not supported yet!');
    }

    /**
     * @param string $source
     * @param string $destination
     * @param Config $config
     * @return void
     * @throws \Exception
     */
    public function move(string $source, string $destination, Config $config): void
    {
        throw new \Exception('Not supported yet!');
    }

    /**
     * @param string $source
     * @param string $destination
     * @param Config $config
     * @return void
     * @throws \Exception
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        throw new \Exception('Not supported yet!');
    }

    /**
     * Calculates the checksum of a file.
     *
     * @param string $path The path to the file.
     * @param Config $config The configuration instance.
     * @return string The checksum of the file.
     * @throws \Exception Throws an exception indicating that this operation is not supported yet.
     */
    public function checksum(string $path, Config $config): string
    {
        throw new \Exception('Not supported yet!');
    }
}
