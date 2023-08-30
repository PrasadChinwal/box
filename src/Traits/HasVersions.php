<?php

namespace PrasadChinwal\Box\Traits;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

trait HasVersions
{
    /**
     * @see https://developer.box.com/reference/get-files-id-versions/
     *
     * @throws RequestException
     */
    public function versions(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->getAccessToken())
            ->get($this->endpoint.$this->id.'/versions')
            ->throwUnlessStatus(200)
            ->collect();
    }
}
