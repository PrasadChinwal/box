<?php

namespace PrasadChinwal\Box\Folder;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use PrasadChinwal\Box\Box;
use PrasadChinwal\Box\Contracts\FolderContract;
use PrasadChinwal\Box\Traits\CanCollaborate;
use PrasadChinwal\Box\Traits\CanShare;
use PrasadChinwal\Box\Traits\HasLock;

class BoxFolder implements FolderContract
{
    use CanCollaborate;
    use HasLock;
    use CanShare;

    protected string $endpoint = 'https://api.box.com/2.0/folders/';

    protected string $sharedLinkUrl = 'https://api.box.com/2.0/folders/';

    protected string $lockEndpoint = 'https://api.box.com/2.0/folder_locks';

    protected ?string $id = null;

    protected Collection $result;

    protected Box $box;

    /**
     * @throws Exception
     */
    public function __construct(Box $box)
    {
        $this->box = $box;
    }

    /**
     * @throws Exception
     */
    public static function whereId($id): static
    {
        $instance = new static(new Box());
        $instance->id = $id;
        return $instance;
    }

    /**
     * @throws Exception
     */
    public function info(): Collection
    {
        return Http::withToken($this->box->getAccessToken())
            ->get($this->endpoint . $this->id)
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @return Collection
     * @throws Exception
     */
    public function items(): Collection
    {
        return Http::withToken($this->box->getAccessToken())
            ->get($this->endpoint . $this->id . '/items')
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @param array $attributes
     * @return Collection
     * @throws Exception
     */
    public function create(array $attributes): Collection
    {
        return Http::asJson()
            ->withToken($this->box->getAccessToken())
            ->post($this->endpoint . $this->id, $attributes)
            ->throwUnlessStatus(201)
            ->collect();
    }

    /**
     * @param array $attributes
     * @return Collection
     * @throws Exception
     */
    public function copy(array $attributes): Collection
    {
        return Http::asJson()
            ->withToken($this->box->getAccessToken())
            ->post($this->endpoint . $this->id, $attributes)
            ->throwUnlessStatus(201)
            ->collect();
    }

    /**
     * @param array $attributes
     * @return Collection
     * @throws Exception
     */
    public function update(array $attributes): Collection
    {
        return Http::asJson()
            ->withToken($this->box->getAccessToken())
            ->put($this->endpoint . $this->id, $attributes)
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @param array $attributes
     * @return Collection
     * @throws Exception
     */
    public function createSharedLink(array $attributes): Collection
    {
        return Http::asForm()
            ->withToken($this->box->getAccessToken())
            ->asJson()
            ->put($this->endpoint . $this->id, $attributes)
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @throws Exception
     */
    public function delete(bool $recursive = false): Response|Exception
    {
        Http::withToken($this->box->getAccessToken())
            ->delete($this->endpoint . $this->id . '?recursive=' . $recursive)
            ->throwUnlessStatus(204);
        return new Response('Folder has been deleted successfully');
    }

}
