<?php

namespace App\Modules\Dolby\Segments;

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

class TocDac4
{
    //Private subreporters
    private SubReporter $cmafReporter;

    private TestCase $bitstreamCase;
    private TestCase $fsIndexCase;
    private TestCase $framerateCase;
    private TestCase $presentationCountCase;
    private TestCase $programIdCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->cmafReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "ETSI TS 103 190-2",
            "V1.2.1",
            []
        ));

        $this->bitstreamCase = $this->cmafReporter->add(
            section: 'E.6.3 [13197]',
            test: "Bitstream version must match between TOC and DAC4",
            skipReason: 'No compatible track found'
        );
        $this->fsIndexCase = $this->cmafReporter->add(
            section: 'E.6.4 [14309]',
            test: "FS index must match between TOC and DAC4",
            skipReason: 'No compatible track found'
        );
        $this->framerateCase = $this->cmafReporter->add(
            section: 'E.6.5 [14209]',
            test: "Framerate index version must match between TOC and DAC4",
            skipReason: 'No compatible track found'
        );
        $this->presentationCountCase = $this->cmafReporter->add(
            section: 'E.6.6 [14215]',
            test: "Number of presentations must match between TOC and DAC4",
            skipReason: 'No compatible track found'
        );
        $this->programIdCase = $this->cmafReporter->add(
            section: 'E.6.7 [14221]',
            test: "Short program id must match between TOC and DAC4",
            skipReason: 'No compatible track found'
        );
    }

    //Public validation functions
    public function validateTocDac4(Representation $representation, Segment $segment): void
    {
        if ($representation->getTransientAttribute('mimeType') != 'audio/mp4') {
            return;
        }

        $codecSubstring = substr($representation->getTransientAttribute('codecs'), 0, 4);

        if ($codecSubstring != "ac-3" && $codecSubstring != "ec-3" && $codecSubstring != "ac-4") {
            return;
        }

        $dsiList = $segment->boxAccess()->ac4DSI();
        $tocList = $segment->boxAccess()->ac4TOC();

        if (count($dsiList) == 0 || count($tocList) == 0) {
            return;
        }

        $dsi = $dsiList[0];

        foreach ($tocList as $tocIndex => $toc) {
            $this->bitstreamCase->pathAdd(
                path: $representation->path() . "-init:$tocIndex",
                result: $toc->bitstream_version == $dsi->bitstream_version,
                severity: "FAIL",
                pass_message: "Value matches",
                fail_message: "Values mismatched",
            );
            $this->fsIndexCase->pathAdd(
                path: $representation->path() . "-init:$tocIndex",
                result: $toc->fs_index == $dsi->fs_index,
                severity: "FAIL",
                pass_message: "Value matches",
                fail_message: "Values mismatched",
            );
            $this->framerateCase->pathAdd(
                path: $representation->path() . "-init:$tocIndex",
                result: $toc->frame_rate_index == $dsi->frame_rate_index,
                severity: "FAIL",
                pass_message: "Value matches",
                fail_message: "Values mismatched",
            );
            $this->presentationCountCase->pathAdd(
                path: $representation->path() . "-init:$tocIndex",
                result: $toc->n_presentations == $dsi->n_presentations,
                severity: "FAIL",
                pass_message: "Value matches",
                fail_message: "Values mismatched",
            );
            $this->programIdCase->pathAdd(
                path: $representation->path() . "-init:$tocIndex",
                result: $toc->short_program_id == $dsi->short_program_id,
                severity: "FAIL",
                pass_message: "Value matches",
                fail_message: "Values mismatched",
            );
        }
    }

    //Private helper functions
}
