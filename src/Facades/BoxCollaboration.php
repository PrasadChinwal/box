<?php

namespace PrasadChinwal\Box\Facades;

use Illuminate\Support\Facades\Facade;

class BoxCollaboration extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'box-collaboration';
    }
}
