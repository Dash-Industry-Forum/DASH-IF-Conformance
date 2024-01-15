<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../mp4BoxRepresentation.php';
require_once __DIR__ . '/../../MPDUtility.php';


final class MP4BoxRepresentationTest extends TestCase
{
    public function testUnknownSampleDescription()
    {
        $r = new DASHIF\MP4BoxRepresentation();
        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<MediaBox>
        <HandlerBox flags="0"></HandlerBox>
      </MediaBox>'
        );
        $this->assertNull($r->getSampleDescription());
    }
    public function testSTPPSampleDescription()
    {
        $r = new DASHIF\MP4BoxRepresentation();
        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<MediaBox>
        <HandlerBox hdlrType="subt" flags="0"></HandlerBox>
      </MediaBox>'
        );
        $this->assertNull($r->getSampleDescription());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<MediaBox>
        <HandlerBox hdlrType="subt" flags="0"></HandlerBox>
        <XMLSubtitleSampleEntryBox>
        </XMLSubtitleSampleEntryBox>
      </MediaBox>'
        );
        $this->assertNull($r->getSampleDescription());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<MediaBox>
        <HandlerBox hdlrType="subt" flags="0"></HandlerBox>
        <XMLSubtitleSampleEntryBox 
          Type="stpp" 
          namespace="someNamespace"
          schema_location="someSchemaLoc"
          auxiliary_mime_types="someMimeType"
        >
        </XMLSubtitleSampleEntryBox>
      </MediaBox>'
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
        $r = new DASHIF\MP4BoxRepresentation();
        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<MediaBox>
        <HandlerBox hdlrType="text" flags="0"></HandlerBox>
      </MediaBox>'
        );
        $this->assertNull($r->getSampleDescription());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<MediaBox>
        <HandlerBox hdlrType="text" flags="0"></HandlerBox>
        <WebVTTSampleEntryBox>
        </WebVTTSampleEntryBox>
      </MediaBox>'
        );
        $this->assertNull($r->getSampleDescription());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<MediaBox>
        <HandlerBox hdlrType="text" flags="0"></HandlerBox>
        <WebVTTSampleEntryBox Type="wvtt">
        </WebVTTSampleEntryBox>
      </MediaBox>'
        );
        $sampleDesc = $r->getSampleDescription();
        $this->assertNotNull($sampleDesc);
        $this->assertEquals(DASHIF\Boxes\DescriptionType::Text, $sampleDesc->type);
        $this->assertEquals("wvtt", $sampleDesc->codingname);
    }


    public function testTopLevelBoxNames()
    {
        $r = new DASHIF\MP4BoxRepresentation();
        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<ISOBaseMediaFileTrace>
        <IsoMediaFile>
          <HandlerBox Type="hdlr"></HandlerBox>
          <HandlerBox Type="sidx"></HandlerBox>
        </IsoMediaFile>
      </ISOBaseMediaFileTrace>'
        );
        $this->assertEquals(['hdlr','sidx'], $r->getTopLevelBoxNames());
    }
    public function testSidxDurations()
    {
        $r = new DASHIF\MP4BoxRepresentation();
        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<container>
      </container>'
        );
        $this->assertEquals(array(), $r->getSegmentDurations());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
            '<container>
               <SegmentIndexBox timescale="12288" earliest_presentation_time="49152" first_offset="0">
                 <Reference type="0" size="248828" duration="12288" startsWithSAP="1" SAP_type="1" SAPDeltaTime="0"/>
                 <Reference type="0" size="248828" duration="12288" startsWithSAP="1" SAP_type="1" SAPDeltaTime="0"/>
               </SegmentIndexBox>

               <SegmentIndexBox timescale="12288" earliest_presentation_time="73728" first_offset="0">
                 <Reference type="0" size="268947" duration="24576" startsWithSAP="1" SAP_type="1" SAPDeltaTime="0"/>
               </SegmentIndexBox>
             </container>'
        );
        $this->assertEquals([2,2], $r->getSegmentDurations());
    }
}
