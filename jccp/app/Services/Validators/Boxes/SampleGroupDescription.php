<?php

namespace App\Services\Validators\Boxes;

use App\Services\Validators\Boxes\SEIGDescription;

class SampleGroupDescription
{
    public string $groupingType;
    /**
     * @var array<SEIGDescription> $entries
     **/
    public array $entries;
}
