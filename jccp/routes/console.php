<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\ManifestController;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('getManifest', function () {
    ManifestController->retrieve("http://nonexistant");
})->purpose('Display an inspiring quote');
