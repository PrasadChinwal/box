<?php

namespace PrasadChinwal\Box\Traits;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

trait CanCollaborate
{
    /**
     * @see https://developer.box.com/reference/get-folders-id-collaborations/
     * @see https://developer.box.com/reference/get-files-id-collaborations/
     *
     * @throws RequestException
     */
    public function getCollaboration(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->getAccessToken())
            ->asJson()
            ->get($this->endpoint.$this->id.'/collaborations')
            ->throwUnlessStatus(200)
            ->collect();
    }
}
