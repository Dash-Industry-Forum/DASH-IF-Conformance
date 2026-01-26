<?php

namespace App\Interfaces\ModuleComponents;

use App\Interfaces\ModuleComponent;
use App\Services\Reporter\Context as ReporterContext;
//
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Segment;
//
use Illuminate\Support\Facades\Cache;

abstract class SegmentComponent extends ModuleComponent
{
    public function __construct(string $component, ReporterContext $context)
    {
        parent::__construct($component, $context);
    }

    public function withSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        $cachePath = cache_path([$this->component, $representation->path(), "$segmentIndex"]);
        $serialized = Cache::rememberForever(
            $cachePath,
            function () use ($representation, $segment, $segmentIndex) {
                $this->validateSegment($representation, $segment, $segmentIndex);
                return $this->reporter->store();
            }
        );
        $this->reporter->restore($serialized);
    }

    public function withSegmentList(Representation $representation, array $segments): void
    {
    }
    public function validateSegmentList(Representation $representation, array $segments): void
    {
    }

    public function withAdaptationSet(AdaptationSet $adaptationSet): void
    {
    }
    public function validateAdaptationSet(AdaptationSet $adaptationSet): void
    {
    }

    public function withInitSegment(Representation $representation, Segment $segment): void
    {
    }
    public function validateInitSegment(Representation $representation, Segment $segment): void
    {
    }
}
