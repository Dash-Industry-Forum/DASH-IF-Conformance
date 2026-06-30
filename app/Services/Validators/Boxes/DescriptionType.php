<?php

namespace App\Services\Validators\Boxes;

enum DescriptionType
{
    case Unknown;
    case Video;
    case Audio;
    case Text;
    case Subtitle;
}
