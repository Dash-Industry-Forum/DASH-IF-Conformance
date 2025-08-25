<?php

namespace App\Interfaces;

use App\Services\ModuleLogger;
use Illuminate\Support\Facades\Log;

class Module
{
    public string $name = '';
    public bool $autoDetected = false;

    public function __construct()
    {
    }

    public function isAutoDetected(): bool
    {
        return $this->autoDetected;
    }

    public function validateMPD(): void
    {
    }
}
