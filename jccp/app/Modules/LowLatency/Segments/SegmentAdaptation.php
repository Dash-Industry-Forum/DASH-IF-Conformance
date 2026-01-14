<?php

namespace App\Modules\LowLatency\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Manifest\AdaptationSet;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use App\Interfaces\ModuleComponents\SegmentListComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SegmentAdaptation extends SegmentListComponent
{
    private TestCase $moofCase;
    private TestCase $smdsCase;
    private TestCase $durationCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "LEGACY",
                "Low Latency",
                []
            )
        );

        $this->moofCase = $this->reporter->add(
            section: '9.X.4.4',
            test: "Each Segment SHOULD include only a single 'moof' box",
            skipReason: "No Segment Adaptation Set Found",
        );
        $this->smdsCase = $this->reporter->add(
            section: '9.X.4.4',
            test: "A segment with a single 'moof' box may carry an 'smds' brand",
            skipReason: "No Segment with a single 'moof' box found",
        );
        $this->durationCase = $this->reporter->add(
            section: '9.X.4.4',
            test: "A segment SHALL NOT exceed 50% and SHOULD not exceed 30% of the tareget latency",
            skipReason: "No Segment Adaptation Set Found, or no target latency found",
        );
    }

    //Public validation functions
    /**
     * @param array<Segment> $segments
     **/
    public function validateSegmentList(Representation $representation, array $segments): void
    {
        foreach ($segments as $segmentIndex => $segment) {
            $this->validateSingleSegment($representation, $segment, $segmentIndex);
            $this->validateDuration($representation, $segment, $segmentIndex);
        }
    }

    //Private helper functions
    public function validateSingleSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        $topLevelBoxes = $segment->getTopLevelBoxNames();

        $boxCounts = array_count_values($topLevelBoxes);

        $singleMoof = $this->moofCase->pathAdd(
            path: $representation->path() . "-$segmentIndex",
            result: array_key_exists('moof', $boxCounts) && $boxCounts['moof'] == 1,
            severity: "WARN",
            pass_message: "Single 'moof' box",
            fail_message: "No or multiple 'moof' boxes"
        );


        if ($singleMoof) {
            $this->validateSMDS($representation, $segment, $segmentIndex);
        }
    }

    public function validateSMDS(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        $hasSMDSBrand = in_array('smds', $segment->getBrands());

        $this->smdsCase->pathAdd(
            path: $representation->path() . "-$segmentIndex",
            result: $hasSMDSBrand,
            severity: "INFO",
            pass_message: "Carries 'smds' brand",
            fail_message: "Does not carry 'smds' brand"
        );

        if ($hasSMDSBrand) {
            $this->smdsCase->pathAdd(
                path: $representation->path() . "-$segmentIndex",
                result: strpos($representation->getTransientAttribute('segmentProfiles'), 'smds') !== false,
                severity: "FAIL",
                pass_message: "'smds' brand carried, also signalled in Manifest",
                fail_message: "'smds' brand carried, but not in Manifest",
            );
        }
    }

    public function validateDuration(Representation $representation, Segment $segment, int $segmentIndex): void
    {

        $mpdCache = app(MPDCache::class);
        $latencyElements = $mpdCache->getDOMElements('Latency');

        if (!count($latencyElements)) {
            return;
        }

        $targetDuration = $latencyElements->item(0)->getAttribute("target");
        $segmentDuration = array_reduce($segment->getSegmentDurations(), function ($res, $cur) {
            $res += $cur;
            return $res;
        }, 0);


        if (
            $this->durationCase->pathAdd(
                path: $representation->path() . "-$segmentIndex",
                result: $segmentDuration * 1000 > intval($targetDuration) * 0.5,
                severity: "FAIL",
                pass_message: "Duration does not exceed 50% of the target",
                fail_message: "Duration exceeds 50% of the target",
            )
        ) {
            $this->durationCase->pathAdd(
                path: $representation->path() . "-$segmentIndex",
                result: $segmentDuration * 1000 > intval($targetDuration) * 0.3,
                severity: "WARN",
                pass_message: "Duration does not exceed 30% of the target",
                fail_message: "Duration exceeds 30% of the target",
            );
        }
    }
}
