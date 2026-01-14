<?php

namespace App\Interfaces;

use App\Services\ModuleLogger;
use App\Services\Segment;
use App\Services\Manifest\Period;
use App\Services\Manifest\Representation;
use App\Services\Manifest\AdaptationSet;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Context\ScopeInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

abstract class ModuleComponent implements ModuleComponentInterface
{
    public string $component = '';
    public SubReporter $reporter;

    private SpanInterface $span;
    private ScopeInterface $scope;

    public function __construct(string $component, ReporterContext $context)
    {
        $moduleReporter = app(ModuleReporter::class);

        $separatorPos = strrpos($component, "\\");
        if ($separatorPos === false) {
            $this->component = $component;
        } else {
            $this->component = substr($component, $separatorPos + 1);
        }
        $this->reporter = &$moduleReporter->context($context);

        $this->span = Tracer::newSpan($this->component)->start();
        $this->scope = $this->span->activate();
    }

    public function __destruct()
    {
        $this->scope->detach();
        $this->span->end();
    }
}
