<?php

namespace PrasadChinwal\Box\Traits;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

trait CanWatermark
{

    /**
     * @throws RequestException
     */
    public function getWatermark(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->box->getAccessToken())
            ->asJson()
            ->get($this->endpoint . $this->id . '/watermark')
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     */
    public function createWatermark(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->box->getAccessToken())
            ->asJson()
            ->put($this->endpoint . $this->id . '/watermark', [
                'watermark' => [
                    'imprint' => 'default'
                ]
            ])->collect();
    }

    /**
     * @return \Illuminate\Support\Collection
     * @throws RequestException
     */
    public function removeWatermark(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->box->getAccessToken())
            ->asJson()
            ->delete($this->endpoint . $this->id . '/watermark')
            ->throwUnlessStatus(204)
            ->collect();
    }
}
