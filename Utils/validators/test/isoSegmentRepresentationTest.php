<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../isoSegmentRepresentation.php';
require_once __DIR__ . '/../../MPDUtility.php';


final class ISOSegmentRepresentationTest extends TestCase
{
    public function testUnknownSampleDescription()
    {
        $r = new DASHIF\ISOSegmentValidatorRepresentation();
        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<hdlr version="0" flags="0">
      </hdlr>'
        );
        $this->assertNull($r->getSampleDescription());
    }
    public function testSTPPSampleDescription()
    {
        $r = new DASHIF\ISOSegmentValidatorRepresentation();
        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<hdlr version="0" flags="0" handler_type="subt">
      </hdlr>'
        );
        $this->assertNull($r->getSampleDescription());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<mdia>
        <hdlr version="0" flags="0" handler_type="subt">
        </hdlr>
        <subt_sampledescription>
        </subt_sampledescription>
      </mdia>'
        );
        $this->assertNull($r->getSampleDescription());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<mdia>
        <hdlr version="0" flags="0" handler_type="subt">
        </hdlr>
        <subt_sampledescription sdType="stpp">
        </subt_sampledescription>
      </mdia>'
        );
        $this->assertNull($r->getSampleDescription());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<mdia>
        <hdlr version="0" flags="0" handler_type="subt">
        </hdlr>
        <subt_sampledescription sdType="stpp">
          <stpp
            namespace="someNamespace"
            schema_location="someSchemaLoc"
            auxiliary_mime_types="someMimeType"
          />
        </subt_sampledescription>
      </mdia>'
        );
        $sampleDesc = $r->getSampleDescription();
        $this->assertNotNull($sampleDesc);
        $this->assertEquals(DASHIF\Boxes\DescriptionType::Subtitle, $sampleDesc->type);
        $this->assertEquals("someNamespace", $sampleDesc->namespace);
        $this->assertEquals("someSchemaLoc", $sampleDesc->schemaLocation);
        $this->assertEquals("someMimeType", $sampleDesc->auxiliaryMimeTypes);
    }
    public function testWVTTSampleDescription()
    {
        $r = new DASHIF\ISOSegmentValidatorRepresentation();
        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<hdlr version="0" flags="0" handler_type="text">
      </hdlr>'
        );
        $this->assertNull($r->getSampleDescription());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<mdia>
        <hdlr version="0" flags="0" handler_type="text">
        </hdlr>
        <text_sampledescription>
        </text_sampledescription>
      </mdia>'
        );
        $this->assertNull($r->getSampleDescription());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<mdia>
        <hdlr version="0" flags="0" handler_type="text">
        </hdlr>
        <text_sampledescription sdType="wvtt">
        </text_sampledescription>
      </mdia>'
        );
        $sampleDesc = $r->getSampleDescription();
        $this->assertNotNull($sampleDesc);
        $this->assertEquals(DASHIF\Boxes\DescriptionType::Text, $sampleDesc->type);
        $this->assertEquals("wvtt", $sampleDesc->codingname);
    }
    public function testSidxDurations()
    {
        $r = new DASHIF\ISOSegmentValidatorRepresentation();
        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<container>
      </container>'
        );
        $this->assertEquals(array(), $r->getSegmentDurations());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<container>
         <sidx version="0" flags="0"
            referenceID="1"
            timeScale="12288"
            earliestPresentationTime="0"
            firstOffset="0"
            referenceCount="1"
            reference_type_1="0"
            cumulatedDuration="2.000000"
         ></sidx>
         <sidx version="0" flags="0"
           referenceID="1"
           timeScale="12288"
           earliestPresentationTime="24576"
           firstOffset="0"
           referenceCount="1"
           reference_type_1="0"
           cumulatedDuration="2.000000"
           ></sidx>
      </container>'
        );
        $this->assertEquals([2,2], $r->getSegmentDurations());
    }

    public function testGetProtectionScheme()
    {
        $r = new DASHIF\ISOSegmentValidatorRepresentation();
        $this->assertNull($r->getProtectionScheme());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
             <sinf>
               <frma original_format="avc1"
                 >
               </frma>
               <schm scheme="cbcs" version="65536"
                 >
               </schm>
               <schi>
                  comment="1 contained atoms" >
                 <tenc       version="1" flags="0"
                   default_IsEncrypted="71936"
                   default_IV_size="16"
                   default_KID="3915338731061279337218105242179178121154127"
                   >
                 </tenc>
               </schi>
             </sinf>
          </container>'
        );

        $protection = $r->getProtectionScheme();

        $this->assertNotNull($protection);
        $this->assertEquals('avc1', $protection->originalFormat);
        $this->assertEquals('cbcs', $protection->scheme->schemeType);
        $this->assertEquals('65536', $protection->scheme->schemeVersion);
        $this->assertEquals(true, $protection->encryption->isEncrypted);
        $this->assertEquals(16, $protection->encryption->ivSize);
        $this->assertEquals("3915338731061279337218105242179178121154127", $protection->encryption->kid);
    }
}