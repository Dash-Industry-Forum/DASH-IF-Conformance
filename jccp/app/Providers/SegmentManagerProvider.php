<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SegmentManager;

class SegmentManagerProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public mixed $singletons = [
        SegmentManager::class => SegmentManager::class,
    ];
}
