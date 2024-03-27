<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../module.php';
require_once __DIR__ . '/../../Utils/ValidatorInterface.php';
require_once __DIR__ . '/../../Utils/moduleLogger.php';

enum TextComponentTestCases
{
    case INVALID;
    case ISMC1Valid;
    case ISMC1InvalidNamespace;
    case ISMC1InvalidSchemaLocation;
    case ISMC1InvalidMimeType;
    case ISMC1InvalidMimeTypeTTMLOnly;
    case ISMC1InvalidMimeTypeCodec11;
    case ISMC11Valid;
    case ISMC11InvalidNamespace;
    case ISMC11InvalidSchemaLocation;
    case ISMC11InvalidMimeType;
    case ISMC11InvalidMimeTypeTTMLOnly;
    case ISMC11InvalidMimeTypeCodec1;
    case WebVTTValid;
    case WebVTTInvalidCodingName;
}

class TextComponentsMockRepresentation extends DASHIF\RepresentationInterface
{
    private $case;
    public function __construct($testCase)
    {
        $this->case = $testCase;
    }

    public function getSampleDescription(): DASHIF\Boxes\SampleDescription|null
    {
        $sdesc = null;
        switch ($this->case) {
          // ISMC1
            case TextComponentTestCases::ISMC1Valid:
                $sdesc = $this->validISMC1();
                break;
            case TextComponentTestCases::ISMC1InvalidNamespace:
                $sdesc = $this->validISMC1();
                $sdesc->namespace = '';
                break;
            case TextComponentTestCases::ISMC1InvalidSchemaLocation:
                $sdesc = $this->validISMC1();
                $sdesc->schemaLocation = '';
                break;
            case TextComponentTestCases::ISMC1InvalidMimeType:
                $sdesc = $this->validISMC1();
                $sdesc->auxiliaryMimeTypes = '';
                break;
            case TextComponentTestCases::ISMC1InvalidMimeTypeTTMLOnly:
                $sdesc = $this->validISMC1();
                $sdesc->auxiliaryMimeTypes = 'application/ttml+xml';
                break;
            case TextComponentTestCases::ISMC1InvalidMimeTypeCodec11:
                $sdesc = $this->validISMC1();
                $sdesc->auxiliaryMimeTypes = 'application/ttml+xml;codecs=im2t';
                break;
          // ISMC1.1
            case TextComponentTestCases::ISMC11Valid:
                $sdesc = $this->validISMC11();
                break;
            case TextComponentTestCases::ISMC11InvalidNamespace:
                $sdesc = $this->validISMC11();
                $sdesc->namespace = '';
                break;
            case TextComponentTestCases::ISMC11InvalidSchemaLocation:
                $sdesc = $this->validISMC11();
                $sdesc->schemaLocation = '';
                break;
            case TextComponentTestCases::ISMC11InvalidMimeType:
                $sdesc = $this->validISMC11();
                $sdesc->auxiliaryMimeTypes = '';
                break;
            case TextComponentTestCases::ISMC11InvalidMimeTypeTTMLOnly:
                $sdesc = $this->validISMC11();
                $sdesc->auxiliaryMimeTypes = 'application/ttml+xml';
                break;
            case TextComponentTestCases::ISMC11InvalidMimeTypeCodec1:
                $sdesc = $this->validISMC11();
                $sdesc->auxiliaryMimeTypes = 'application/ttml+xml;codecs=im1t';
                break;
          // WebVTT
            case TextComponentTestCases::WebVTTValid:
                $sdesc = $this->validWebVTT();
                break;
            case TextComponentTestCases::WebVTTInvalidCodingName:
                $sdesc = $this->validWebVTT();
                $sdesc->codingname = '';
                break;
        }
        return $sdesc;
    }

    private function validISMC1()
    {
        $sdesc = new DASHIF\Boxes\SampleDescription();
        $sdesc->type = DASHIF\Boxes\DescriptionType::Subtitle;
        $sdesc->codingname = 'stpp';
        $sdesc->namespace = 'http://www.w3.org/ns/ttml';
        $sdesc->schemaLocation = 'http://www.w3.org/ns/ttml/profile/imsc1/text';
        $sdesc->auxiliaryMimeTypes = 'application/ttml+xml;codecs=im1t';
        return $sdesc;
    }

    private function validISMC11()
    {
        $sdesc = new DASHIF\Boxes\SampleDescription();
        $sdesc->type = DASHIF\Boxes\DescriptionType::Subtitle;
        $sdesc->codingname = 'stpp';
        $sdesc->namespace = 'http://www.w3.org/ns/ttml';
        $sdesc->schemaLocation = 'http://www.w3.org/ns/ttml/profile/imsc1.1/text';
        $sdesc->auxiliaryMimeTypes = 'application/ttml+xml;codecs=im2t';
        return $sdesc;
    }

    private function validWebVTT()
    {
        $sdesc = new DASHIF\Boxes\SampleDescription();
        $sdesc->type = DASHIF\Boxes\DescriptionType::Text;
        $sdesc->codingname = 'wvtt';
        return $sdesc;
    }
}

class TextComponentsMockValidator extends DASHIF\ValidatorInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "Mock";
        $this->enabled = true;
    }

    public function getRepresentation($p, $a, $r)
    {
      //We abuse $r to denote a testcase;
        return new TextComponentsMockRepresentation($r);
    }

    public function enableFeature($featureName)
    {
        throw new Exception("EnableFeature");
    }
    public function run($p, $a, $r)
    {
        throw new Exception("Run");
    }
}

class TextComponentsTestModule extends DASHIF\ModuleWaveHLSInterop
{
    public function __construct()
    {
        parent::__construct();
    }
}

final class WaveCmafTextComponentTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['validatorWrapper'] = new DASHIF\ValidatorWrapper(false);
        $GLOBALS['validatorWrapper']->addValidator(new TextComponentsMockValidator());
        $GLOBALS['logger'] = new DASHIF\ModuleLogger();
        $this->module = new TextComponentsTestModule();
    }


    public function testNonISMC(): void
    {
        $this->assertNull($GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TextComponentTestCases::INVALID),
            $this->module,
            'textComponentConstraints'
        ));
    }

  //ISMC1
    public function testISMC1Valid(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TextComponentTestCases::ISMC1Valid),
            $this->module,
            'textComponentConstraints'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testISMC1InvalidNamespace(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TextComponentTestCases::ISMC1InvalidNamespace),
            $this->module,
            'textComponentConstraints'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testISMC1InvalidSchemaLocation(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TextComponentTestCases::ISMC1InvalidSchemaLocation),
            $this->module,
            'textComponentConstraints'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testISMC1InvalidMimeType(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TextComponentTestCases::ISMC1InvalidMimeType),
            $this->module,
            'textComponentConstraints'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testISMC1InvalidMimeTypeTTMLOnly(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TextComponentTestCases::ISMC1InvalidMimeTypeTTMLOnly),
            $this->module,
            'textComponentConstraints'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testISMC1InvalidMimeTypeCodec11(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TextComponentTestCases::ISMC1InvalidMimeTypeCodec11),
            $this->module,
            'textComponentConstraints'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }

  //ISMC1.1
    public function testISMC11Valid(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TextComponentTestCases::ISMC11Valid),
            $this->module,
            'textComponentConstraints'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testISMC11InvalidNamespace(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TextComponentTestCases::ISMC11InvalidNamespace),
            $this->module,
            'textComponentConstraints'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testISMC11InvalidSchemaLocation(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TextComponentTestCases::ISMC11InvalidSchemaLocation),
            $this->module,
            'textComponentConstraints'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testISMC11InvalidMimeType(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TextComponentTestCases::ISMC11InvalidMimeType),
            $this->module,
            'textComponentConstraints'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testISMC11InvalidMimeTypeTTMLOnly(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TextComponentTestCases::ISMC11InvalidMimeTypeTTMLOnly),
            $this->module,
            'textComponentConstraints'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testISMC11InvalidMimeTypeCodec1(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TextComponentTestCases::ISMC11InvalidMimeTypeCodec1),
            $this->module,
            'textComponentConstraints'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }

  //WebVTT
    public function testWebVTTValid(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TextComponentTestCases::WebVTTValid),
            $this->module,
            'textComponentConstraints'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testWebVTTInvalidCodingName(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TextComponentTestCases::WebVTTInvalidCodingName),
            $this->module,
            'textComponentConstraints'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
}
