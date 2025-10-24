<?php

namespace App\Interfaces;

use App\Services\ModuleLogger;
use App\Services\Segment;
use App\Services\Manifest\Representation;
use App\Services\Manifest\AdaptationSet;
use Illuminate\Support\Facades\Log;

class Module
{
    public string $name = '';
    public bool $autoDetected = false;

    public function __construct()
    {
    }

    public function isAutoDetected(): bool
    {
        return $this->autoDetected;
    }

    public function validateMPD(): void
    {
    }

    /**
     * @param array<Segment> $segments
     **/
    public function validateSegments(Representation $representation, array $segments): void
    {
    }

    public function validateCrossAdaptationSet(AdaptationSet $adaptationSet): void
    {
    }
}
