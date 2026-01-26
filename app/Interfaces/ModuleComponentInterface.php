<?php

namespace App\Interfaces;

use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Segment;

interface ModuleComponentInterface
{
    public function withAdaptationSet(AdaptationSet $adaptationSet): void;
    public function validateAdaptationSet(AdaptationSet $adaptationSet): void;

    /**
     * @param array<Segment> $segments
     **/
    public function withSegmentList(Representation $representation, array $segments): void;
    /**
     * @param array<Segment> $segments
     **/
    public function validateSegmentList(Representation $representation, array $segments): void;

    public function withInitSegment(Representation $representation, Segment $segment): void;
    public function validateInitSegment(Representation $representation, Segment $segment): void;

    public function withSegment(Representation $representation, Segment $segment, int $segmentIndex): void;
    public function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void;
}
