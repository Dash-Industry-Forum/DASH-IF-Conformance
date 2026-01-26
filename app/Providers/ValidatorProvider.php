<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Validators\MP4Box;

class ValidatorProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public mixed $singletons = [
        MP4Box::class => MP4Box::class,
    ];
}
