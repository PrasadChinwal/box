<?php

namespace PrasadChinwal\Box\Folder;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use PrasadChinwal\Box\Box;
use PrasadChinwal\Box\Contracts\FolderContract;
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

    /**
     * @throws Exception
     */
    public function whereId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @see https://developer.box.com/reference/get-folders-id/
     *
     * @throws Exception
     */
    public function info(): Collection
    {
        return $this->boxRequest()
            ->get($this->endpoint.$this->id)
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/get-folders-id-items/
     *
     * @throws Exception
     */
    public function items(int $limit = 1000, int $offset = 0): Collection
    {
        return $this->boxRequest()
            ->get($this->endpoint.$this->id.'/items', [
                'limit' => $limit,
                'offset' => $offset,
                'fields' => 'name,size,modified_at,type,id',
            ])
            ->throwUnlessStatus(200)
            ->collect();
    }

    public function allEntries(): array
    {
        $entries = [];
        $offset = 0;
        $limit = 1000;
        $totalCount = null;

        do {
            $response = $this->items(limit: $limit, offset: $offset);
            $pageEntries = $response->get('entries', []);
            $entries = array_merge($entries, $pageEntries);
            $totalCount = (int) $response->get('total_count', count($entries));
            $offset += count($pageEntries);
        } while ($pageEntries !== [] && $offset < $totalCount);

        return $entries;
    }

    /**
     * @see https://developer.box.com/reference/post-folders/
     *
     * @throws Exception
     */
    public function create(array $attributes): Collection
    {
        return $this->jsonRequest()
            ->post($this->endpoint, $attributes)
            ->throwUnlessStatus(201)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/post-folders-id-copy/
     *
     * @throws Exception
     */
    public function copy(array $attributes): Collection
    {
        return $this->jsonRequest()
            ->post($this->endpoint.$this->id.'/copy', $attributes)
            ->throwUnlessStatus(201)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/put-folders-id/
     *
     * @throws Exception
     */
    public function update(array $attributes): Collection
    {
        return $this->jsonRequest()
            ->put($this->endpoint.$this->id, $attributes)
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/delete-folders-id/
     *
     * @throws Exception
     */
    public function delete(bool $recursive = false): Response
    {
        $this->boxRequest()
            ->delete($this->endpoint.$this->id, [
                'recursive' => $recursive,
            ])
            ->throwUnlessStatus(204);

        return new Response('Folder has been deleted successfully');
    }
}
