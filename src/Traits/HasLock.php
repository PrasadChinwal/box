<?php

namespace PrasadChinwal\Box\Traits;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

trait HasLock
{
    /**
     * @throws RequestException
     */
    public function getLocks(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->box->getAccessToken())
            ->asJson()
            ->get($this->lockEndpoint, [
                'folder_id' => $this->id
            ])
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @throws RequestException
     */
    public function lock(array $attributes): \Illuminate\Support\Collection
    {
        return Http::withToken($this->box->getAccessToken())
            ->asJson()
            ->post($this->lockEndpoint, $attributes)
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @throws RequestException
     */
    public function unlock(string $lockid): \Illuminate\Support\Collection
    {
        return Http::withToken($this->box->getAccessToken())
            ->asJson()
            ->delete($this->lockEndpoint. $lockid)
            ->throwUnlessStatus(204)
            ->collect();
    }
}
