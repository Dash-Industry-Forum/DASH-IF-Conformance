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

    public function testGetProtectionScheme()
    {
        $r = new DASHIF\MP4BoxRepresentation();
        $this->assertNull($r->getProtectionScheme());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
            <ProtectionSchemeInfoBox Size="97" Type="sinf" Specification="p12" Container="ipro sample_entry">
              <OriginalFormatBox Size="12" Type="frma" Specification="p12" Container="sinf rinf" data_format="avc1">
</OriginalFormatBox>
              <SchemeTypeBox Size="20" Type="schm" Version="0" Flags="0" Specification="p12" Container="sinf rinf" scheme_type="cbcs" scheme_version="65536">
</SchemeTypeBox>
              <SchemeInformationBox Size="57" Type="schi" Specification="p12" Container="sinf rinf">
                <TrackEncryptionBox Size="49" Type="tenc" Version="1" Flags="0" Specification="cenc" Container="schi" isEncrypted="1" constant_IV_size="16" constant_IV="0x0A610676CB88F302D10AC8BC66E039ED" KID="0x279926496A7F5D25DA69F2B3B2799A7F" crypt_byte_block="1" skip_byte_block="9">
                </TrackEncryptionBox>
              </SchemeInformationBox>
            </ProtectionSchemeInfoBox>
          </container>'
        );

        $protection = $r->getProtectionScheme();

        $this->assertNotNull($protection);
        $this->assertEquals('avc1', $protection->originalFormat);
        $this->assertEquals('cbcs', $protection->scheme->schemeType);
        $this->assertEquals('65536', $protection->scheme->schemeVersion);
        $this->assertEquals(true, $protection->encryption->isEncrypted);
        $this->assertEquals(16, $protection->encryption->ivSize);
        $this->assertEquals("0x0A610676CB88F302D10AC8BC66E039ED", $protection->encryption->iv);
        $this->assertEquals("0x279926496A7F5D25DA69F2B3B2799A7F", $protection->encryption->kid);
        $this->assertEquals(1, $protection->encryption->cryptByteBlock);
        $this->assertEquals(9, $protection->encryption->skipByteBlock);

    }

    public function testGetSampleAuxiliaryInformation()
    {
        $r = new DASHIF\MP4BoxRepresentation();
        $this->assertNull($r->getSampleAuxiliaryInformation());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
          </container>'
        );
        $this->assertNull($r->getSampleAuxiliaryInformation());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
            <SampleAuxiliaryInfoOffsetBox Size="24" Type="saio" Version="1" Flags="0" Specification="p12" Container="stbl traf" entry_count="1">
              <SAIChunkOffset offset="230"/>
            </SampleAuxiliaryInfoOffsetBox>

          </container>'
        );
        $this->assertNotNull($r->getSampleAuxiliaryInformation());
    }
    public function testGetKindBoxes()
    {
        $r = new DASHIF\MP4BoxRepresentation();
        $this->assertEquals($r->getKindBoxes(), array());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
          </container>'
        );
        $this->assertEquals($r->getKindBoxes(), array());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
            <KindBox Size="41" Type="kind" Version="0" Flags="0" Specification="p12" Container="udta" schemeURI="urn:mpeg:dash:role:2011" value="main">
            </KindBox>
            <KindBox Size="46" Type="kind" Version="0" Flags="0" Specification="p12" Container="udta" schemeURI="urn:mpeg:dash:role:2011" value="alternate">
            </KindBox>

          </container>'
        );
        $this->assertEquals($r->getKindBoxes(), array());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
            <UserDataBox>
              <KindBox Size="41" Type="kind" Version="0" Flags="0" Specification="p12" Container="udta" schemeURI="urn:mpeg:dash:role:2011" value="main">
              </KindBox>
              <KindBox Size="46" Type="kind" Version="0" Flags="0" Specification="p12" Container="udta" schemeURI="urn:mpeg:dash:role:2011" value="alternate">
              </KindBox>
            </UserDataBox>

          </container>'
        );
        $kindBoxes = $r->getKindBoxes();
        $this->assertEquals(count($kindBoxes), 2);
        $this->assertEquals($kindBoxes[0]->schemeURI, "urn:mpeg:dash:role:2011");
        $this->assertEquals($kindBoxes[0]->value, "main");
        $this->assertEquals($kindBoxes[1]->schemeURI, "urn:mpeg:dash:role:2011");
        $this->assertEquals($kindBoxes[1]->value, "alternate");
    }
}
