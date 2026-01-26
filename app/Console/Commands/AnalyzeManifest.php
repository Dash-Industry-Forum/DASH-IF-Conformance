<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
use App\Services\ModuleReporter;
use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\SpecManager;
use App\Modules\Schematron;
use App\Services\Downloader;
use App\Modules\DVB\MPD as DVBMpd;

class AnalyzeManifest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:analyze-manifest
                              {manifest_url : The URL of the manifest to analyze}
                              {--all : Enable all modules}
                              {--M|modules : List all modules}
                              {module?* : The modules to enable validation for}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze a manifest';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Tracer::newSpan("artisan: analyze-manifest")->measure(function () {
            session([
                'artisan' => true,
                'mpd' => $this->argument('manifest_url')
            ]);


            $mpdCache = app(MPDCache::class);
            $reporter = app(ModuleReporter::class);

            $specManager = app(SpecManager::class);


            if ($this->option('modules')) {
                echo \json_encode($specManager->specNames(), JSON_PRETTY_PRINT);
                return;
            }

            $toEnable = $this->argument("module");
            if ($this->option("all")) {
                $toEnable = $specManager->specNames();
            }

            $specManager->disableAll();

            foreach ($toEnable as $specification) {
                $specManager->enable($specification);
            }


            $specManager->validate();
            $specManager->validateSegments();


            echo \json_encode($reporter->serialize(true), JSON_PRETTY_PRINT);
        });
    }
}
