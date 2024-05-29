<?php

namespace PrasadChinwal\Box\Traits;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

trait HasLock
{
    /**
     * @see https://developer.box.com/reference/get-folder-locks/
     *
     * @throws RequestException|\Illuminate\Http\Client\ConnectionException
     */
    public function getLocks(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->getAccessToken())
            ->asJson()
            ->get($this->lockEndpoint, [
                'folder_id' => $this->id,
            ])
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/post-folder-locks/
     *
     * @throws RequestException|\Illuminate\Http\Client\ConnectionException
     */
    public function lock(array $attributes): \Illuminate\Support\Collection
    {
        return Http::withToken($this->getAccessToken())
            ->asJson()
            ->post($this->lockEndpoint, $attributes)
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/delete-folder-locks-id/
     *
     * @throws RequestException|\Illuminate\Http\Client\ConnectionException
     */
    public function unlock(string $lockid): \Illuminate\Support\Collection
    {
        return Http::withToken($this->getAccessToken())
            ->asJson()
            ->delete($this->lockEndpoint.$lockid)
            ->throwUnlessStatus(204)
            ->collect();
    }
}
