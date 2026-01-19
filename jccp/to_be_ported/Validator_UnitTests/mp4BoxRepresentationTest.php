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
    public function testGetPsshBoxes()
    {
        $r = new DASHIF\MP4BoxRepresentation();
        $this->assertEquals($r->getPsshBoxes(), array());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
          </container>'
        );
        $this->assertEquals($r->getPsshBoxes(), array());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
             <ProtectionSystemHeaderBox Size="109" Type="pssh" Version="1" Flags="0" Specification="cenc" Container="moov moof meta" SystemID="0x6770616363656E6364726D746F6F6C31">
              <PSSHKey KID="0x279926496A7F5D25DA69F2B3B2799A7F"/>
              <PSSHKey KID="0x676CB88F302D10227992649885984045"/>
              <PSSHData size="41" value="0x084349443D546F746F5A8E62EB7DF2829F7D583A722E60DA8AD0F9A2234837719EF2A7332871FC5517"/>
            </ProtectionSystemHeaderBox>
          </container>'
        );
        $psshBoxes = $r->getPsshBoxes();
        $this->assertEquals(count($psshBoxes), 1);
        $this->assertEquals($psshBoxes[0]->systemId, "0x6770616363656E6364726D746F6F6C31");
        $this->assertEquals(count($psshBoxes[0]->keys), 2);
        $this->assertEquals(count($psshBoxes[0]->data), 1);
    }
    public function testGetSencBoxes()
    {
        $r = new DASHIF\MP4BoxRepresentation();
        $this->assertEquals($r->getSencBoxes(), array());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
          </container>'
        );
        $this->assertEquals($r->getSencBoxes(), array());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
          <SampleEncryptionBox Size="1056" Type="senc" Specification="cenc" Container="trak traf" sampleCount="2">
            <FullBoxInfo Version="0" Flags="0x2"/>
            <SampleEncryptionEntry sampleNumber="1" IV_size="0" SubsampleCount="1">
              <SubSampleEncryptionEntry NumClearBytes="10" NumEncryptedBytes="236125"/>
            </SampleEncryptionEntry>
            <SampleEncryptionEntry sampleNumber="2" IV_size="10" SubsampleCount="1">
              <SubSampleEncryptionEntry NumClearBytes="10" NumEncryptedBytes="34819"/>
            </SampleEncryptionEntry>
            </SampleEncryptionBox>
          </container>'
        );
        $sencBoxes = $r->getSencBoxes();
        $this->assertEquals(count($sencBoxes), 1);
        $this->assertEquals($sencBoxes[0]->sampleCount, 2);
        $this->assertEquals(count($sencBoxes[0]->ivSizes), 2);
        $this->assertEquals($sencBoxes[0]->ivSizes[0], 0);
        $this->assertEquals($sencBoxes[0]->ivSizes[1], 10);
    }
    public function testGetEmsgBoxes()
    {
        $r = new DASHIF\MP4BoxRepresentation();
        $this->assertEquals($r->getEmsgBoxes(), array());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
          </container>'
        );
        $this->assertEquals($r->getEmsgBoxes(), array());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
            <EventMessageBox Size="85" Type="emsg" Version="1" Flags="0" Specification="dash" Container="file" 
                             timescale="90000" presentation_time="450000" event_duration="270000" event_id="0" 
                             scheme_id_uri="https://aomedia.org/emsg/ID3" value="1" 
                             message_data="0x4944330400000000000C545858580000000200000300" >
            </EventMessageBox>
          </container>'
        );
        $emsgBoxes = $r->getEmsgBoxes();
        $this->assertEquals(count($emsgBoxes), 1);
        $this->assertEquals($emsgBoxes[0]->timeScale, 90000);
        $this->assertEquals($emsgBoxes[0]->presentationTime, 450000);
        $this->assertEquals($emsgBoxes[0]->eventDuration, 270000);

        //Optionals
        $this->assertEquals($emsgBoxes[0]->id, null);
        $this->assertEquals($emsgBoxes[0]->schemeIdUri, 'https://aomedia.org/emsg/ID3');
        $this->assertEquals($emsgBoxes[0]->value, 1);
        $this->assertEquals($emsgBoxes[0]->messageData,  '0x4944330400000000000C545858580000000200000300');

    }

    public function testGetBoxNameTree()
    {
        $r = new DASHIF\MP4BoxRepresentation();

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
            <IsoMediaFile>
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
          </IsoMediaFile>
          </container>'
        );

        $boxTree = $r->getBoxNameTree();

        $this->assertEquals(count($boxTree->children), 1);
        $this->assertEquals($boxTree->children[0]->name, 'sinf');
        $this->assertEquals(count($boxTree->children[0]->children), 3);
        $this->assertEquals($boxTree->children[0]->children[0]->name, 'frma');
        $this->assertEquals($boxTree->children[0]->children[1]->name, 'schm');
        $this->assertEquals($boxTree->children[0]->children[2]->name, 'schi');

        $filtered = $boxTree->filterChildrenRecursive('schi');
        $this->assertEquals(count($filtered), 1);
    }
    public function testGetSampleDuration()
    {
        $r = new DASHIF\MP4BoxRepresentation();
        $this->assertNull($r->getSampleDuration());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
          </container>'
        );
        $this->assertNull($r->getSampleDuration());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
            <TrackExtendsBox SampleDuration="424242" />
          </container>'
        );
        $this->assertEquals($r->getSampleDuration(), 424.242);

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
            <TrackExtendsBox SampleDuration="12000" />
            <MediaHeaderBox TimeScale="400" />
          </container>'
        );
        $this->assertEquals($r->getSampleDuration(), 30.0);
    }

    public function testGetFragmentDurations()
    {
      $r = new DASHIF\MP4BoxRepresentation();
        $this->assertNull($r->getFragmentDurations());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
          </container>'
        );
        $this->assertNull($r->getFragmentDurations());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
             <SegmentIndexBox Size="44" Type="sidx" Version="0" Flags="0" Specification="p12" Container="file" reference_ID="1" timescale="12288" earliest_presentation_time="0" first_offset="0">
              <Reference type="0" size="467615" duration="16384" startsWithSAP="1" SAP_type="1" SAPDeltaTime="0"/>
            </SegmentIndexBox>
            <SegmentIndexBox Size="44" Type="sidx" Version="0" Flags="0" Specification="p12" Container="file" reference_ID="1" timescale="12288" earliest_presentation_time="16384" first_offset="0">
              <Reference type="0" size="1855560" duration="51712" startsWithSAP="1" SAP_type="1" SAPDeltaTime="0"/>
            </SegmentIndexBox>
          </container>'
        );
        $fragmentDurations = $r->getFragmentDurations();

        $this->assertEquals(count($fragmentDurations), 2);
        //With floor to make sure we dont have rounding errors during the test.
        $this->assertEquals(floor($fragmentDurations[0] * 1000), 1333);
        $this->assertEquals(floor($fragmentDurations[1] * 1000), 2875);
    }

    public function testGetSeigDescriptionGroups()
    {
      $r = new DASHIF\MP4BoxRepresentation();
        $this->assertNull($r->getSeigDescriptionGroups());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
          </container>'
        );
        $this->assertNull($r->getSeigDescriptionGroups());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
            <SampleGroupDescriptionBox/>
          </container>'
        );
        $this->assertEquals(count($r->getSeigDescriptionGroups()), 1);
        $this->assertEquals($r->getSeigDescriptionGroups()[0]->groupingType, '');

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
             <SampleGroupDescriptionBox Size="61" Type="sgpd" Version="1" Flags="0" Specification="p12" Container="stbl traf" grouping_type="seig" default_length="37">
                <CENCSampleEncryptionGroupEntry IsEncrypted="1" IV_size="0" KID="0x676CB88F302D10227992649885984045" constant_IV_size="16" constant_IV="0x0A610676CB88F302D10AC8BC66E039ED"/>
              </SampleGroupDescriptionBox>
          </container>'
        );

        $seigDescriptions = $r->getSeigDescriptionGroups();
        $this->assertEquals(count($seigDescriptions), 1);
        $this->assertEquals($seigDescriptions[0]->groupingType, 'seig');
        $this->assertEquals(count($seigDescriptions[0]->entries), 1);

        $seigEntry = $seigDescriptions[0]->entries[0];
        $this->assertEquals($seigEntry->isEncrypted, 1);
        $this->assertEquals($seigEntry->ivSize, 0);
        $this->assertEquals($seigEntry->kid, '0x676CB88F302D10227992649885984045');
        $this->assertEquals($seigEntry->constantIvSize, 16);
        $this->assertEquals($seigEntry->constantIv, '0x0A610676CB88F302D10AC8BC66E039ED');
    }

    public function testGetSampleGroups()
    {
        $r = new DASHIF\MP4BoxRepresentation();
        $this->assertNull($r->getSampleGroups());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
          </container>'
        );
        $this->assertNull($r->getSampleGroups());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
            <SampleGroupBox/>
          </container>'
        );
        $this->assertEquals(count($r->getSampleGroups()), 1);
        $this->assertEquals(count($r->getSampleGroups()[0]->sampleCounts), 0);
        $this->assertEquals(count($r->getSampleGroups()[0]->groupDescriptionIndices), 0);

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
            <SampleGroupBox Size="28" Type="sbgp" Version="0" Flags="0" Specification="p12" Container="stbl traf" grouping_type="seig">
              <SampleGroupBoxEntry sample_count="101" group_description_index="1"/>
            </SampleGroupBox>
          </container>'
        );

        $sampleGroups = $r->getSampleGroups();
        $this->assertEquals(count($sampleGroups), 1);
        $this->assertEquals(count($sampleGroups[0]->sampleCounts), 1);
        $this->assertEquals($sampleGroups[0]->sampleCounts[0], 101);
        $this->assertEquals(count($sampleGroups[0]->groupDescriptionIndices), 1);
        $this->assertEquals($sampleGroups[0]->groupDescriptionIndices[0], 1);
    }
}
