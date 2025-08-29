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
            return $value->equals($context);
        });
        if ($key === null) {
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

        $this->resolveDependencies($res);

        return $res;
    }


    /**
     * @param array<array<mixed>> $serialized
     **/
    public function resolveDependencies(array &$serialized): void
    {
        foreach ($serialized as &$element) {
            foreach ($element as &$module) {
                foreach ($module as &$section) {
                    foreach ($section['checks'] as &$check) {
                        if ($check['state'] != "DEPENDENT") {
                            continue;
                        }
                        $depArray = explode('::', $check['messages'][0]);

                        if (!array_key_exists($depArray[0], $element)) {
                            $check['messages'][] = "Unable to resolve dependent spec";
                            $check['state'] = "FAIL";
                        }
                        if (!array_key_exists($depArray[1], $element[$depArray[0]])) {
                            $check['messages'][] = "Unable to resolve dependent section";
                            $check['state'] = "FAIL";
                        }
                        $dependentState = $element[$depArray[0]][$depArray[1]]['state'];
                        $check['state'] = $dependentState;
                        if ($dependentState == "WARN") {
                            if ($section['state'] != "FAIL") {
                                $section['state'] = "WARN";
                            }
                        }
                        if ($dependentState == "FAIL") {
                            $section['state'] = "FAIL";
                        }
                    }
                }
            }
        }
    }
}
