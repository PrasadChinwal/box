<?php

namespace PrasadChinwal\Box\File;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use PrasadChinwal\Box\Box;
use PrasadChinwal\Box\Contracts\FileContract;
use PrasadChinwal\Box\Traits\CanCollaborate;
use PrasadChinwal\Box\Traits\CanShare;
use PrasadChinwal\Box\Traits\CanWatermark;
use PrasadChinwal\Box\Traits\HasVersions;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BoxFile extends Box implements FileContract
{
    use CanCollaborate;
    use CanShare;
    use HasVersions;
    use CanWatermark;

    protected string $endpoint = 'https://api.box.com/2.0/files/';

    protected string $uploadUrl = 'https://upload.box.com/api/2.0/files/content';

    private string $sharedLinkUrl = 'https://api.box.com/2.0/shared_items/';

    protected ?string $id = null;

    protected string $sharedLink = '';

    protected ?string $sharedLinkPassword = null;

    protected Collection $result;

    protected ?string $folderId = null;

    public function __construct()
    {
        parent::__construct();
        $this->folderId = config('box.folder_id');
    }

    /**
     * @throws Exception
     */
    public function whereId(string $id): BoxFile
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param string $id
     * @return BoxFile
     */
    public function inFolder(string $id): BoxFile
    {
        $this->folderId = $id;

        return $this;
    }

    /**
     * @see https://developer.box.com/reference/get-files-id/
     *
     * @throws Exception
     */
    public function info(): Collection
    {
        return Http::withToken($this->getAccessToken())
            ->get($this->endpoint.$this->id)
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @return string
     * @throws FileNotFoundException
     */
    public function contents(): string
    {
        $fileInfo = $this->info();
        $response = Http::withToken($this->getAccessToken())
            ->sink(storage_path("/app/{$fileInfo['name']}"))
            ->get($this->endpoint.$this->id.'/content');
        if ($response->noContent()) {
            throw new FileNotFoundException('The file information was not found!');
        }
        dump(\Storage::get(storage_path("/app/{$fileInfo['name']}")));
        return $response;
    }

    /**
     * @throws FileNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    public function downloadFile(): BinaryFileResponse
    {
        $fileInfo = $this->info();
        $response = Http::withToken($this->getAccessToken())
            ->sink(storage_path("/app/{$fileInfo['name']}"))
            ->get($this->endpoint.$this->id.'/content');
        if ($response->noContent()) {
            throw new FileNotFoundException('The file information was not found!');
        }

        if (! $response->successful()) {
            throw new Exception('Could not find File!');
        }

        return response()->download(storage_path("/app/{$fileInfo['name']}"));
    }

    /**
     * Returns the download url for the file
     *
     * @throws Exception
     */
    public function getDownloadUrl(): string
    {
        $response = Http::withToken($this->getAccessToken())
            ->withOptions([
                'allow_redirects' => false
            ])
            ->get($this->endpoint.$this->id.'/content');

        if ($response->status() !== 302) {
            throw new Exception('Could not find File!');
        }
        if(!$response->header('location')) {
            throw new Exception('File download url not found!');
        }
        return $response->header('location');
    }

    /**
     * @see https://developer.box.com/reference/get-files-id-thumbnail-id/
     *
     * @throws RequestException
     */
    public function thumbnail(string $extension, int $width = null, int $height = null): Collection
    {
        if (! in_array($extension, ['.png', '.jpg'])) {
            throw new ValidationException('File extension not supported!');
        }

        return Http::withToken($this->getAccessToken())
            ->get($this->endpoint.$this->id.'/thumbnail'.$extension)
            ->throwUnlessStatus([200, 201])
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/post-files-id-copy/
     *
     * @throws Exception
     */
    public function copy(array $attributes = []): Collection
    {
        $response = Http::asForm()
            ->withToken($this->getAccessToken())
            ->asJson()
            ->post($this->endpoint.$this->id.'/copy', $attributes);

        if ($response->noContent()) {
            throw new Exception('The file information was not found!');
        }
        if (! $response->successful()) {
            throw new Exception('Could not find File information!');
        }

        return $response->collect();
    }

    /**
     * @see https://developer.box.com/reference/put-files-id/
     *
     * @throws Exception
     */
    public function update(array $attributes = []): Collection
    {
        return Http::asForm()
            ->withToken($this->getAccessToken())
            ->asJson()
            ->put($this->endpoint.$this->id, $attributes)
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @see https://developer.box.com/guides/uploads/direct/file/
     *
     * @throws RequestException
     */
    public function create(string $filepath, string $filename, array $attributes = []): Collection
    {
        return Http::asMultipart()
            ->withToken($this->getAccessToken())
            ->attach('file', file_get_contents($filepath), $filename)
            ->post($this->uploadUrl, $attributes)
            ->throwUnlessStatus(201)
            ->collect();
    }

    /**
     * @see https://developer.box.com/guides/uploads/direct/file/
     *
     * @throws RequestException
     */
    public function write(string $filepath, $contents): Collection
    {
        $filename = basename($filepath);
        return Http::asMultipart()
            ->withToken($this->getAccessToken())
            ->attach('file', $contents, $filename)
            ->post($this->uploadUrl, [
                'attributes' => json_encode([
                    'name' => $filename,
                    'parent' => [
                        'id' => $this->folderId
                    ],
                ]),
            ])
            ->throwUnlessStatus(201)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/delete-files-id/
     *
     * @throws Exception
     */
    public function delete(): Response
    {
        $response = Http::withToken($this->getAccessToken())
            ->delete($this->endpoint.$this->id)
            ->throwUnlessStatus(302);

        if ($response->noContent()) {
            return new Response('File has been deleted successfully');
        }
        throw new Exception('Could not delete File!');
    }
}
