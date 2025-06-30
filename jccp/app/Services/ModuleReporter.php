<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Services\ReporterContext;
use App\Services\SubReporter;

class ModuleReporter
{
    /**
        * @var array<ReporterContext> $contextList
    **/
    private array $contextList = [];
    /**
        * @var array<SubReporter> $reportByContext
    **/
    private array $reportByContext = [];

    public function __construct()
    {
    }

    public function &context(ReporterContext $context): SubReporter
    {
        $key = array_find_key($this->contextList, function (ReporterContext $value) use ($context) {
            return $value == $context;
        });
        echo "  Found key " . $key . "\n";
        if ($key == null) {
            $this->contextList[] = $context;
            $this->reportByContext[] = new SubReporter();
            $key = array_key_last($this->contextList);
        }
        return $this->reportByContext[$key];
    }

    /**
     * @return array<string>
     **/
    public function knownContexts(): array
    {
        $result = [];
        foreach ($this->contextList as $context) {
            $result[] = $context->toString();
        }
        return $result;
    }
}
