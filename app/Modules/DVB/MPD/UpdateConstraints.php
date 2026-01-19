<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\ManifestType;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class UpdateConstraints
{
    //Private subreporters
    private SubReporter $v141reporter;

    private TestCase $timeShiftCase;
    private TestCase $astCase;
    private TestCase $maxSegmentDurationCase;

    private TestCase $periodIdCase;
    private TestCase $periodStartCase;
    private TestCase $periodAssetIdentifierCase;

    private TestCase $adaptationSetIdCase;
    private TestCase $representationIdCase;

    private TestCase $roleCase;
    private TestCase $audioConfigurationCase;
    private TestCase $contentTypeCase;
    private TestCase $codecsCase;
    private TestCase $langCase;


    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));

        $isLive = $this->isLive();

        $this->timeShiftCase = $this->v141reporter->add(
            section: "Section 4.8.3",
            test: "MPD@timeShiftBufferDepth shall not change",
            skipReason: $this->skipReason($isLive, "Unable to parse MPD"),
        );
        $this->astCase = $this->v141reporter->add(
            section: "Section 4.8.3",
            test: "MPD@availabilityStartTime shall not change",
            skipReason: $this->skipReason($isLive, "Unable to parse MPD"),
        );
        $this->maxSegmentDurationCase = $this->v141reporter->add(
            section: "Section 4.8.3",
            test: "MPD@maxSegmentDuration shall not change to a larger duration",
            skipReason: $this->skipReason($isLive, "Unable to parse MPD"),
        );
        $this->periodIdCase = $this->v141reporter->add(
            section: "Section 4.8.3",
            test: "@id shall not change for a corresponding Period",
            skipReason: $this->skipReason($isLive, "Unable to match any periods")
        );
        $this->periodStartCase = $this->v141reporter->add(
            section: "Section 4.8.3",
            test: "@start shall not change for a corresponding Period",
            skipReason: $this->skipReason($isLive, "Unable to match any periods")
        );
        $this->periodAssetIdentifierCase = $this->v141reporter->add(
            section: "Section 4.8.3",
            test: "@AssetIdentifier shall not change for a corresponding Period",
            skipReason: $this->skipReason($isLive, "Unable to match any periods")
        );

        $this->adaptationSetIdCase = $this->v141reporter->add(
            section: "Section 4.8.3",
            test: "@id shall not change for a corresponding AdaptationSet",
            skipReason: $this->skipReason($isLive, "Unable to match any adaptation sets")
        );
        $this->representationIdCase = $this->v141reporter->add(
            section: "Section 4.8.3",
            test: "@id shall not change for a corresponding Representation",
            skipReason: $this->skipReason($isLive, "Unable to match any representation")
        );

        $this->roleCase = $this->v141reporter->add(
            section: "Section 4.8.3",
            test: "Role Element shall not change for a corresponding AdaptationSet or corresponding Representation",
            skipReason: $this->skipReason($isLive, "Unable to match any adaptation sets or representation")
        );
        $this->audioConfigurationCase = $this->v141reporter->add(
            section: "Section 4.8.3",
            test: "AudioConfiguration Element shall not change for a corresponding AdaptationSet " .
                  "or corresponding Representation",
            skipReason: $this->skipReason($isLive, "Unable to match any adaptation sets or representation")
        );
        $this->contentTypeCase = $this->v141reporter->add(
            section: "Section 4.8.3",
            test: "@contentType shall not change for a corresponding AdaptationSet or corresponding Representation",
            skipReason: $this->skipReason($isLive, "Unable to match any adaptation sets or representation")
        );
        $this->codecsCase = $this->v141reporter->add(
            section: "Section 4.8.3",
            test: "@codecs shall not change for a corresponding AdaptationSet or corresponding Representation",
            skipReason: $this->skipReason($isLive, "Unable to match any adaptation sets or representation")
        );
        $this->langCase = $this->v141reporter->add(
            section: "Section 4.8.3",
            test: "@lang shall not change for a corresponding AdaptationSet or corresponding Representation",
            skipReason: $this->skipReason($isLive, "Unable to match any adaptation sets or representation")
        );
    }

    //Public validation functions
    public function validateUpdateConstraints(): void
    {
        if (!$this->isLive()) {
            return;
        }

        if ($this->shouldSkip()) {
            $this->markAllSkipped();
            return;
        }

        $mpdCache = app(MPDCache::class);

        $this->timeShiftCase->add(
            result: $mpdCache->getAttribute('timeShiftBufferDepth', ManifestType::Regular) ==
                    $mpdCache->getAttribute('timeShiftBufferDepth', ManifestType::Live),
            severity: "FAIL",
            pass_message: "Value not changed",
            fail_message: "Value changed",
        );
        $this->astCase->add(
            result: $mpdCache->getAttribute('availabilityStartTime', ManifestType::Regular) ==
                    $mpdCache->getAttribute('availabilityStartTime', ManifestType::Live),
            severity: "FAIL",
            pass_message: "Value not changed",
            fail_message: "Value changed",
        );
        $this->maxSegmentDurationCase->add(
            result: $mpdCache->getAttribute('maxSegmentDuration', ManifestType::Regular) >=
                    $mpdCache->getAttribute('maxSegmentDuration', ManifestType::Live),
            severity: "FAIL",
            pass_message: "Value not increased",
            fail_message: "Value increased",
        );


        $this->validateAllPeriods();
    }

    //Private helper functions
    private function markAllSkipped(): void
    {
        $this->timeShiftCase->skipWithInfo("MPD not yet reloaded");
        $this->astCase->skipWithInfo("MPD not yet reloaded");
    }

    private function skipReason(bool $isLive, string $reason): string
    {
        return $isLive ? $reason : "Not a live stream";
    }
    private function isLive(): bool
    {
        $mpdCache = app(MPDCache::class);
        $minimumUpdatePeriod = $mpdCache->getAttribute('minimumUpdatePeriod');

        return $minimumUpdatePeriod != '';
    }
    private function shouldSkip(): bool
    {
        $mpdCache = app(MPDCache::class);
        $minimumUpdatePeriod = $mpdCache->getAttribute('minimumUpdatePeriod');

        $originalRetrieval = Cache::get(cache_path(['mpd','url_retrieval']), '0');

        /** TODO: Add minimumUpdateTime to this call **/
        return intval($originalRetrieval) > time();
    }

    private function validateAllPeriods(): void
    {
        $mpdCache = app(MPDCache::class);
        $regularPeriods = $mpdCache->allPeriods(ManifestType::Regular);
        $livePeriods = $mpdCache->allPeriods(ManifestType::Live);

        foreach ($regularPeriods as $regularPeriod) {
            $regularId = $regularPeriod->getAttribute('id');

            if ($regularId == '') {
                $this->periodIdCase->pathAdd(
                    path: $regularPeriod->path(),
                    result: false,
                    severity: "FAIL",
                    pass_message: "",
                    fail_message: "Unable to retrieve @id"
                );
                continue;
            }

            foreach ($livePeriods as $livePeriod) {
                $liveId = $livePeriod->getAttribute('id');

                if ($regularId != $liveId) {
                    continue;
                }

                $this->periodIdCase->pathAdd(
                    path: $regularPeriod->path() . " -> " . $livePeriod->path(),
                    result: true,
                    severity: "INFO",
                    pass_message: "Matched on @id",
                    fail_message: ""
                );

                $this->validateSinglePeriod($regularPeriod, $livePeriod);
            }
        }
    }

    private function validateSinglePeriod(Period $regularPeriod, Period $livePeriod): void
    {
        $this->periodStartCase->pathAdd(
            path: $regularPeriod->path() . " -> " . $livePeriod->path(),
            result: $regularPeriod->getAttribute('start') == $livePeriod->getAttribute('start'),
            severity: "FAIL",
            pass_message: "Value not changed",
            fail_message: "Value changed"
        );
        $this->periodAssetIdentifierCase->pathAdd(
            path: $regularPeriod->path() . " -> " . $livePeriod->path(),
            result: $regularPeriod->getAttribute('AssetIdentifier') == $livePeriod->getAttribute('AssetIdentifier'),
            severity: "FAIL",
            pass_message: "Value not changed",
            fail_message: "Value changed"
        );

        $regularAdaptationSets = $regularPeriod->allAdaptationSets();
        $liveAdaptationSets = $livePeriod->allAdaptationSets();

        foreach ($regularAdaptationSets as $regularAdaptationSet) {
            if ($regularAdaptationSet->getAttribute('id') == '') {
                $this->adaptationSetIdCase->pathAdd(
                    path: $regularAdaptationSet->path(),
                    result: false,
                    severity: "FAIL",
                    pass_message: "",
                    fail_message: "Unable to retrieve @id"
                );
                continue;
            }

            foreach ($liveAdaptationSets as $liveAdaptationSet) {
                if ($regularAdaptationSet->getAttribute('id') != $liveAdaptationSet->getAttribute('id')) {
                    continue;
                }

                $this->adaptationSetIdCase->pathAdd(
                    path: $regularAdaptationSet->path() . " -> " . $liveAdaptationSet->path(),
                    result: true,
                    severity: "INFO",
                    pass_message: "Matched on @id",
                    fail_message: ""
                );

                $this->validateSingleAdaptationSet($regularAdaptationSet, $liveAdaptationSet);
            }
        }
    }

    private function validateSingleAdaptationSet(
        AdaptationSet $regularAdaptationSet,
        AdaptationSet $liveAdaptationSet
    ): void {
        $this->roleCase->pathAdd(
            path: $regularAdaptationSet->path() . " -> " . $liveAdaptationSet->path(),
            result: $regularAdaptationSet->getDOMElements('Role') ==
                    $liveAdaptationSet->getDOMElements('Role'),
            severity: "FAIL",
            pass_message: "Value not changed",
            fail_message: "Value changed"
        );
        $this->audioConfigurationCase->pathAdd(
            path: $regularAdaptationSet->path() . " -> " . $liveAdaptationSet->path(),
            result: $regularAdaptationSet->getDOMElements('AudioConfiguration') ==
                    $liveAdaptationSet->getDOMElements('AudioConfiguration'),
            severity: "FAIL",
            pass_message: "Value not changed",
            fail_message: "Value changed"
        );
        $this->contentTypeCase->pathAdd(
            path: $regularAdaptationSet->path() . " -> " . $liveAdaptationSet->path(),
            result: $regularAdaptationSet->getAttribute('contentType') ==
                    $liveAdaptationSet->getAttribute('contentType'),
            severity: "FAIL",
            pass_message: "Value not changed",
            fail_message: "Value changed"
        );
        $this->codecsCase->pathAdd(
            path: $regularAdaptationSet->path() . " -> " . $liveAdaptationSet->path(),
            result: $regularAdaptationSet->getAttribute('codecs') ==
                    $liveAdaptationSet->getAttribute('codecs'),
            severity: "FAIL",
            pass_message: "Value not changed",
            fail_message: "Value changed"
        );
        $this->langCase->pathAdd(
            path: $regularAdaptationSet->path() . " -> " . $liveAdaptationSet->path(),
            result: $regularAdaptationSet->getAttribute('lang') ==
                    $liveAdaptationSet->getAttribute('lang'),
            severity: "FAIL",
            pass_message: "Value not changed",
            fail_message: "Value changed"
        );

        $regularRepresentations = $regularAdaptationSet->allRepresentations();
        $liveRepresentations = $liveAdaptationSet->allRepresentations();

        foreach ($regularRepresentations as $regularRepresentation) {
            if ($regularRepresentation->getAttribute('id') == '') {
                $this->representationIdCase->pathAdd(
                    path: $regularRepresentation->path(),
                    result: false,
                    severity: "FAIL",
                    pass_message: "",
                    fail_message: "Unable to retrieve @id"
                );
                continue;
            }

            foreach ($liveRepresentations as $liveRepresentation) {
                if ($regularRepresentation->getAttribute('id') != $liveRepresentation->getAttribute('id')) {
                    continue;
                }

                $this->representationIdCase->pathAdd(
                    path: $regularRepresentation->path() . " -> " . $liveRepresentation->path(),
                    result: true,
                    severity: "INFO",
                    pass_message: "Matched on @id",
                    fail_message: ""
                );

                $this->validateSingleRepresentation($regularRepresentation, $liveRepresentation);
            }
        }
    }

    private function validateSingleRepresentation(
        Representation $regularRepresentation,
        Representation $liveRepresentation
    ): void {
        $this->roleCase->pathAdd(
            path: $regularRepresentation->path() . " -> " . $liveRepresentation->path(),
            result: $regularRepresentation->getDOMElements('Role') ==
                    $liveRepresentation->getDOMElements('Role'),
            severity: "FAIL",
            pass_message: "Value not changed",
            fail_message: "Value changed"
        );
        $this->audioConfigurationCase->pathAdd(
            path: $regularRepresentation->path() . " -> " . $liveRepresentation->path(),
            result: $regularRepresentation->getDOMElements('AudioConfiguration') ==
                    $liveRepresentation->getDOMElements('AudioConfiguration'),
            severity: "FAIL",
            pass_message: "Value not changed",
            fail_message: "Value changed"
        );
        $this->contentTypeCase->pathAdd(
            path: $regularRepresentation->path() . " -> " . $liveRepresentation->path(),
            result: $regularRepresentation->getAttribute('contentType') ==
                    $liveRepresentation->getAttribute('contentType'),
            severity: "FAIL",
            pass_message: "Value not changed",
            fail_message: "Value changed"
        );
        $this->codecsCase->pathAdd(
            path: $regularRepresentation->path() . " -> " . $liveRepresentation->path(),
            result: $regularRepresentation->getAttribute('codecs') ==
                    $liveRepresentation->getAttribute('codecs'),
            severity: "FAIL",
            pass_message: "Value not changed",
            fail_message: "Value changed"
        );
        $this->langCase->pathAdd(
            path: $regularRepresentation->path() . " -> " . $liveRepresentation->path(),
            result: $regularRepresentation->getAttribute('lang') ==
                    $liveRepresentation->getAttribute('lang'),
            severity: "FAIL",
            pass_message: "Value not changed",
            fail_message: "Value changed"
        );
    }
}
