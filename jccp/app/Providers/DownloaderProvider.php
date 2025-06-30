<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Downloader;

class DownloaderProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public mixed $singletons = [
        Downloader::class => Downloader::class,
    ];
}
