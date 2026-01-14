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
    private TestCase $profileCase;
    private TestCase $brandCase;
    private TestCase $caacCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "LEGACY",
                "CMAF",
                []
            )
        );

        $this->profileCase = $this->reporter->add(
            section: 'Section 7.3.4.1',
            test: "All CMAF audio tracks in a CMAF Switching Set SHALL conform to one CMAF Media Profile",
            skipReason: 'No audio switching set found'
        );
        $this->brandCase = $this->reporter->add(
            section: 'Section A.3',
            test: "If a CMAF brand is signalled, it SHALL correspond with the table",
            skipReason: 'No cmaf brands signalled'
        );
        $this->caacCase = $this->reporter->add(
            section: 'Section A.1.2/A.1.3/A.1.4',
            test: "Audio adaptation sets SHALL include at least one 'caac' representation",
            skipReason: 'No audio track found with CMAF profile found'
        );
    }

    //Public validation functions
    public function validateAdaptationSet(AdaptationSet $adaptationSet): void
    {
        //TODO: Only if audio
        $signalledBrands = [];

        $segmentManager = app(SegmentManager::class);

        $hasCAAC = false;
        foreach ($adaptationSet->allRepresentations() as $representation) {
            $segmentList = $segmentManager->representationSegments($representation);

            $highestBrand = '____'; // Unknown;
            if (count($segmentList)) {
                if (in_array('caac', $segmentList[0]->getBrands())) {
                    $hasCAAC = true;
                }
                $highestBrand = $this->validateAndDetermineBrand($representation, $segmentList[0]);
            }
            $signalledBrands[] = $highestBrand; // Unknown
        }

        $this->profileCase->pathAdd(
            result: count(array_unique($signalledBrands)) == 1,
            severity: "FAIL",
            path: $adaptationSet->path(),
            pass_message: "All representations signal the same highest brand",
            fail_message: "Not all representations signal the same highest brand"
        );

        $this->caacCase->pathAdd(
            result: $hasCAAC,
            severity: "FAIL",
            path: $adaptationSet->path(),
            pass_message: "At least one 'caac' track found",
            fail_message: "No 'caac' tracks found"
        );
    }

    //Private helper functions
    private function validateAndDetermineBrand(Representation $representation, Segment $segment): string
    {
        $sdType = $segment->getSampleDescriptor();

        if ($sdType != 'mp4a') {
            return '____';
        }
            return $this->validateAndDetermineBrandAAC($representation, $segment);
    }

    private function validateAndDetermineBrandAAC(Representation $representation, Segment $segment): string
    {
        $brands = $segment->getBrands();

        $highestBrand = '____'; //Unknown

        if (in_array('caac', $brands)) {
            $highestBrand = 'caac';
            $this->validateAACParameters(
                representation: $representation,
                segment: $segment,
                brand: 'caac',
                maxSampleRate: "48000",
                maxChannels: "2",
                allowedObjectTypes: ["2","5","29"]
            );
        }
        if (in_array('caaa', $brands)) {
            $highestBrand = 'caaa';
        }
        return $highestBrand;
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
            result: in_array($aacConfiguration['objectTypeIndication'], $allowedObjectTypes),
            severity: "FAIL",
            pass_message: "Signalled brand $brand conforms to allowed object types",
            fail_message: "Signalled brand $brand does not conform to allowed object types",
        );
    }
}
