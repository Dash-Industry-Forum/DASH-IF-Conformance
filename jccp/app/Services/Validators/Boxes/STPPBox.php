<?php

namespace App\Services\Validators\Boxes;

use App\Services\Validators\Boxes\SampleDescription;

class STPPBox extends SampleDescription
{
    public string $codingname;
    public string $namespace;
    public string $schemaLocation;
    public string $auxiliaryMimeTypes;
}
