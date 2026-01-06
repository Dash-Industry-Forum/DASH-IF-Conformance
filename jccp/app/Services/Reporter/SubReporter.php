<?php

namespace App\Services\Reporter;

use Illuminate\Support\Facades\Log;
use App\Services\Reporter\TestResult;
use App\Services\Reporter\TestCase;
use App\Services\SpecManager;

class SubReporter
{
    /**
     * @var array<TestCase> $cases
    **/
    public array $cases = [];

    public function __construct()
    {
    }

    public function verdict(): string
    {
        $verdict = "PASS";

        foreach ($this->cases as $case) {
            foreach ($case->results as $testResult) {
                if ($testResult == "FAIL") {
                    return "FAIL";
                }
                if ($testResult == "WARN") {
                    $verdict = "WARN";
                }
            }
        }

        return $verdict;
    }

    public function &dependencyAdd(
        string $section,
        string $test,
        string $dependentModule,
        string $dependentSpec,
        string $dependentSection,
        string $skipReason = '',
    ): TestCase {
        $this->cases[] = new TestCase(
            section: $section,
            test: "{$dependentSpec}::${dependentSection}",
            skipReason: $skipReason
        );

        return $this->cases[array_key_last($this->cases)];
    }

    public function &add(
        string $section,
        string $test,
        string $skipReason
    ): TestCase {
        $this->cases[] = new TestCase(
            section: $section,
            test: $test,
            skipReason: $skipReason
        );

        return $this->cases[array_key_last($this->cases)];
    }

    /**
     * @return array<mixed>
     **/
    public function byCheck(bool $verbose): array
    {
        $res = array();

        foreach ($this->cases as $case) {
            $section = $case->section;
            if (!array_key_exists($section, $res)) {
                $res[$section]  = [
                'checks' => [],
                'state' => 'SKIP'
                ];
            }

            $test = $case->test;
            if (!array_key_exists($test, $res[$section]['checks'])) {
                $res[$section]['checks'][$test]  = [
                    'state' => 'SKIP',
                    'messages' => [$case->skipReason]
                ];
            }
            foreach ($case->results as $result) {
                if ($res[$section]['checks'][$test]['state'] == "SKIP") {
                    $res[$section]['checks'][$test]['state'] = "PASS";
                    $res[$section]['checks'][$test]['messages'] = [];
                }
                if ($res[$section]['state'] == "SKIP") {
                    $res[$section]['state'] = "PASS";
                }

                if ($verbose) {
                    $res[$section]['checks'][$test]['messages'][] = $result->getMessage();
                }
                if ($result->getSeverity() == "WARN") {
                    if ($res[$section]['checks'][$test]['state'] != "FAIL") {
                        $res[$section]['checks'][$test]['state'] = "WARN";
                    }
                    if ($res[$section]['state'] != "FAIL") {
                        $res[$section]['state'] = "WARN";
                    }
                }
                if ($result->getSeverity() == "FAIL") {
                    $res[$section]['checks'][$test]['state'] = "FAIL";
                    $res[$section]['state'] = "FAIL";
                }
                if ($result->getSeverity() == "DEPENDENCY") {
                    $res[$section]['checks'][$test]['state'] = "DEPENDENCY";
                }
            }
        }

        ksort($res, SORT_NATURAL);

        return $res;
    }
}
