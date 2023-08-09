<?php

namespace PrasadChinwal\Box\Traits;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

trait CanCollaborate
{
    /**
     * @throws RequestException
     */
    public function getCollaboration(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->box->getAccessToken())
            ->asJson()
            ->get($this->endpoint . $this->id . '/collaborations')
            ->throwUnlessStatus(200)
            ->collect();
    }
}
