<?php

namespace PrasadChinwal\Box\Facades;

use Illuminate\Support\Facades\Facade;

class BoxFile extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'box-file';
    }
}
