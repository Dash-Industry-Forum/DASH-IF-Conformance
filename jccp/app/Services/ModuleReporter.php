<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Services\Reporter\Context;
use App\Services\Reporter\SubReporter;

class ModuleReporter
{
    /**
        * @var array<Context> $contextList
    **/
    private array $contextList = [];
    /**
        * @var array<SubReporter> $reportByContext
    **/
    private array $reportByContext = [];

    public function __construct()
    {
    }

    public function &context(Context $context): SubReporter
    {
        $key = array_find_key($this->contextList, function (Context $value) use ($context) {
            return $value == $context;
        });
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

    /**
     * @return array<array<mixed>>
     **/
    public function serialize(bool $verbose = false): array
    {
        $res = [];


        foreach ($this->contextList as $i => $ctx) {
            $specVersion = $ctx->spec . " - " . $ctx->version;

            if (!array_key_exists($ctx->element, $res)) {
                $res[$ctx->element] = [];
            }
            if (!array_key_exists($specVersion, $res[$ctx->element])) {
                $res[$ctx->element][$specVersion] = [];
            }

            $res[$ctx->element][$specVersion] = array_merge(
                $res[$ctx->element][$specVersion],
                $this->reportByContext[$i]->byCheck($verbose)
            );
        }


        return $res;
    }
}
