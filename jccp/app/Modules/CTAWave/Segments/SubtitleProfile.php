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

class SubtitleProfile
{
    //Private subreporters
    private SubReporter $waveReporter;

    private TestCase $validProfileCase;
    private TestCase $singleProfileCase;
    private TestCase $mandatoryProfileCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->waveReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "WAVE Content Spec 2018Ed",
            []
        ));

        $this->validProfileCase = $this->waveReporter->add(
            section: '4.4.1',
            test: "Each WAVE Subtitle Media profile SHALL conform to normative ref. listed in Table 1",
            skipReason: "No video track found",
        );
        $this->singleProfileCase = $this->waveReporter->add(
            section: '4.1',
            test: "Wave content SHALL include one or more switch sets conforming to at least one approved CMAF profile",
            skipReason: "No corresponding adaptations"
        );
        //NOTE: This check seems very conflicting with the above one....
        $this->mandatoryProfileCase = $this->waveReporter->add(
            section: '5 ',
            test: "If a subtitle track is included, the conforming (presentation will at least " .
                  "include TTML Text Media profile",
            skipReason: "No corresponding adaptations"
        );
    }

    //Public validation functions
    public function validateSubtitleProfile(AdaptationSet $adaptationSet): void
    {
        if (
            $adaptationSet->getAttribute('mimeType') == 'audio/mp4' ||
            $adaptationSet->getAttribute('mimeType') == 'video/mp4'
        ) {
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
            result:  count(array_unique($foundProfiles)) == 1 && $foundProfiles[0] != '',
            severity: "FAIL",
            pass_message: "Subtitles conforms to a single approved profile",
            fail_message: "Subtitles do not conform to a single approved profile",
        );
        $this->mandatoryProfileCase->pathAdd(
            path: $adaptationSet->path(),
            result: in_array('TTML_IMSC1_Text', $foundProfiles),
            severity: "FAIL",
            pass_message: "Mandatory profile found",
            fail_message: "Mandatory profile not found"
        );
    }

    //Private helper functions
    /**
     * @param array<Segment> $segments
     **/
    private function getProfile(Representation $representation, array $segments): string
    {
        $sdType = $segments[0]->getSampleDescriptor();


        if ($sdType != 'stpp') {
            $this->validProfileCase->pathAdd(
                path: $representation->path(),
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: "Unsupported Sample description $sdType"
            );
            return '';
        }

        //todo Check mimetype in a correct way

        $codecs = $representation->getTransientAttribute('codecs');

        $mediaProfile = '';

        if (strpos($codecs, 'im1t') !== false) {
            $mediaProfile = 'TTML_IMSC1_Text';
        }
        if (strpos($codecs, 'im1i') !== false) {
            $mediaProfile = 'TTML_IMSC1_Image';
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
}
