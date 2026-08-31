<?php

namespace PrasadChinwal\Box\Traits;

use Illuminate\Http\Client\RequestException;

trait HasLock
{
    /**
     * @see https://developer.box.com/reference/get-folder-locks/
     *
     * @throws RequestException
     */
    public function getLocks(): \Illuminate\Support\Collection
    {
        return $this->jsonRequest()
            ->get($this->lockEndpoint, [
                'folder_id' => $this->id,
            ])
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/post-folder-locks/
     *
     * @throws RequestException
     */
    public function lock(array $attributes): \Illuminate\Support\Collection
    {
        return $this->jsonRequest()
            ->post($this->lockEndpoint, $attributes)
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/delete-folder-locks-id/
     *
     * @throws RequestException
     */
    public function unlock(string $lockid): \Illuminate\Support\Collection
    {
        return $this->jsonRequest()
            ->delete($this->lockEndpoint.$lockid)
            ->throwUnlessStatus(204)
            ->collect();
    }
}
