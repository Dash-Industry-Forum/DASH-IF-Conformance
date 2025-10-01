<?php

namespace App\Modules\DVB\MPD;

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

class Codecs
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $codecCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "MPD",
            "LEGACY",
            "DVB",
            []
        ));

        $this->codecCase = $this->legacyReporter->add(
            section: 'Codec Information',
            test: 'The codec should be supported by the specification',
            skipReason: "No representation found",
        );
    }

    //Public validation functions
    public function validateCodecs(Representation $representation): void
    {
        $validCodecs = [
            'avc', 'hev1', 'hvc1',
            'mp4a', 'ec-3', 'ac-4',
            'dtsc', 'dtsh', 'dtse', 'dtsl',
            'stpp'
        ];
        $codecs = $representation->getTransientAttribute("codecs");

        foreach (explode(',', $codecs) as $codec) {
            $isValidCodec = false;
            foreach ($validCodecs as $validCodec) {
                if (str_starts_with($codec, $validCodec)) {
                    $isValidCodec = true;
                    break;
                }
            }
            $this->codecCase->pathAdd(
                path: $representation->path(),
                result: $isValidCodec,
                severity: "WARN",
                pass_message: "Valid codec",
                fail_message: "Invalid codec - $codec"
            );
        }
    }

    //Private helper functions
}
