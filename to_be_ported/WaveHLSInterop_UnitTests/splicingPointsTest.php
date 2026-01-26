<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../module.php';
require_once __DIR__ . '/../../Utils/ValidatorInterface.php';
require_once __DIR__ . '/../../Utils/moduleLogger.php';

enum SplicingPointTestCases
{
    case INVALID;
    case NoSampleDuration;
    case NoMoofBox;
    case SingleMoofSingleTraf;
    case SingleMoofMultipleTraf;
    case ValidFragments;
    case InvalidFragments;
    case LastFragmentInvalid;
}

class SplicingPointMockRepresentation extends DASHIF\RepresentationInterface
{
    private $case;
    public function __construct($testCase)
    {
        $this->case = $testCase;
    }

    public function getBoxNameTree() : DASHIF\Boxes\NameOnlyNode|null
    {
        $result = new DASHIF\Boxes\NameOnlyNode('');
        switch ($this->case) {
          case SplicingPointTestCases::INVALID:
            return null;
            break;
          case SplicingPointTestCases::NoMoofBox:
          case SplicingPointTestCases::NoSampleDuration:
            break;
          case SplicingPointTestCases::SingleMoofSingleTraf:
          case SplicingPointTestCases::ValidFragments:
          case SplicingPointTestCases::InvalidFragments:
          case SplicingPointTestCases::LastFragmentInvalid:
            $moofBox = new DASHIF\Boxes\NameOnlyNode('moof');
            $moofBox->children[] = new DASHIF\Boxes\NameOnlyNode('traf');
            $result->children[] = $moofBox;
            break;
          case SplicingPointTestCases::SingleMoofMultipleTraf:
            $moofBox = new DASHIF\Boxes\NameOnlyNode('moof');
            $moofBox->children[] = new DASHIF\Boxes\NameOnlyNode('traf');
            $moofBox->children[] = new DASHIF\Boxes\NameOnlyNode('traf');
            $result->children[] = $moofBox;
            break;

        }
        return $result;
      
    }

    public function getSampleDuration() : float|null{
      if ($this->case == SplicingPointTestCases::NoSampleDuration){
        return null;
      }
      return 0.500;
    }

    public function getFragmentDurations() : array|null {
      $result = array();
      switch ($this->case){
        case SplicingPointTestCases::ValidFragments:
          $result[] = 5.00;
          $result[] = 4.75;
          $result[] = 5.20;
          break;
        case SplicingPointTestCases::InvalidFragments:
          $result[] = 5.00;
          $result[] = 4.35;
          $result[] = 5.20;
          break;
        case SplicingPointTestCases::LastFragmentInvalid:
          $result[] = 5.00;
          $result[] = 4.75;
          $result[] = 2.00;
      }
      return $result;

    }


}

class SplicingPointMockValidator extends DASHIF\ValidatorInterface
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
        return new SplicingPointMockRepresentation($r);
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

class SplicingPointTestModule extends DASHIF\ModuleWaveHLSInterop
{
    public function __construct()
    {
        parent::__construct();
    }
}

final class WaveSplicingPointTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['validatorWrapper'] = new DASHIF\ValidatorWrapper(false);
        $GLOBALS['validatorWrapper']->addValidator(new SplicingPointMockValidator());
        $GLOBALS['logger'] = new DASHIF\ModuleLogger();
        $this->module = new SplicingPointTestModule();
    }


    public function testInvalid(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,SplicingPointTestCases::INVALID),
            $this->module,
            'splicingPoints'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testNoSampleDuration(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,SplicingPointTestCases::NoSampleDuration),
            $this->module,
            'splicingPoints'
        );
        $this->assertEquals('WARN', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testNoMoofBox(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,SplicingPointTestCases::NoMoofBox),
            $this->module,
            'splicingPoints'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testSingleMoofSingleTraf(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,SplicingPointTestCases::SingleMoofSingleTraf),
            $this->module,
            'splicingPoints'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testSingleMoofMultipleTraf(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,SplicingPointTestCases::SingleMoofMultipleTraf),
            $this->module,
            'splicingPoints'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testValidFragments(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,SplicingPointTestCases::ValidFragments),
            $this->module,
            'splicingPoints'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testInvalidFragments(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,SplicingPointTestCases::InvalidFragments),
            $this->module,
            'splicingPoints'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testLastFragmentInvalid(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,SplicingPointTestCases::LastFragmentInvalid),
            $this->module,
            'splicingPoints'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
}
