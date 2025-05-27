<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ModuleLogger;

class ModuleLoggerProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public mixed $singletons = [
        ModuleLogger::class => ModuleLogger::class
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
