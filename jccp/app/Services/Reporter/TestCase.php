<?php

namespace App\Services\Reporter;

use Illuminate\Support\Facades\Log;
use App\Services\Reporter\TestResult;
use App\Services\SpecManager;

class TestCase
{
    /**
     * @var array<int, TestResult> $results
    **/
    public array $results = [];

    public string $section;
    public string $test;
    public string $skipReason;

    public function __construct(string $section, string $test, string $skipReason)
    {
        $this->section = $section;
        $this->test = $test;
        $this->skipReason = "🛈 " . $skipReason;
    }

    public function pathAdd(
        bool $result,
        string $severity,
        string $path,
        string $pass_message,
        string $fail_message
    ): bool {
        return $this->add(
            result: $result,
            severity: $severity,
            pass_message: "$path - $pass_message",
            fail_message: "$path - $fail_message"
        );
    }

    public function add(
        bool $result,
        string $severity,
        string $pass_message,
        string $fail_message
    ): bool {
        $passResult = $severity;
        if ($passResult != "INFO" && $passResult != "DEPENDENCY") {
            $passResult = "PASS";
        }
        $this->results[] = new TestResult(
            severity: ($result ? $passResult : $severity),
            message: ($result ? $pass_message : $fail_message),
        );
        return $result;
    }
}
