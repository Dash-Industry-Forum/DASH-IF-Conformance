<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../module.php';
require_once __DIR__ . '/../../Utils/ValidatorInterface.php';
require_once __DIR__ . '/../../Utils/moduleLogger.php';
require_once __DIR__ . '/../../Utils/boxes/boxes.php';

enum EncryptionSchemeTestCases
{
    case INVALID;
    case NotProtected;
    case Valid;
    case NonCBCS;
    case Non16Byte;
    case NoIV;
    case ValidVideoPattern;
    case ValidVideoPatternMultiple;
    case InvalidVideoPatternZeroCrypt;
    case InvalidVideoPattern;
    case ValidAudioPattern;
    case InvalidAudioPattern;
    case ValidWithSAIO;
}

class EncryptionSchemeMockRepresentation extends DASHIF\RepresentationInterface
{
    private $case;
    public function __construct($testCase)
    {
        $this->case = $testCase;
    }

    public function getSampleAuxiliaryInformation() : DASHIF\Boxes\SampleAuxiliaryInformation|null {
      $res = null;
        switch ($this->case) {
          case EncryptionSchemeTestCases::ValidWithSAIO:
            $res = new DASHIF\Boxes\SampleAuxiliaryInformation();
            break;
        }
        return $res;

    }

    public function getHandlerType() {
      $handler = '';
        switch ($this->case) {
        case EncryptionSchemeTestCases::ValidVideoPattern:
        case EncryptionSchemeTestCases::ValidVideoPatternMultiple:
        case EncryptionSchemeTestCases::InvalidVideoPatternZeroCrypt:
        case EncryptionSchemeTestCases::InvalidVideoPattern:
          $handler = 'vide';
          break;
        case EncryptionSchemeTestCases::ValidAudioPattern:
        case EncryptionSchemeTestCases::InvalidAudioPattern:
          $handler = 'soun';
          break;
        }
      return $handler;


    }

    public function getProtectionScheme() : DASHIF\Boxes\ProtectionScheme|null
    {
        $res = null;
        switch ($this->case) {
          case EncryptionSchemeTestCases::NotProtected:
            $res = new DASHIF\Boxes\ProtectionScheme();
            break;
          case EncryptionSchemeTestCases::Valid:
          case EncryptionSchemeTestCases::ValidWithSAIO:
            $res = $this->getValidEncryption();
            break;
          case EncryptionSchemeTestCases::NonCBCS:
            $res = $this->getValidEncryption();
            $res->scheme->schemeType = 'cenc';
            break;
          case EncryptionSchemeTestCases::Non16Byte:
            $res = $this->getValidEncryption();
            $res->encryption->ivSize = 8;
            break;
          case EncryptionSchemeTestCases::NoIV:
            $res = $this->getValidEncryption();
            $res->encryption->iv = '';
            break;
          case EncryptionSchemeTestCases::ValidVideoPattern:
            $res = $this->getValidEncryption();
            $res->encryption->cryptByteBlock = 1;
            $res->encryption->skipByteBlock = 9;
            break;
          case EncryptionSchemeTestCases::ValidVideoPatternMultiple:
            $res = $this->getValidEncryption();
            $res->encryption->cryptByteBlock = 3;
            $res->encryption->skipByteBlock = 27;
            break;
          case EncryptionSchemeTestCases::InvalidVideoPatternZeroCrypt:
            $res = $this->getValidEncryption();
            $res->encryption->cryptByteBlock = 0;
            $res->encryption->skipByteBlock = 9;
            break;
          case EncryptionSchemeTestCases::InvalidVideoPattern:
            $res = $this->getValidEncryption();
            $res->encryption->cryptByteBlock = 1;
            $res->encryption->skipByteBlock = 6;
            break;
          case EncryptionSchemeTestCases::ValidAudioPattern:
            $res = $this->getValidEncryption();
            $res->encryption->cryptByteBlock = 1;
            break;
          case EncryptionSchemeTestCases::InvalidAudioPattern:
            $res = $this->getValidEncryption();
            $res->encryption->cryptByteBlock = 0;
            break;
        }
        return $res;
    }

    function getValidEncryption() {
            $res = new DASHIF\Boxes\ProtectionScheme();
            $res->encryption->isEncrypted = true;
            $res->encryption->ivSize = 16;
            $res->encryption->iv = '0x0A610676CB88F302D10AC8BC66E039ED';
            $res->scheme->schemeType = 'cbcs';
            return $res;
    }
}

class EncryptionSchemeMockValidator extends DASHIF\ValidatorInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "Mock";
        $this->enabled = true;
        $this->flags[] = DASHIF\ValidatorFlags::PreservesOrder;
    }

    public function getRepresentation($p, $a, $r)
    {
      //We abuse $r to denote a testcase;
        return new EncryptionSchemeMockRepresentation($r);
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

class EncryptionSchemeTestModule extends DASHIF\ModuleCTAWave
{
    public function __construct()
    {
        parent::__construct();
    }
}

final class WaveEncryptionSchemeTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['validatorWrapper'] = new DASHIF\ValidatorWrapper(false);
        $GLOBALS['validatorWrapper']->addValidator(new EncryptionSchemeMockValidator());
        $GLOBALS['logger'] = new DASHIF\ModuleLogger();
        $this->module = new EncryptionSchemeTestModule();
    }


    public function testInvalid(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,EncryptionSchemeTestCases::INVALID),
            $this->module,
            'encryptionScheme'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testNotProtected(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,EncryptionSchemeTestCases::NotProtected),
            $this->module,
            'encryptionScheme'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testValid(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,EncryptionSchemeTestCases::Valid),
            $this->module,
            'encryptionScheme'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testValidWithSAIO(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,EncryptionSchemeTestCases::ValidWithSAIO),
            $this->module,
            'encryptionScheme'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testNonCBCS(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,EncryptionSchemeTestCases::NonCBCS),
            $this->module,
            'encryptionScheme'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testNon16Byte(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,EncryptionSchemeTestCases::Non16Byte),
            $this->module,
            'encryptionScheme'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testNoIV(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,EncryptionSchemeTestCases::NoIV),
            $this->module,
            'encryptionScheme'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testValidVideoPattern(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,EncryptionSchemeTestCases::ValidVideoPattern),
            $this->module,
            'encryptionScheme'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testValidVideoPatternMultiple(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,EncryptionSchemeTestCases::ValidVideoPatternMultiple),
            $this->module,
            'encryptionScheme'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testInvalidVideoPatternZeroCrypt(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,EncryptionSchemeTestCases::InvalidVideoPatternZeroCrypt),
            $this->module,
            'encryptionScheme'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testInvalidVideoPattern(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,EncryptionSchemeTestCases::InvalidVideoPattern),
            $this->module,
            'encryptionScheme'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testValidAudioPattern(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,EncryptionSchemeTestCases::ValidAudioPattern),
            $this->module,
            'encryptionScheme'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testInvalidAudioPattern(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,EncryptionSchemeTestCases::InvalidAudioPattern),
            $this->module,
            'encryptionScheme'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }

}
