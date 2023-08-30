<?php

namespace PrasadChinwal\Box\Facades;

use Illuminate\Support\Facades\Facade;

class Box extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'box';
    }
}
