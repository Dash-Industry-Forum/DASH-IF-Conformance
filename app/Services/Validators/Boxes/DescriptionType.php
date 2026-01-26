<?php

namespace App\Services\Validators\Boxes;

enum DescriptionType
{
    case Video;
    case Audio;
    case Text;
    case Subtitle;
}
