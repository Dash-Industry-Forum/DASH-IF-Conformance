<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Services\MPDCache;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;

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

            foreach ($mpdCache->allPeriods() as $period) {
                $adaptationSetCount = $period->getAdaptationSetCount();
                echo $period->path() . " - " .
                    $period->getTransientAttribute('profiles') . "\n";

                foreach ($period->allAdaptationSets() as $adaptationSet) {
                    echo $adaptationSet->path() . " - " .
                        $adaptationSet->getTransientAttribute('profiles') . "\n";
                    foreach ($adaptationSet->allRepresentations() as $representation) {
                        echo $representation->path() . " - " .
                        $representation->getTransientAttribute('profiles') . "\n";
                    }
                }
            }
        });
    }
}
