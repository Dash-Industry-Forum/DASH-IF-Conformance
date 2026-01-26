<?php

namespace App\Modules\LowLatency\MPD;

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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SegmentURL
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $presentCase;
    private TestCase $segmentTemplateCase;
    private TestCase $segmentTimelineCase;


    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "MPD",
            "LEGACY",
            "Low Latency",
            []
        ));

        $this->presentCase = $this->legacyReporter->add(
            section: '9.X.4.3',
            test: 'Either (constrained) SegmentTemplate or SegmentTimeline shall be present',
            skipReason: "",
        );
        $this->segmentTemplateCase = $this->legacyReporter->add(
            section: '9.X.4.3',
            test: 'SegmentTemplate will have @duration set and have @media containing $Number$',
            skipReason: "No SegmentTemplate found",
        );
        $this->segmentTimelineCase = $this->legacyReporter->add(
            section: '9.X.4.3',
            test: 'SegmentTimeline will have @media containing $Number$ and $Time$',
            skipReason: "No SegmentTimeline found",
        );
    }

    //Public validation functions
    public function validateSegmentURL(): void
    {
        $mpdCache = app(MPDCache::class);
        foreach ($mpdCache->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                $validTemplate = $this->validateSegmentTemplate($adaptationSet);
                $validTimeline = $this->validateSegmentTimeline($adaptationSet);
                $this->presentCase->pathAdd(
                    path: $adaptationSet->path(),
                    result: $validTemplate xor $validTimeline,
                    severity: "FAIL",
                    pass_message: "Either element found valid",
                    fail_message: "Neither or both elements found",
                );
            }
        }
    }
    public function validateSegmentTemplate(AdaptationSet $adaptationSet): bool
    {
        $segmentTemplates = $adaptationSet->getDOMElements('SegmentTemplate');
        if (!count($segmentTemplates)) {
            $segmentTemplates = $adaptationSet->getPeriod()->getDOMElements('SegmentTemplate');
        }
        if (!count($segmentTemplates)) {
            return false;
        }
        //We validate only the first
        $segmentTemplate = $segmentTemplates->item(0);


        $validDuration = $this->segmentTemplateCase->pathAdd(
            path: $adaptationSet->path(),
            result: $segmentTemplate->getAttribute('duration') != '',
            severity: "WARN",
            pass_message: "Duration attribute found",
            fail_message: "Duration not set",
        );

        $validMedia = $this->segmentTemplateCase->pathAdd(
            path: $adaptationSet->path(),
            result: strpos($segmentTemplate->getAttribute('media'), '$Number$') !== false,
            severity: "WARN",
            pass_message: '$Number$ in @media',
            fail_message: '$Number$ pattern not found',
        );

        return $validDuration && $validMedia;
    }

    public function validateSegmentTimeline(AdaptationSet $adaptationSet): bool
    {
        $segmentTimelines = $adaptationSet->getDOMElements('SegmentTimeline');
        if (!count($segmentTimelines)) {
            $segmentTemplates = $adaptationSet->getPeriod()->getDOMElements('SegmentTimeline');
        }
        if (!count($segmentTimelines)) {
            return false;
        }
        //We validate only the first
        $segmentTimeline = $segmentTimelines->item(0);

        $mediaAttribute = $segmentTimeline->getAttribute('media');

        $validMedia = $this->segmentTimelineCase->pathAdd(
            path: $adaptationSet->path(),
            result: strpos($mediaAttribute, '$Number$') !== false &&
                    strpos($mediaAttribute, '$Time$') !== false,
            severity: "WARN",
            pass_message: '$Number$ and $Time$ in @media',
            fail_message: '$Number$ and/or $Time$ pattern not found',
        );
        return $validMedia;
    }
    //Private helper functions
}
