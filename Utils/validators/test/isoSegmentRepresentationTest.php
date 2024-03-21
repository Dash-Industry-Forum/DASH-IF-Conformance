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

    public function testGetEmsgBoxes()
    {
        $r = new DASHIF\ISOSegmentValidatorRepresentation();
        $this->assertEquals($r->getEmsgBoxes(), array());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
          </container>'
        );
        $this->assertEquals($r->getEmsgBoxes(), array());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
            <emsg version="1" flags="0"
              offset="0"
              timeScale="0"
              presentationTimeDelta="115200000"
              eventDuration="69120000"
              id="104"
              >
            </emsg>

          </container>'
        );
        $emsgBoxes = $r->getEmsgBoxes();
        $this->assertEquals(count($emsgBoxes), 1);
        $this->assertEquals($emsgBoxes[0]->timeScale, 0);
        $this->assertEquals($emsgBoxes[0]->presentationTime, 115200000);
        $this->assertEquals($emsgBoxes[0]->eventDuration, 69120000);

        //Optionals
        $this->assertEquals($emsgBoxes[0]->id, 104);
        $this->assertEquals($emsgBoxes[0]->schemeIdUri, null);
        $this->assertEquals($emsgBoxes[0]->value, null);
        $this->assertEquals($emsgBoxes[0]->messageData, null);

    }

    public function testGetBoxNameTree()
    {
        $r = new DASHIF\ISOSegmentValidatorRepresentation();

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
         '<atomlist>
            <ftyp majorbrand="iso6" version="0x1">
            </ftyp>
            <moov>
              <mvhd       version="0" flags="0"
                creationTime="0x3789971995"
                modificationTime="0x3789971995"
                timeScale="1000"
                duration="0"
                nextTrackID="2"
                >
              </mvhd>
              <trak>
                <tkhd     version="0" flags="3"
                  creationTime="0x0"
                  modificationTime="0x3789971995"
                  trackID="1"
                  duration="0"
                  volume="0.000000"
                  width="1920.000000"
                  height="1080.000000"
                  >
                </tkhd>
                <edts>
                  <elst   version="0" flags="0"
                    entryCount="1"
                    >
                      <elstEntry duration="0" mediaTime="1024" mediaRate="1.000000" />
                  </elst>
                </edts>
              </trak>
            </moov>
          </atomlist>'
        );

        $boxTree = $r->getBoxNameTree();

        $this->assertEquals(count($boxTree->children), 2);
        $this->assertEquals($boxTree->children[0]->name, 'ftyp');
        $this->assertEquals($boxTree->children[1]->name, 'moov');
        $this->assertEquals(count($boxTree->children[1]->children), 2);
        $this->assertEquals($boxTree->children[1]->children[0]->name, 'mvhd');
        $this->assertEquals($boxTree->children[1]->children[1]->name, 'trak');

        $filtered = $boxTree->filterChildrenRecursive('mvhd');
        $this->assertEquals(count($filtered), 1);


    }
    public function testGetSampleDuration()
    {
        $r = new DASHIF\ISOSegmentValidatorRepresentation();
        $this->assertNull($r->getSampleDuration());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
          </container>'
        );
        $this->assertNull($r->getSampleDuration());

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
            <trex sampleDuration="424242" />
          </container>'
        );
        $this->assertEquals($r->getSampleDuration(), 424.242);

        $r->payload = DASHIF\Utility\xmlStringAsDoc(
          '<container>
            <trex sampleDuration="12000" />
            <mdhd timescale="400" />
          </container>'
        );
        $this->assertEquals($r->getSampleDuration(), 30.0);
    }
}
