<?php

namespace App\Modules\CMAF\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\SegmentManager;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\ModuleComponents\AdaptationComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AudioMediaProfile extends AdaptationComponent
{
    private TestCase $brandCase;
    private TestCase $caacCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "Edition 3",
                "CMAF",
                []
            )
        );

        $this->brandCase = $this->reporter->add(
            section: 'Section A.3',
            test: "The maximum encoding parameters [..as per the table..] SHALL not be exceeded",
            skipReason: 'No cmaf brands signalled'
        );
        $this->caacCase = $this->reporter->add(
            section: 'Section A.1.2',
            test: "Audio adaptation sets SHALL include at least one 'caac' representation",
            skipReason: 'No audio track found, or no CMFHD profile signalled'
        );
    }

    //Public validation functions
    public function validateAdaptationSet(AdaptationSet $adaptationSet): void
    {
        if (!str_starts_with($adaptationSet->getTransientAttribute('mimeType'), 'audio/')) {
            return;
        }
        $segmentManager = app(SegmentManager::class);

        $hasCAAC = false;
        foreach ($adaptationSet->allRepresentations() as $representation) {
            $segmentList = $segmentManager->representationSegments($representation);

            if (count($segmentList)) {
                if (in_array('caac', $segmentList[0]->getBrands())) {
                    $hasCAAC = true;
                }
                $this->validateAndDetermineBrand($representation, $segmentList[0]);
            }
        }

        $mpdCache = app(MPDCache::class);

        if ($mpdCache->hasProfile("urn:mpeg:cmaf:presentation_profile:cmfhd:2016")) {
            $this->caacCase->pathAdd(
                result: $hasCAAC,
                severity: "FAIL",
                path: $adaptationSet->path(),
                pass_message: "At least one 'caac' track found",
                fail_message: "No 'caac' tracks found"
            );
        }
    }

    //Private helper functions
    private function validateAndDetermineBrand(Representation $representation, Segment $segment): void
    {
        $sdType = $segment->getSampleDescriptor();

        if ($sdType == 'mp4a') {
            $this->validateAndDetermineBrandAAC($representation, $segment);
        }
    }

    private function validateAndDetermineBrandAAC(Representation $representation, Segment $segment): void
    {
        $brands = $segment->getBrands();


        if (in_array('caac', $brands)) {
            $this->validateAACParameters(
                representation: $representation,
                segment: $segment,
                brand: 'caac',
                maxSampleRate: "48000",
                maxChannels: "2",
                allowedObjectTypes: ["2","5","29"]
            );
        }
    }

    /**
     * @param array<string> $allowedObjectTypes
     **/
    private function validateAACParameters(
        Representation $representation,
        Segment $segment,
        string $brand,
        string $maxSampleRate,
        string $maxChannels,
        array $allowedObjectTypes,
    ): void {
        //TODO: ValidateFramerate
        $aacConfiguration = $segment->getAACConfiguration();

        $this->brandCase->pathAdd(
            path: $representation->path() . "-init",
            result: $aacConfiguration['sampleRate'] <= $maxSampleRate,
            severity: "FAIL",
            pass_message: "Signalled brand $brand conforms to maximum sample rate",
            fail_message: "Signalled brand $brand but exceeds maximum sample rate",
        );

        $this->brandCase->pathAdd(
            path: $representation->path() . "-init",
            result: $aacConfiguration['numChannels'] <= $maxChannels,
            severity: "FAIL",
            pass_message: "Signalled brand $brand conforms to maximum channel count",
            fail_message: "Signalled brand $brand but exceeds maximum channel count",
        );

        $this->brandCase->pathAdd(
            path: $representation->path() . "-init",
            result: in_array($aacConfiguration['streamType'], $allowedObjectTypes),
            severity: "FAIL",
            pass_message: "Signalled brand $brand conforms to allowed object types",
            fail_message: "Signalled brand $brand does not conform to allowed object types",
        );
    }
}
