<?php

namespace App\Modules\CMAF\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MetaData
{
    //Private subreporters
    private SubReporter $cmafReporter;

    private TestCase $metaCase;
    private TestCase $udtaCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->cmafReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "Legacy",
            "CMAF",
            []
        ));

        $this->metaCase = $this->cmafReporter->add(
            section: 'Section 7.5.2',
            test: "When metadata is carried in a 'meta' box, it SHALL NOT occur at the file level",
            skipReason: 'No video track found'
        );
        $this->udtaCase = $this->cmafReporter->add(
            section: 'Section 7.5.2',
            test: "When metadata is carried in a 'udta' box, it SHALL NOT occur at the file level",
            skipReason: 'No video track found'
        );
    }

    //Public validation functions
    public function validateMetaData(Representation $representation, Segment $segment, int $segmentIndex): void
    {

        $boxList = $segment->getTopLevelBoxNames();

        $this->metaCase->pathAdd(
            result: !in_array('meta', $boxList),
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "'meta' not found at top level",
            fail_message: "'meta' found at top level",
        );
        $this->udtaCase->pathAdd(
            result: !in_array('udta', $boxList),
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "'udta' not found at top level",
            fail_message: "'udta' found at top level",
        );
    }

    //Private helper functions
}
