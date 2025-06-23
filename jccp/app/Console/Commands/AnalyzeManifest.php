<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Services\MPDCache;
use App\Services\Manifest\AdaptationSetCache;
use App\Services\Manifest\PeriodCache;
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


            $periodCount = app(MPDCache::class)->getPeriodCount();

            for ($periodIndex = 0; $periodIndex < $periodCount; $periodIndex++) {
                $periodCache = new PeriodCache($periodIndex);
                $adaptationSetCount = $periodCache->getAdaptationSetCount();
                echo "Period $periodIndex: " . $periodCache->getTransientAttribute('profiles') . "\n";

                for ($adaptationSetIndex = 0; $adaptationSetIndex < $adaptationSetCount; $adaptationSetIndex++) {
                    $adaptationSetCache = new AdaptationSetCache($periodIndex, $adaptationSetIndex);
                    echo "  AdaptationSet $adaptationSetIndex: " . $adaptationSetCache->getTransientAttribute('profiles') . "\n";
                }
            }
        });
    }
}
