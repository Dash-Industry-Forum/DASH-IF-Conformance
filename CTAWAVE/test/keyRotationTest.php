<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../module.php';
require_once __DIR__ . '/../../Utils/ValidatorInterface.php';
require_once __DIR__ . '/../../Utils/moduleLogger.php';

enum KeyRotationTestCases
{
    case INVALID;
    case NoKeyChangesSingleSeig;
    case NoKeyChangesMultipleSeig;
    case KeyChangedFailSgbpAssumption;
    case KeyChangedSgbpNoMap;
    case KeyChangedFailPsshAssumption1;
    case KeyChangedPsshCountIdentical;
    case KeyChangedPsshCountMultiple;
    case KeyChangedPsshValidRepeat;
    case KeyChangedPsshInvalidRepeat;
}

class KeyRotationMockRepresentation extends DASHIF\RepresentationInterface
{
    private $case;
    public function __construct($testCase)
    {
        $this->case = $testCase;
    }

    public function getSeigDescriptionGroups() : array|null{
      $res = array();
      switch ($this->case){
        case KeyRotationTestCases::NoKeyChangesSingleSeig:
          $res[] = $this->createSeig('A');
          break;
        case KeyRotationTestCases::NoKeyChangesMultipleSeig:
          $res[] = $this->createSeig('A');
          $res[] = $this->createSeig('A');
          break;
        case KeyRotationTestCases::KeyChangedFailSgbpAssumption:
        case KeyRotationTestCases::KeyChangedSgbpNoMap:
        case KeyRotationTestCases::KeyChangedFailPsshAssumption1:
        case KeyRotationTestCases::KeyChangedPsshCountIdentical:
        case KeyRotationTestCases::KeyChangedPsshCountMultiple:
        case KeyRotationTestCases::KeyChangedPsshValidRepeat:
        case KeyRotationTestCases::KeyChangedPsshInvalidRepeat:
          $res[] = $this->createSeig('A');
          $res[] = $this->createSeig('B');
          break;
      }

      return $res;
    }

    public function getSampleGroups(): array|null {
      $res = array();
      switch ($this->case){
        case KeyRotationTestCases::KeyChangedSgbpNoMap:
          $res[] = $this->createSgbp([5],[1]);
          $res[] = $this->createSgbp([5],[0]);
          break;
        case KeyRotationTestCases::KeyChangedFailPsshAssumption1:
        case KeyRotationTestCases::KeyChangedPsshCountIdentical:
        case KeyRotationTestCases::KeyChangedPsshCountMultiple:
        case KeyRotationTestCases::KeyChangedPsshValidRepeat:
        case KeyRotationTestCases::KeyChangedPsshInvalidRepeat:
          $res[] = $this->createSgbp([5],[1]);
          $res[] = $this->createSgbp([5],[1]);
          break;
      }
      return $res;
    }

    public function getPsshBoxes(): array|null {
      $res = array();
      switch($this->case){
        case KeyRotationTestCases::KeyChangedPsshCountIdentical:
          $res[] = $this->createPssh('systemA');
          $res[] = $this->createPssh('systemA');
          break;
        case KeyRotationTestCases::KeyChangedPsshCountMultiple:
          $res[] = $this->createPssh('systemA');
          $res[] = $this->createPssh('systemB');
          $res[] = $this->createPssh('systemA');
          $res[] = $this->createPssh('systemB');
          break;
        case KeyRotationTestCases::KeyChangedPsshValidRepeat:
          $res[] = $this->createPssh('systemA');
          $res[] = $this->createPssh('systemB');
          $res[] = $this->createPssh('systemA');
          $res[] = $this->createPssh('systemB');
          $res[] = $this->createPssh('systemB');
          $res[] = $this->createPssh('systemA');
          break;
        case KeyRotationTestCases::KeyChangedPsshInvalidRepeat:
          $res[] = $this->createPssh('systemA');
          $res[] = $this->createPssh('systemB');
          $res[] = $this->createPssh('systemA');
          $res[] = $this->createPssh('systemB');
          $res[] = $this->createPssh('systemB');
          $res[] = $this->createPssh('systemC');
          break;
      }
      return $res;
    }

    function createSeig($key){
      $seig = new DASHIF\Boxes\SeigDescription();
      $seig->kid = $key;
      return $seig;
    }

    function createSgbp($samples, $mapping){ 
      $res = new DASHIF\Boxes\SampleGroup();
      $res->sampleCounts = $samples;
      $res->groupDescriptionIndices = $mapping;
      return $res;
    }

    function createPssh($systemid){
      $res = new DASHIF\Boxes\ProtectionSystem($systemid);
      $res->systemId = $systemid;
      return $res;
    }

}

class KeyRotationMockValidator extends DASHIF\ValidatorInterface
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
        return new KeyRotationMockRepresentation($r);
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

class KeyRotationTestModule extends DASHIF\ModuleCTAWave
{
    public function __construct()
    {
        parent::__construct();
    }
}

final class WaveKeyRotationTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['validatorWrapper'] = new DASHIF\ValidatorWrapper(false);
        $GLOBALS['validatorWrapper']->addValidator(new KeyRotationMockValidator());
        $GLOBALS['logger'] = new DASHIF\ModuleLogger();
        $this->module = new KeyRotationTestModule();
    }


    public function testInvalid(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,KeyRotationTestCases::INVALID),
            $this->module,
            'keyRotation'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testNoKeyChangesSingleSeig(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,KeyRotationTestCases::NoKeyChangesSingleSeig),
            $this->module,
            'keyRotation'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testNoKeyChangesMultipleSeig(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,KeyRotationTestCases::NoKeyChangesMultipleSeig),
            $this->module,
            'keyRotation'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testKeyChangedFailSgbpAssumption(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,KeyRotationTestCases::KeyChangedFailSgbpAssumption),
            $this->module,
            'keyRotation'
        );
        $this->assertEquals('WARN', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testKeyChangedSgbpNoMap(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,KeyRotationTestCases::KeyChangedSgbpNoMap),
            $this->module,
            'keyRotation'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
    
    public function testKeyChangedFailPsshAssumption1(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,KeyRotationTestCases::KeyChangedFailPsshAssumption1),
            $this->module,
            'keyRotation'
        );
        $this->assertEquals('WARN', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testKeyChangedPsshCountIdentical(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,KeyRotationTestCases::KeyChangedPsshCountIdentical),
            $this->module,
            'keyRotation'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testKeyChangedPsshCountMultiple(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,KeyRotationTestCases::KeyChangedPsshCountMultiple),
            $this->module,
            'keyRotation'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testKeyChangedPsshValidRepeat(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
          array(0,0,KeyRotationTestCases::KeyChangedPsshValidRepeat),
            $this->module,
            'keyRotation'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testKeyChangedPsshInvalidRepeat(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
          array(0,0,KeyRotationTestCases::KeyChangedPsshInvalidRepeat),
            $this->module,
            'keyRotation'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
}
