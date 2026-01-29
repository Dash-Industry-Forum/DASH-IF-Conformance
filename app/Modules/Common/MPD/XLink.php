<?php

namespace App\Modules\Common\MPD;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use App\Interfaces\Module;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use Illuminate\Support\Facades\Log;

class XLink
{
    private SubReporter $xlinkReporter;
    private TestCase $xlinkCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->xlinkReporter = &$reporter->context(new ReporterContext(
            "MPD",
            "Global",
            "XLink",
            []
        ));

        $this->xlinkCase = $this->xlinkReporter->add(
            section: "XLink",
            test: "XLink validation succesful",
            skipReason: ''
        );
    }

    private function resolvePeriod(\DOMDocument $parent, \DOMElement $adoptedNode): \DOMNode
    {
        $href = $adoptedNode->getAttribute('xlink:href');
        if ($href == '') {
            return $adoptedNode;
        }

        $this->xlinkCase->add(
            result: $adoptedNode->getAttribute("xlink:actuate") == "onLoad",
            severity: "FAIL",
            pass_message: "Valid xlink:actuate found",
            fail_message: "Invalid xlink:actuate found",
        );

        $xlinkContents = file_get_contents($href);

        $xlinkDocument = new \DOMDocument();
        $xlinkDocument->loadXML($xlinkContents);

        if ($xlinkDocument->childNodes->length != 1){
            $this->xlinkCase->add(
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: "Found more than 1 child element, ignoring",
            );
            return $adoptedNode;
        }
        if ($xlinkDocument->firstElementChild->nodeName != "Period"){
            $this->xlinkCase->add(
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: "Node name different than xlink source, ignoring",
            );
            return $adoptedNode;
        }


        return $parent->adoptNode($xlinkDocument->firstElementChild->cloneNode(true));
    }


    public function resolveAndValidate(): void
    {
        //TODO: Implement xlink resolution on elements other than Period
        $mpdCache = app(MPDCache::class);

        $xmlDocument = $mpdCache->getDocument();

        $resolvedDocument = new \DOMDocument();
        $resolvedDocument->formatOutput = true;

        //Adopt MPD
        $mpdNode = $resolvedDocument->adoptNode($xmlDocument->getElementsByTagName('MPD')->item(0)->cloneNode(false));
        $resolvedDocument->appendChild($mpdNode);

        //Adopt Periods
        $periods = $xmlDocument->getElementsByTagName('Period');
        foreach ($periods as $period) {
            /**
             * @var \DOMElement|false $adopted
             * This actually is a DOMELement, but PHPStan complains if we don't tell it so
             **/
            $adopted = $resolvedDocument->adoptNode($period->cloneNode(true));
            if ($adopted) {
                $mpdNode->appendChild($this->resolvePeriod($resolvedDocument, $adopted));
            }
        }

        $resolvedPayload = $resolvedDocument->saveXML();
        $sessionDir = session_dir();
        file_put_contents($sessionDir . "resolved.mpd", $resolvedPayload);
    }
}
