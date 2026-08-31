<?php

namespace PrasadChinwal\Box\Traits;

use Illuminate\Http\Client\RequestException;

trait HasVersions
{
    /**
     * @see https://developer.box.com/reference/get-files-id-versions/
     *
     * @throws RequestException
     */
    public function versions(): \Illuminate\Support\Collection
    {
        return $this->boxRequest()
            ->get($this->endpoint.$this->id.'/versions')
            ->throwUnlessStatus(200)
            ->collect();
    }
}
