<?php

namespace PrasadChinwal\Box\Traits;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

trait CanWatermark
{
    /**
     * https://developer.box.com/reference/get-folders-id-watermark/
     *
     * @see https://developer.box.com/reference/get-files-id-watermark/
     *
     * @throws RequestException
     */
    public function getWatermark(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->getAccessToken())
            ->asJson()
            ->get($this->endpoint.$this->id.'/watermark')
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/put-folders-id-watermark/
     * @see https://developer.box.com/reference/put-files-id-watermark/
     */
    public function createWatermark(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->getAccessToken())
            ->asJson()
            ->put($this->endpoint.$this->id.'/watermark', [
                'watermark' => [
                    'imprint' => 'default',
                ],
            ])->collect();
    }

    /**
     * @see https://developer.box.com/reference/delete-folders-id-watermark/
     * @see https://developer.box.com/reference/delete-files-id-watermark/
     *
     * @throws RequestException
     */
    public function removeWatermark(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->getAccessToken())
            ->asJson()
            ->delete($this->endpoint.$this->id.'/watermark')
            ->throwUnlessStatus(204)
            ->collect();
    }
}
