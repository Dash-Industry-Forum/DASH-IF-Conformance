<?php

namespace App\Services\Reporter;

use Illuminate\Support\Facades\Log;

class TestResult
{
    private readonly string $section;
    private readonly string $test;
    private readonly string $message;
    private readonly string $severity;

    public function __construct(
        string $section = 'Unknown',
        string $test = '',
        string $message = '',
        string $severity = 'PASS'
    ) {
        $this->section = $section;
        $this->test = $test;
        $this->severity = $severity;
        $msgLead = "";
        if ($severity == "PASS") {
            $msgLead = "✓ ";
        }
        if ($severity == "WARN") {
            $msgLead = "! ";
        }
        if ($severity == "FAIL") {
            $msgLead = "✗ ";
        }
        $this->message = "$msgLead$message";
    }

    public function getSection(): string
    {
        return $this->section;
    }
    public function getTest(): string
    {
        return $this->test;
    }
    public function getMessage(): string
    {
        return $this->message;
    }
    public function getSeverity(): string
    {
        return $this->severity;
    }
}
