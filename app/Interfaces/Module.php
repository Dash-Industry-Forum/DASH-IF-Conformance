<?php

namespace App\Interfaces;

use App\Services\ModuleLogger;
use App\Services\Segment;
use App\Services\SpecManager;
use App\Services\Manifest\Period;
use App\Services\Manifest\Representation;
use App\Services\Manifest\AdaptationSet;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Context\ScopeInterface;
use Illuminate\Support\Facades\Log;

class Module
{
    public string $name = '';
    public bool $autoDetected = false;

    private SpanInterface $mySpan;
    private ScopeInterface $myScope;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function isAutoDetected(): bool
    {
        return $this->autoDetected;
    }

    public function enableDependencies(SpecManager $manager): void
    {
    }

    public function validateMPD(): void
    {
    }

    public function activate(): void
    {
        $this->mySpan = Tracer::newSpan("Module - $this->name")->start();
        $this->myScope = $this->mySpan->activate();
    }
    public function deactivate(): void
    {
        $this->myScope->detach();
        $this->mySpan->end();
    }

    /**
     * @param array<Segment> $segments
     **/
    public function validateSegments(Representation $representation, array $segments): void
    {
    }

    public function validatePeriod(Period $period): void
    {
    }

    public function validateCrossAdaptationSet(AdaptationSet $adaptationSet): void
    {
    }

    public function validateMultiPeriod(Period $firstPeriod, Period $secondPeriod): void
    {
    }
}
