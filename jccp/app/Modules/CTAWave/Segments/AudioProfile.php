<?php

namespace App\Modules\CTAWave\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\SegmentManager;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use App\Services\Validators\Boxes;
use App\Interfaces\ModuleComponents\AdaptationComponent;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AudioProfile extends AdaptationComponent
{
    private TestCase $validProfileCase;
    private TestCase $singleProfileCase;
    private TestCase $mandatoryProfileCase;
    private TestCase $crossPeriodProfileCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "LEGACY",
                "WAVE Content Spec 2018Ed",
                []
            )
        );

        $this->validProfileCase = $this->reporter->add(
            section: '4.3.1',
            test: "Each WAVE audio Media profile SHALL conform to normative ref. listed in Table 1",
            skipReason: "No video track found",
        );
        $this->singleProfileCase = $this->reporter->add(
            section: '4.1',
            test: "Wave content SHALL include one or more switch sets conforming to at least one approved CMAF profile",
            skipReason: "No corresponding adaptations"
        );
        //NOTE: This check seems very conflicting with the above one....
        $this->mandatoryProfileCase = $this->reporter->add(
            section: '5 ',
            test: "If an audio track is included, the conforming (presentation will at least " .
                  "include AAC (Core) Media profile",
            skipReason: "No corresponding adaptations"
        );
        $this->crossPeriodProfileCase = $this->reporter->add(
            section: "7.2.2",
            test: "Sequential sets SHALL conform to the same CMAF profile",
            skipReason: "Single period found",
        );
    }

    //Public validation functions
    public function validateAdaptationSet(AdaptationSet $adaptationSet): void
    {
        if ($adaptationSet->getAttribute('mimeType') != 'audio/mp4') {
            return;
        }
        $segmentManager = app(SegmentManager::class);
        $foundProfiles = [];
        foreach ($adaptationSet->allRepresentations() as $representation) {
            $segmentList = $segmentManager->representationSegments($representation);

            if (count($segmentList) == 0) {
                $this->validProfileCase->pathAdd(
                    path: $representation->path(),
                    result: false,
                    severity: "FAIL",
                    pass_message: "",
                    fail_message: "No segments"
                );
                $foundProfiles[] = '';
                continue;
            }
            $foundProfiles[] = $this->getProfile($representation, $segmentList);
        }
        if (count($foundProfiles) == 0) {
            return;
        }
        $this->singleProfileCase->pathAdd(
            path: $adaptationSet->path(),
            result: count(array_unique($foundProfiles)) == 1 && $foundProfiles[0] != '',
            severity: "FAIL",
            pass_message: "Audio conforms to a single approved profile",
            fail_message: "Audio does not conform to a single approved profile",
        );
        $this->mandatoryProfileCase->pathAdd(
            path: $adaptationSet->path(),
            result: in_array('AAC_Core', $foundProfiles),
            severity: "FAIL",
            pass_message: "Mandatory profile found",
            fail_message: "Mandatory profile not found"
        );

        $this->validateCrossPeriod($adaptationSet, $foundProfiles[0]);
    }

    //Private helper functions
    private function validateCrossPeriod(AdaptationSet $adaptationSet, string $profile): void
    {
        $mpdCache = app(MPDCache::class);
        $segmentManager = app(SegmentManager::class);

        foreach ($mpdCache->allPeriods() as $period) {
            if ($period->periodIndex <= $adaptationSet->periodIndex) {
                continue;
            }

            $correspondingAdaptation = $period->getAdaptationSet($adaptationSet->adaptationSetIndex);
            $firstCorrespondingRepresentation = $correspondingAdaptation->getRepresentation(0);

            $segmentList = $segmentManager->representationSegments($firstCorrespondingRepresentation);

            $correspondingProfile = $this->getProfile(
                $firstCorrespondingRepresentation,
                $segmentList
            );

            $this->crossPeriodProfileCase->pathAdd(
                path: $adaptationSet->path() . " - " . $correspondingAdaptation->path(),
                result: $profile == $correspondingProfile,
                severity: "FAIL",
                pass_message: "Profiles correspond",
                fail_message: "Profiles do not correspond"
            );
        }
    }

    /**
     * @param array<Segment> $segments
     **/
    private function getProfile(Representation $representation, array $segments): string
    {
        $sdType = $segments[0]->getSampleDescriptor();


        if ($sdType == 'mp4a') {
            return $this->getAACProfile($representation, $segments);
        }

        $mediaProfile = '';

        if ($sdType == "ec-3" || $sdType == "ac-3") {
            $mediaProfile = 'Enhanced_AC-3';
        }

        if ($sdType == "ac-4") {
            //TODO: Handle whatever level was in this commit
            $mediaProfile = 'AC-4_SingleStream';
        }

        if ($sdType == 'mhm1') {
            //TODO: Handle whatever profile was in this commit
            //TODO: Parse channel count from MPEG-H
            $validSampleRate = intval($representation->getAttribute('sampleRate')) <= 48000;
            if (!$validSampleRate) {
                $this->validProfileCase->pathAdd(
                    path: $representation->path(),
                    result: false,
                    severity: "FAIL",
                    pass_message: "",
                    fail_message: "MPEG-H - Unsupported sampleRate"
                );
                return '';
            }

            $mediaProfile = 'MPEG-H_SingleStream';
        }

        $this->validProfileCase->pathAdd(
            path: $representation->path(),
            result: $mediaProfile != '',
            severity: "FAIL",
            pass_message: "$mediaProfile",
            fail_message: "Unsupported sample descriptor $sdType"
        );
        return $mediaProfile;
    }

    /**
     * @param array<Segment> $segments
     **/
    private function getAACProfile(Representation $representation, array $segments): string
    {

        $validSampleRate = intval($representation->getAttribute('sampleRate')) <= 48000;
        if (!$validSampleRate) {
            $this->validProfileCase->pathAdd(
                path: $representation->path(),
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: "AAC - Unsupported sampleRate"
            );
            return '';
        }

        $config = $segments[0]->getAudioConfiguration();
        $channelConfig = array_key_exists('Channels', $config) ? $config['Channels'] : '';

        $mediaProfile = '';
        if (in_array($channelConfig, ['1','2'])) {
            //TODO: check whatever level and profile is from this commit

            if (in_array('caaa', $segments[0]->getBrands())) {
                $mediaProfile = 'Adaptive_AAC_Core';
            } else {
                $mediaProfile = 'AAC_Core';
            }
        } elseif (in_array($channelConfig, ['5','6','7','12','14'])) {
            //TODO: check whatever profile was is this commit
            $mediaProfile = 'AAC_MultiChannel';
        }


        $this->validProfileCase->pathAdd(
            path: $representation->path(),
            result: $mediaProfile != '',
            severity: "FAIL",
            pass_message: "AAC - $mediaProfile",
            fail_message: "AAC - Unsupported channel configuration $channelConfig"
        );
        return $mediaProfile;
    }
}
