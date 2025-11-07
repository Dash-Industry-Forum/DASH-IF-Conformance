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

class SubtitleMediaProfile
{
    //Private subreporters
    private SubReporter $cmafReporter;

    private TestCase $profileCase;

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
            test: "All CMAF subtitle tracks in a CMAF Switching Set SHALL conform to one CMAF Media Profile",
            skipReason: 'No subtitle switching set found'
        );
    }

    //Public validation functions
    public function validateSubtitleMediaProfiles(AdaptationSet $adaptationSet): void
    {
        //TODO: Check if all languages exist
        //TODO: Only if subtitle
        $signalledBrands = [];

        $segmentManager = app(SegmentManager::class);

        $hasIm1t = false;
        foreach ($adaptationSet->allRepresentations() as $representation) {
            $segmentList = $segmentManager->representationSegments($representation);

            $highestBrand = '____'; // Unknown;
            if (count($segmentList)) {
                if (in_array('im1t', $segmentList[0]->getBrands())) {
                    $hasIm1t = true;
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
    }

    //Private helper functions
    private function validateAndDetermineBrand(Representation $representation, Segment $segment): string
    {
        $hdlrType = $segment->getHandlerType();
        $sdType = $segment->getSampleDescriptor();

        if ($hdlrType == 'text' && $sdType == 'wvtt') {
            return 'cwvt';
        }

        if ($hdlrType == 'subt') {
            $brands = $segment->getBrands();

            if (in_array("im1i", $brands)) {
                return "im1i";
            }
            if (in_array("im1t", $brands)) {
                return "im1t";
            }
        }

        return '____';
    }
}
