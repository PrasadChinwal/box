<?php

namespace PrasadChinwal\Box\Folder;

use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use PrasadChinwal\Box\Box;
use PrasadChinwal\Box\Contracts\FolderContract;
use PrasadChinwal\Box\Responses\Folder\FolderResponse;
use PrasadChinwal\Box\Traits\CanCollaborate;
use PrasadChinwal\Box\Traits\CanShare;
use PrasadChinwal\Box\Traits\HasLock;

class BoxFolder extends Box implements FolderContract
{
    use CanCollaborate;
    use CanShare;
    use HasLock;

    protected string $endpoint = 'https://api.box.com/2.0/folders/';

    protected string $sharedLinkUrl = 'https://api.box.com/2.0/folders/';

    protected string $lockEndpoint = 'https://api.box.com/2.0/folder_locks';

    protected ?string $id = '0';

    protected Collection $result;

    public function __construct()
    {
        parent::__construct();
        $this->id = config('box.folder_id');
    }

    /**
     * @throws Exception
     */
    public function whereId($id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @see https://developer.box.com/reference/get-folders-id/
     *
     * @throws Exception
     */
    public function info(): FolderResponse
    {
        $response = Http::withToken($this->getAccessToken())
            ->get($this->endpoint.$this->id)
            ->throwUnlessStatus(200)
            ->collect();

        return FolderResponse::from($response);
    }

    /**
     * @see https://developer.box.com/reference/get-folders-id-items/
     *
     * @throws Exception
     */
    public function items(): Collection
    {
        $response = Http::withToken($this->getAccessToken())
            ->get($this->endpoint.$this->id.'/items')
            ->throwUnlessStatus(200)
            ->collect('entries');

        return FolderResponse::collect($response);
    }

    /**
     * @see https://developer.box.com/reference/post-folders/
     *
     * @throws Exception
     */
    public function create(array $attributes): FolderResponse
    {
        $response = Http::asJson()
            ->withToken($this->getAccessToken())
            ->post($this->endpoint, $attributes)
            ->throwUnlessStatus(201)
            ->collect();

        return FolderResponse::from($response);
    }

    /**
     * @throws RequestException
     */
    public function createDirectory(string $name): Collection
    {
        $attributes = [
            'name' => $name,  // The name for the new folder. max length 255
            'parent' => [  // The parent folder to create the new folder within
                'id' => $this->id,
            ],
        ];

        return Http::asJson()
            ->withToken($this->getAccessToken())
            ->post($this->endpoint.$this->id, $attributes)
            ->throwUnlessStatus(201)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/post-folders-id-copy/
     *
     * @throws Exception
     */
    public function copy(array $attributes): FolderResponse
    {
        $response = Http::asJson()
            ->withToken($this->getAccessToken())
            ->post($this->endpoint.$this->id.'/copy', $attributes)
            ->throwUnlessStatus(201)
            ->collect();

        return FolderResponse::from($response);
    }

    /**
     * @see https://developer.box.com/reference/put-folders-id/
     *
     * @throws Exception
     */
    public function update(array $attributes): FolderResponse
    {
        $response = Http::asJson()
            ->withToken($this->getAccessToken())
            ->put($this->endpoint.$this->id, $attributes)
            ->throwUnlessStatus(200)
            ->collect();

        return FolderResponse::from($response);
    }

    /**
     * @see https://developer.box.com/reference/delete-folders-id/
     *
     * @throws Exception
     */
    public function delete(bool $recursive = false): Response|Exception
    {
        Http::withToken($this->getAccessToken())
            ->delete($this->endpoint.$this->id.'?recursive='.$recursive)
            ->throwUnlessStatus(204);

        return new Response('Folder has been deleted successfully');
    }
}
