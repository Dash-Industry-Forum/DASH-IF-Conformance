<?php

namespace App\Modules\DVB\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Services\Validators\Boxes;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Subtitle
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $handlerCase;
    private TestCase $stppCase;
    private TestCase $stppNamespaceCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "DVB",
            []
        ));
        $this->handlerCase = $this->legacyReporter->add(
            section: 'Subtitles',
            test: "For subtitle media, handler SHALL be 'subt'",
            skipReason: "No Subtitle stream found"
        );
        $this->stppCase = $this->legacyReporter->add(
            section: 'Subtitles',
            test: "Sample entry shall be 'stpp'",
            skipReason: "No Subtitle stream with sample description found"
        );
        $this->stppNamespaceCase = $this->legacyReporter->add(
            section: 'Subtitles',
            test: "Sample entry shall countain a namespace",
            skipReason: "No Subtitle stream with sample description found"
        );
    }

    //Public validation functions
    public function validateSubtitles(
        Representation $representation,
        Segment $segment,
        int $segmentIndex
    ): void {

        if ($representation->getTransientAttribute('mimeType') != 'application/mp4') {
            return;
        }
        if ($representation->getTransientAttribute('codecs') != 'stpp') {
            return;
        }

        $handlerType = $segment->getHandlerType();

        $this->handlerCase->pathAdd(
            result: $handlerType == 'subt',
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "'$handlerType' found",
            fail_message: "'$handlerType' found"
        );

        $this->validateSampleDescription($representation, $segment, $segmentIndex);
    }

    //Private helper functions
    private function validateSampleDescription(
        Representation $representation,
        Segment $segment,
        int $segmentIndex
    ): void {
        /** @var ?Boxes\STPPBox $sampleDescription **/
        $sampleDescription = $segment->getSampleDescription();


        if ($sampleDescription === null) {
            return;
        }




        $this->stppCase->pathAdd(
            result: $sampleDescription->codingname == 'stpp',
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "Valid sample description coding found",
            fail_message: "Invalid sample description coding found"
        );

        $this->stppNamespaceCase->pathAdd(
            result: $sampleDescription->namespace != '',
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "Namespace found",
            fail_message: "No namespacel found"
        );
    }
}
