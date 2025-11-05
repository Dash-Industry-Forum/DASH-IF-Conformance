<?php

namespace App\Modules\CMAF\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\SegmentManager;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AudioMediaProfile
{
    //Private subreporters
    private SubReporter $cmafReporter;

    private TestCase $profileCase;
    private TestCase $brandCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->cmafReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "Legacy",
            "CMAF",
            []
        ));

        $this->profileCase = $this->cmafReporter->add(
            section: 'Section 7.3.4.1',
            test: "All CMAF audio tracks in a CMAF Switching Set SHALL conform to one CMAF Media Profile",
            skipReason: 'No audio switching set found'
        );
        $this->brandCase = $this->cmafReporter->add(
            section: 'Section A.3',
            test: "If a CMAF brand is signalled, it SHALL correspond with the table",
            skipReason: 'No cmaf brands signalled'
        );
    }

    //Public validation functions
    public function validateAudioMediaProfiles(AdaptationSet $adaptationSet): void
    {
        $signalledBrands = [];

        $segmentManager = app(SegmentManager::class);

        foreach ($adaptationSet->allRepresentations() as $representation) {
            $segmentList = $segmentManager->representationSegments($representation);

            $highestBrand = '____'; // Unknown;
            if (count($segmentList)) {
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
