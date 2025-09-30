<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SubtitleChecks
{
    //Private subreporters
    private SubReporter $v141reporter;

    private TestCase $codecCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));

        $this->codecCase = $this->v141reporter->add(
            section: "Section 7.1.1",
            test: "The @codecs attribute shall begin with 'stpp' to indicate the use of XML subtitles",
            skipReason: "No text track found"
        );
    }

    //Public validation functions
    public function validateSubtitles(): void
    {

        //NOTE: All checks that were only based on the table in section 7.1.2 have been removed

        $mpdCache = app(MPDCache::class);
        foreach ($mpdCache->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                if ($adaptationSet->getAttribute('contentType') != 'text') {
                    continue;
                }

                $codecs = $adaptationSet->getAttribute('codecs');

                $this->codecCase->pathAdd(
                    path: $adaptationSet->path(),
                    result: str_starts_with($codecs, 'stpp'),
                    severity: "FAIL",
                    pass_message: "Valid @codecs '$codecs'",
                    fail_message: "Invalid @codecs '$codecs'",
                );
            }
        }
    }
}
