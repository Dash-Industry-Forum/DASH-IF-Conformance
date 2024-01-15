<?php

namespace DASHIF\Boxes;

enum DescriptionType
{
    case Video;
    case Audio;
    case Text;
    case Subtitle;
}

class SampleDescription
{
    public DescriptionType $type;
    public $codec;
}
