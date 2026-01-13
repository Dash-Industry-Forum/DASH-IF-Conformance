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
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AudioProfile
{
    //Private subreporters
    private SubReporter $waveReporter;

    private TestCase $validProfileCase;

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
        $this->validProfileCase = $this->waveReporter->add(
            section: '4.2.1',
            test: "Each WAVE video Media profile SHALL conform to normative ref. listed in Table 1",
            skipReason: "No video track found",
        );
    }

    //Public validation functions
    public function validateAudioProfile(AdaptationSet $adaptationSet): void
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
    }

    //Private helper functions
    /**
     * @param array<Segment> $segments
     **/
    private function getProfile(Representation $representation, array $segments): string
    {
        $sdType = $segments[0]->getSampleDescriptor();

        if ($sdType == 'mp4a'){
            return $this->getAACProfile($representation, $segments);
        }

        $this->validProfileCase->pathAdd(
            path: $representation->path(),
            result: false,
            severity: "FAIL",
            pass_message: "",
            fail_message: "Unsupported sample descriptor $sdType"
        );
        return '';
    }

    /**
     * @param array<Segment> $segments
     **/
    private function getAACProfile(Representation $representation, array $segments): string
    {

        $validSampleRate = intval($representation->getAttribute('sampleRate')) <= 48000;
        if (!$validSampleRate){
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
        $channelConfig = array_key_exists('Channels',$config) ? $config['Channels'] : '';

        $mediaProfile = '';


        $this->validProfileCase->pathAdd(
            path: $representation->path(),
            result: $mediaProfile != '',
            severity: "FAIL",
            pass_message: "AAC - $mediaProfile",
            fail_message: "AAC - Unsupported configuration"
        );
        return $mediaProfile;
    }

}
