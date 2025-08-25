<?php

namespace App\Services\Reporter;

use Illuminate\Support\Facades\Log;
use App\Services\Reporter\TestResult;
use App\Services\SpecManager;

class SubReporter
{
    /**
     * @var array<int, TestResult> $results
    **/
    public array $results = [];

    public function __construct()
    {
    }

    public function dependencyCheck(
        string $section,
        string $test,
        string $dependentModule,
        string $dependentSpec,
        string $dependentSection
    ): void {
        $specManager = app(SpecManager::class);
        $specManager->activateDependency($dependentModule);

        $this->results[] = new TestResult(
            section: $section,
            test: $test,
            severity: "DEPENDENT",
            message: "$dependentSpec::$dependentSection"
        );
    }

    public function test(
        string $section,
        string $test,
        bool $result,
        string $severity,
        string $pass_message,
        string $fail_message
    ): bool {
        $this->results[] = new TestResult(
            section: $section,
            test: $test,
            severity: ($result ? "PASS" : $severity),
            message: ($result ? $pass_message : $fail_message),
        );

        return $result;
    }


    /**
     * @return array<mixed>
     **/
    public function byCheck(bool $verbose): array
    {
        $res = array();

        foreach ($this->results as $result) {
            $section = $result->getSection();
            if (!array_key_exists($section, $res)) {
                $res[$section]  = [
                    'checks' => [],
                    'state' => 'PASS'
                ];
            }

            $test = $result->getTest();
            if (!array_key_exists($test, $res[$section]['checks'])) {
                $res[$section]['checks'][$test]  = [
                    'state' => 'PASS'
                ];
                if ($verbose) {
                    $res[$section]['checks'][$test]['messages'] = array();
                }
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
            if ($result->getSeverity() == "DEPENDENT") {
                $res[$section]['checks'][$test]['state'] = "DEPENDENT";
            }
        }
        ksort($res, SORT_NATURAL);

        return $res;
    }
}
