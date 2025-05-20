<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\MPDHandler;

class MPDHandlerProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public $singletons = [
        MPDHandler::class => MPDHandler::class
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
