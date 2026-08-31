<?php

namespace PrasadChinwal\Box\File;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use PrasadChinwal\Box\Box;
use PrasadChinwal\Box\Contracts\FileContract;
use PrasadChinwal\Box\Exceptions\OperationException;
use PrasadChinwal\Box\Exceptions\ResourceNotFoundException;
use PrasadChinwal\Box\Traits\CanCollaborate;
use PrasadChinwal\Box\Traits\CanShare;
use PrasadChinwal\Box\Traits\CanWatermark;
use PrasadChinwal\Box\Traits\HasVersions;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BoxFile extends Box implements FileContract
{
    use CanCollaborate;
    use CanShare;
    use CanWatermark;
    use HasVersions;

    protected string $endpoint = 'https://api.box.com/2.0/files/';

    protected string $uploadUrl = 'https://upload.box.com/api/2.0/files/content';

    private string $sharedLinkUrl = 'https://api.box.com/2.0/shared_items/';

    protected ?string $id = null;

    protected string $sharedLink = '';

    protected ?string $sharedLinkPassword = null;

    protected Collection $result;

    protected ?string $folderId = null;

    public ?string $storagePath = null;

    /**
     * @throws Exception
     */
    public function whereId(string $id): BoxFile
    {
        $this->id = $id;

        return $this;
    }

    public function inFolder(string $id): BoxFile
    {
        $this->folderId = $id;

        return $this;
    }

    /**
     * @throws RequestException|\Illuminate\Http\Client\ConnectionException
     * @throws FileNotFoundException
     * @throws \Throwable
     */
    public function search(string $filename): \PrasadChinwal\Box\Dto\BoxFile
    {
        $search = $this->boxRequest()
            ->get('https://api.box.com/2.0/search', [
                'query' => $filename,
                'ancestor_folder_id' => config('box.folder_id'),
                'content_types' => 'name',
                'limit' => 1,
            ])
            ->throwUnlessStatus(200)
            ->collect('entries');

        throw_if($search->isEmpty(), ResourceNotFoundException::make("File {$filename} not found."));

        return \PrasadChinwal\Box\Dto\BoxFile::from($search->first());
    }

    /**
     * @see https://developer.box.com/reference/get-files-id/
     *
     * @throws Exception
     */
    public function info(): \PrasadChinwal\Box\Dto\BoxFile
    {
        $response = $this->boxRequest()
            ->get($this->endpoint.$this->id)
            ->throwUnlessStatus(200)
            ->collect();

        return \PrasadChinwal\Box\Dto\BoxFile::from($response);
    }

    /**
     * @throws FileNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    public function downloadFile(): BinaryFileResponse
    {
        $fileInfo = $this->info();
        $response = $this->boxRequest()
            ->sink(storage_path("/app/{$fileInfo->name}"))
            ->get($this->endpoint.$this->id.'/content');
        if ($response->noContent()) {
            throw ResourceNotFoundException::make('The file information was not found.');
        }

        $this->ensureSuccessful($response, 'Could not download the Box file.');

        return response()->download(storage_path("/app/{$fileInfo->name}"));
    }

    /**
     * Returns the download url for the file
     *
     * @throws Exception
     */
    public function getDownloadUrl(): string
    {
        $response = $this->boxRequest([
                'allow_redirects' => false,
            ])
            ->get($this->endpoint.$this->id.'/content');

        $this->ensureStatus($response, 302, 'Could not determine the Box file download URL.');

        if (! $response->header('location')) {
            throw new OperationException('File download url not found.');
        }

        return $response->header('location');
    }

    /**
     * @throws FileNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    public function contents(): string
    {
        $fileInfo = $this->info();
        $response = $this->boxRequest()
            ->sink($this->storagePath.$fileInfo->name)
            ->get($this->endpoint.$this->id.'/content');
        if ($response->noContent()) {
            throw ResourceNotFoundException::make('The file information was not found.');
        }

        return $response;
    }

    /**
     * @see https://developer.box.com/reference/get-files-id-thumbnail-id/
     *
     * @throws RequestException
     */
    public function thumbnail(string $extension, ?int $width = null, ?int $height = null): Collection
    {
        if (! in_array($extension, ['.png', '.jpg'])) {
            throw new ValidationException('File extension not supported!');
        }

        return $this->boxRequest()
            ->get($this->endpoint.$this->id.'/thumbnail'.$extension)
            ->throwUnlessStatus([200, 201])
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/post-files-id-copy/
     *
     * @throws Exception
     */
    public function copy(array $attributes = []): \PrasadChinwal\Box\Dto\BoxFile
    {
        $response = $this->formRequest()
            ->asJson()
            ->post($this->endpoint.$this->id.'/copy', $attributes);

        if ($response->noContent()) {
            throw ResourceNotFoundException::make('The file information was not found.');
        }

        $this->ensureSuccessful($response, 'Could not copy the Box file.');

        return \PrasadChinwal\Box\Dto\BoxFile::from($response->collect());
    }

    /**
     * @see https://developer.box.com/reference/put-files-id/
     *
     * @throws Exception
     */
    public function update(array $attributes = []): \PrasadChinwal\Box\Dto\BoxFile
    {
        $response = $this->formRequest()
            ->asJson()
            ->put($this->endpoint.$this->id, $attributes)
            ->throwUnlessStatus(200)
            ->collect();

        return \PrasadChinwal\Box\Dto\BoxFile::from($response);
    }

    /**
     * @see https://developer.box.com/guides/uploads/direct/file/
     *
     * @throws RequestException
     */
    public function create(string $filepath, string $filename, array $attributes = []): Collection
    {
        return $this->multipartRequest()
            ->attach('file', file_get_contents($filepath), $filename)
            ->post($this->uploadUrl, $attributes)
            ->throwUnlessStatus(201)
            ->collect();
    }

    /**
     * @see https://developer.box.com/guides/uploads/direct/file/
     *
     * @throws RequestException
     * @throws \Throwable
     */
    public function write(string $filepath, $contents): \PrasadChinwal\Box\Dto\BoxFile
    {
        $filename = basename($filepath);

        $response = $this->multipartRequest()
            ->attach('file', $contents, $filename)
            ->post($this->uploadUrl, [
                'attributes' => json_encode([
                    'name' => $filename,
                    'parent' => [
                        'id' => $this->folderId,
                    ],
                ]),
            ])
            ->throwUnlessStatus(201)
            ->collect('entries');
        throw_if($response->isEmpty(), new OperationException('Could not create file in Box.'));

        return \PrasadChinwal\Box\Dto\BoxFile::from($response->first());
    }

    /**
     * @see https://developer.box.com/reference/delete-files-id/
     *
     * @throws Exception
     */
    public function delete(): Response
    {
        $response = $this->boxRequest()
            ->delete($this->endpoint.$this->id)
            ;

        $this->ensureStatus($response, 204, 'Could not delete the Box file.');

        return new Response('File has been deleted successfully');
    }
}
