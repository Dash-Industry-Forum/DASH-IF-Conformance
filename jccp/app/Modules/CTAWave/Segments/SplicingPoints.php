<?php

namespace App\Modules\CTAWave\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\SegmentManager;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use App\Services\Validators\Boxes;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SplicingPoints
{
    //Private subreporters
    private SubReporter $waveReporter;

    private TestCase $sampleEntryCase;
    private TestCase $framerateCase;
    private TestCase $audioChannelCase;
    private TestCase $parCase;
    private TestCase $trackIdCase;
    private TestCase $timescaleCase;
    private TestCase $encryptionSchemeCase;
    private TestCase $encryptionKIDCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->waveReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "WAVE Content Spec 2018Ed",
            []
        ));

        //Disallowed changes
        $this->sampleEntryCase = $this->waveReporter->add(
            section: '7.2.2 - Presentation Splicing',
            test: "Sample entries in Sequential Switching Sets Shall not change at Splice points",
            skipReason: "Stream does not contain multiple periods"
        );
        $this->framerateCase = $this->waveReporter->add(
            section: '7.2.2 - Presentation Splicing',
            test: "Framerate in Sequential Switching Sets Shall not change at Splice points",
            skipReason: "Stream does not contain multiple periods"
        );
        $this->audioChannelCase = $this->waveReporter->add(
            section: '7.2.2 - Presentation Splicing',
            test: "Channel count in Sequential Switching Sets Shall not change at Splice points",
            skipReason: "Stream does not contain multiple periods"
        );
        $this->parCase = $this->waveReporter->add(
            section: '7.2.2 - Presentation Splicing',
            test: "Picture Aspect Ration (PAR) in Sequential Switching Sets Shall not change at Splice points",
            skipReason: "Stream does not contain multiple periods"
        );
        //Allowed changes
        $this->trackIdCase = $this->waveReporter->add(
            section: '7.2.2 - Presentation Splicing',
            test: "Track Ids can change at Splice points",
            skipReason: "Stream does not contain multiple periods"
        );
        $this->timescaleCase = $this->waveReporter->add(
            section: '7.2.2 - Presentation Splicing',
            test: "Timescale can change at Splice points",
            skipReason: "Stream does not contain multiple periods"
        );
        $this->encryptionSchemeCase = $this->waveReporter->add(
            section: '7.2.2 - Presentation Splicing',
            test: "Encryption scheme can change at Splice points",
            skipReason: "Stream does not contain multiple periods"
        );
        $this->encryptionKIDCase = $this->waveReporter->add(
            section: '7.2.2 - Presentation Splicing',
            test: "Default KID can change at Splice points",
            skipReason: "Stream does not contain multiple periods"
        );
    }

    //Public validation functions
    public function validateSplicingPoints(Period $firstPeriod, Period $secondPeriod): void
    {
        $segmentManager = app(SegmentManager::class);
        $firstAdaptationSets = $firstPeriod->allAdaptationSets();
        $secondAdaptationSets = $secondPeriod->allAdaptationSets();

        $sharedCount = min(count($firstAdaptationSets), count($secondAdaptationSets));

        for ($adaptationIndex = 0; $adaptationIndex < $sharedCount; $adaptationIndex++) {
            $firstRepresentations = $firstAdaptationSets[$adaptationIndex]->allRepresentations();
            $secondRepresentations = $secondAdaptationSets[$adaptationIndex]->allRepresentations();

            if (!count($firstRepresentations) || !count($secondRepresentations)) {
                continue;
            }

            $firstSegments = $segmentManager->representationSegments($firstRepresentations[0]);
            $secondSegments = $segmentManager->representationSegments($secondRepresentations[0]);

            if (!count($firstSegments) || !count($secondSegments)) {
                continue;
            }
            $this->validateDisallowedChanges(
                $firstRepresentations[0],
                $firstSegments[0],
                $secondRepresentations[0],
                $secondSegments[0]
            );
            $this->validateAllowedChanges(
                $firstRepresentations[0],
                $firstSegments[0],
                $secondRepresentations[0],
                $secondSegments[0]
            );
        }
    }

    //Private helper functions
    private function validateDisallowedChanges(
        Representation $firstRep,
        Segment $firstSeg,
        Representation $secondRep,
        Segment $secondSeg
    ): void {
        $firstSd = $firstSeg->getSampleDescriptor();
        $secondSd = $secondSeg->getSampleDescriptor();

        $this->sampleEntryCase->pathAdd(
            path: $firstRep->path() . " <-> " . $secondRep->path(),
            result: $firstSd == $secondSd,
            severity: "FAIL",
            pass_message: "Matches",
            fail_message: "Change detected"
        );
        $this->framerateCase->pathAdd(
            path: $firstRep->path() . " <-> " . $secondRep->path(),
            result: $firstRep->getTransientAttribute('frameRate') == $secondRep->getTransientAttribute('frameRate'),
            severity: "FAIL",
            pass_message: "Matches",
            fail_message: "Change detected"
        );

        $firstAudioConfig = $firstSeg->getAudioConfiguration();
        $secondAudioConfig = $secondSeg->getAudioConfiguration();

        if ($firstSd == $secondSd && $firstSd == 'soun') {
            $audioMatches = false;
            if ($firstAudioConfig === null && $secondAudioConfig === null) {
                $audioMatches = true;
            }
            if ($firstAudioConfig !== null && $secondAudioConfig !== null) {
                if (
                    array_key_exists('Channels', $firstAudioConfig) &&
                    array_key_exists('Channels', $secondAudioConfig)
                ) {
                    $audioMatches = $firstAudioConfig['Channels'] == $secondAudioConfig['Channels'];
                }
            }
            $this->audioChannelCase->pathAdd(
                path: $firstRep->path() . " <-> " . $secondRep->path(),
                result: $audioMatches,
                severity: "FAIL",
                pass_message: "Matches",
                fail_message: "Change detected"
            );
        }


        if ($firstSd == $secondSd && $firstSd == 'vide') {
            $firstPar = $firstSeg->getWidth() / $firstSeg->getHeight();
            $secondPar = $secondSeg->getWidth() / $secondSeg->getHeight();
            $this->parCase->pathAdd(
                path: $firstRep->path() . " <-> " . $secondRep->path(),
                result: $firstPar == $secondPar,
                severity: "FAIL",
                pass_message: "Matches",
                fail_message: "Change detected"
            );
        }
    }
    private function validateAllowedChanges(
        Representation $firstRep,
        Segment $firstSeg,
        Representation $secondRep,
        Segment $secondSeg
    ): void {
        $this->trackIdCase->pathAdd(
            path: $firstRep->path() . " <-> " . $secondRep->path(),
            result: $firstSeg->getTrackId() == $secondSeg->getTrackId(),
            severity: "INFO",
            pass_message: "Matches",
            fail_message: "Change detected"
        );
        $this->timescaleCase->pathAdd(
            path: $firstRep->path() . " <-> " . $secondRep->path(),
            result: $firstSeg->getTimescale() == $secondSeg->getTimescale(),
            severity: "INFO",
            pass_message: "Matches",
            fail_message: "Change detected"
        );

        $firstEncryption = $firstSeg->getProtectionScheme();
        $secondEncryption = $firstSeg->getProtectionScheme();

        $schemeMatches = false;
        $defaultKIDMatches = false;
        if ($firstEncryption === null && $secondEncryption === null) {
            $schemeMatches = true;
            $defaultKIDMatches = true;
        }

        if ($firstEncryption !== null && $secondEncryption !== null) {
            $schemeMatches = ($firstEncryption->scheme->schemeType == $secondEncryption->scheme->schemeType);
            $defaultKIDMatches = ($firstEncryption->encryption->kid == $secondEncryption->encryption->kid);
        }
        $this->encryptionSchemeCase->pathAdd(
            path: $firstRep->path() . " <-> " . $secondRep->path(),
            result: $schemeMatches,
            severity: "INFO",
            pass_message: "Matches",
            fail_message: "Change detected"
        );
        $this->encryptionKIDCase->pathAdd(
            path: $firstRep->path() . " <-> " . $secondRep->path(),
            result: $defaultKIDMatches,
            severity: "INFO",
            pass_message: "Matches",
            fail_message: "Change detected"
        );
    }
}
