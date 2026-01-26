<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MPDCache;
use App\Services\Manifest\PeriodCache;

class MPDCacheProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public mixed $singletons = [
        MPDCache::class => MPDCache::class,
    ];
}
