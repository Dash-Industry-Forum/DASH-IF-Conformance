<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SpecManager;

class SpecManagerProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public mixed $singletons = [
        SpecManager::class => SpecManager::class,
    ];
}
