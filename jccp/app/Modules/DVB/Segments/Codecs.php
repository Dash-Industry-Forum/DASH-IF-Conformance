<?php

namespace App\Modules\DVB\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

// TODO This is actually a manifest check
class Codecs
{
    //Private subreporters
    private SubReporter $legacyReporter;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "DVB",
            []
        ));
    }

    //Public validation functions
    public function validateCodecs(Representation $representation, Segment $segment): void
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
            foreach($validCodecs as $validCodec){
                if (str_starts_with($codec, $validCodec)){
                    $isValidCodec = true;
                    break;
                }
            }
            $this->legacyReporter->test(
                section: 'Codec Information',
                test: 'The codec should be supported by the specification',
                result: $isValidCodec,
                severity: "WARN",
                pass_message: $representation->path() . " Codec $codec in list of valid codecs",
                fail_message: $representation->path() . " Codec $codec not in list of valid codecs",
            );
        }
    }

    //Private helper functions
}
