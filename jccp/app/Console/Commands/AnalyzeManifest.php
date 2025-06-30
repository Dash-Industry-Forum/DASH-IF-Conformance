<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
use App\Services\ModuleReporter;
use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\Schematron;
use App\Services\Downloader;
use App\Modules\DVB\MPD as DVBMpd;

class AnalyzeManifest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:analyze-manifest {manifest_url}';

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

            echo " - " .
                $mpdCache->getBaseUrl() . "\n";

            foreach ($mpdCache->allPeriods() as $period) {
                $adaptationSetCount = $period->getAdaptationSetCount();
                echo $period->path() . " - " .
                    $period->getTransientAttribute('profiles') . "\n";
                echo $period->path() . " - " .
                    $period->getBaseUrl() . "\n";

                foreach ($period->allAdaptationSets() as $adaptationSet) {
                    echo $adaptationSet->path() . " - " .
                        $adaptationSet->getTransientAttribute('profiles') . "\n";
                    echo $adaptationSet->path() . " - " .
                    $adaptationSet->getBaseUrl() . "\n";
                    foreach ($adaptationSet->allRepresentations() as $representation) {
                        echo $representation->path() . " - " .
                        $representation->getTransientAttribute('profiles') . "\n";
                        echo $representation->path() . " - " .
                        $representation->getBaseUrl() . "\n";
                        $initUrl = $representation->initializationUrl();
                        if ($initUrl) {
                            echo "  Init: $initUrl\n";
                        }
                        foreach ($representation->segmentUrls() as $url) {
                            echo "    $url\n";
                        }
                    }
                }
            }

            #$schematron = app(Schematron::class);
            #$schematron->validate();
            #$schematron->validateSchematron();

            #$downloader = app(Downloader::class);
            #$downloader->downloadSegments(0, 0, 0);


            $dvbMpd = new DVBMpd();
            $dvbMpd->validateMPD();

            #echo app(ModuleLogger::class)->asJSON() . "\n";


            print_r($reporter);
        });
    }
}
