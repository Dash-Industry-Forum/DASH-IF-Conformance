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

abstract class AdaptationComponent extends ModuleComponent
{
    public function __construct(string $component, ReporterContext $context)
    {
        parent::__construct($component, $context);
    }

    public function withAdaptationSet(AdaptationSet $adaptationSet): void
    {
        $cachePath = cache_path([$this->component, $adaptationSet->path()]);
        $serialized = Cache::rememberForever(
            $cachePath,
            function () use ($adaptationSet) {
                $this->validateAdaptationSet($adaptationSet);
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

    public function withInitSegment(Representation $representation, Segment $segment): void
    {
    }
    public function validateInitSegment(Representation $representation, Segment $segment): void
    {
    }

    public function withSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
    }
    public function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
    }
}
