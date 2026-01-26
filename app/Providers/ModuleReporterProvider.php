<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ModuleReporter;

class ModuleReporterProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public mixed $singletons = [
        ModuleReporter::class => ModuleReporter::class
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
