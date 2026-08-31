<?php

namespace PrasadChinwal\Box\Traits;

use Illuminate\Http\Client\RequestException;

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
        return $this->jsonRequest()
            ->get($this->endpoint.$this->id.'/collaborations')
            ->throwUnlessStatus(200)
            ->collect();
    }
}
