<?php

namespace App\Services\Reporter;

use Illuminate\Support\Facades\Log;
use App\Services\Reporter\TestResult;

class SubReporter
{
    /**
     * @var array<int, TestResult> $results
    **/
    public array $results = [];

    public function __construct()
    {
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
            $c = $result->getSection() . " - " . $result->getTest();
            if (!in_array($c, $res)) {
                $res[$c]  = array(
                    'state' => 'PASS'
                );
                if ($verbose) {
                    $res[$c]['messages'] = array();
                }
            }

            if ($verbose) {
                $res[$c]['messages'][] = $result->getMessage();
            }
            if ($result->getSeverity() == "WARN") {
                if ($res[$c]['state'] != "FAIL") {
                    $res[$c]['state'] = "WARN";
                }
            }
            if ($result->getSeverity() == "FAIL") {
                $res[$c]['state'] = "FAIL";
            }
        }

        return $res;
    }
}
