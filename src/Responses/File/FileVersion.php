<?php

namespace PrasadChinwal\Box\Responses\File;

use Spatie\LaravelData\Data;

class FileVersion extends Data
{
    public string $id;

    public string $type;

    public string $sha1;
}