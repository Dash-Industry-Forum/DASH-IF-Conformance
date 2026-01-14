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

class HEVCComparison
{
    //Private subreporters
    private SubReporter $cmafReporter;

    private TestCase $spsCase;
    private TestCase $seiCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->cmafReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "CMAF",
            []
        ));

        $this->spsCase = $this->cmafReporter->add(
            section: 'Section B.2.4',
            test: "CMAF Switching sets SHALL be constrained to include identical SPS information",
            skipReason: 'No HEVC switching set found'
        );
        $this->seiCase = $this->cmafReporter->add(
            section: 'Section B.2.4',
            test: "CMAF Switching sets SHALL be constrained to include identical SEI nals",
            skipReason: 'No HEVC switching set found'
        );
    }

    //Public validation functions
    public function validateHEVC(AdaptationSet $adaptationSet): void
    {
        //TODO: Only if HEVC
        //TODO: Rename fields so they're not hardcoded ISOSegmentvalidator ones
        //TODO: Compare prefix and postfix sei messages in first sample
        $signalledBrands = [];

        $segmentManager = app(SegmentManager::class);

        $spsComparisonResults = [
            'vui_parameters_present_flag' => [],
            'video_signal_type_present_flag' => [],
            'colour_description_present_flag' => [],
            'colour_primaries' => [],
            'transfer_characteristics' => [],
            'matrix_coeffs' => [],
            'chroma_loc_info_present_flag' => [],
            'chroma_sample_loc_type_top_field' => [],
            'chroma_sample_loc_type_bottom_field' => [],
            'neutral_chroma_indication_flag' => [],
            'sps_extension_present_flag' => [],
            'sps_range_extension_flag' => [],
            'extended_precision_processing_flag' => []
        ];

        $seiComparisonResults = [
            'length' => ['__'],
            'zero-bit' => ['__'],
            'nuh_layer_id' => ['__'],
            'nuh_temporal_id_plus1' => ['__'],
        ];


        foreach ($adaptationSet->allRepresentations() as $representation) {
            $segmentList = $segmentManager->representationSegments($representation);
            if (count($segmentList)) {
                $this->addSPSComparison($representation, $segmentList[0], $spsComparisonResults);
            }
        }

        //Add check for comparisonResult uniqueness
        foreach (array_keys($spsComparisonResults) as $attribute) {
            $this->spsCase->pathAdd(
                path: $adaptationSet->path(),
                result: count(array_unique($spsComparisonResults[$attribute])) == 1,
                severity: "FAIL",
                pass_message: "Attribute $attribute identical over all representations",
                fail_message: "Attribute $attribute not identical over all representations",
            );
        }
        //Add check for comparisonResult uniqueness
        foreach (array_keys($seiComparisonResults) as $attribute) {
            $this->seiCase->pathAdd(
                path: $adaptationSet->path(),
                result: count(array_unique($seiComparisonResults[$attribute])) == 1,
                severity: "FAIL",
                pass_message: "Attribute $attribute identical over all representations",
                fail_message: "Attribute $attribute not identical over all representations",
            );
        }
    }

    //Private helper functions
    /**
     * @param array<array<string>> $spsComparisonResults
     **/
    private function addSPSComparison(
        Representation $representation,
        Segment $segment,
        array &$spsComparisonResults
    ): void {
        $spsConfiguration = $segment->getSPSConfiguration();
        foreach (array_keys($spsComparisonResults) as $attribute) {
            if (in_array($attribute, $spsConfiguration)) {
                $spsComparisonResults[$attribute][] = $spsConfiguration[$attribute];
            } else {
                $spsComparisonResults[$attribute][] = '';
            }
        }
    }
}
