<?php

namespace Tests\Unit\Manifest;

use Tests\TestCase;
use App\Services\Manifest\Representation;

class RepresentationUrlsTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function testReplacement(): void
    {

        $xmlString = '<Representation id="vp9/144p"/>';


        $representationDoc = new \DOMDocument();
        $representationDoc->loadXML($xmlString);
        $representationElem = $representationDoc->getElementsByTagName('Representation')->item(0);

        $representation = new Representation($representationElem, 0,0,0);



        $this->assertEquals($representation->fillTemplateUrl('', 4), '');
        $this->assertEquals($representation->fillTemplateUrl('$Number$', 4), '4');
        $this->assertEquals($representation->fillTemplateUrl('$Number%03d$', 4), '004');
        $this->assertEquals($representation->fillTemplateUrl('$Number%05d$', 4), '00004');
    }
}
