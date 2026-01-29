<?php

namespace App\Services\Manifest;

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
    public function __construct()
    {
    }

    private function resolvePeriod(\DOMDocument $parent, \DOMElement $adoptedNode): \DOMNode
    {
        $href = $adoptedNode->getAttribute('xlink:href');
        if ($href == '') {
            return $adoptedNode;
        }

        $xlinkContents = file_get_contents($href);

        $xlinkDocument = new \DOMDocument();
        $xlinkDocument->loadXML($xlinkContents);

        if ($xlinkDocument->childNodes->length != 1) {
            return $adoptedNode;
        }
        if ($xlinkDocument->firstElementChild->nodeName != "Period") {
            return $adoptedNode;
        }


        return $parent->adoptNode($xlinkDocument->firstElementChild->cloneNode(true));
    }

    public function resolveAndValidate(string $manifest): string
    {
        //TODO: Implement xlink resolution on elements other than Period
        $mpdCache = app(MPDCache::class);

        $xmlDocument = new \DOMDocument();
        $xmlDocument->loadXML($manifest);

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
        return $resolvedPayload;
    }
}
