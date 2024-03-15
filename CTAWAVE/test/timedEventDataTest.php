<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../module.php';
require_once __DIR__ . '/../../Utils/ValidatorInterface.php';
require_once __DIR__ . '/../../Utils/moduleLogger.php';

enum TimedEventDataTestCases
{
    case INVALID;
    case NoEmsgBox;
    case BoxOrderNotParsed;
    case OnlyEmsgBox;
    case ExpectRepeatValid;
    case ExpectRepeatValidOutOfOrder;
    case ExpectRepeatValidOutOfOrder2;
    case ExpectRepeatValidDueToEndOfStream;
    case ExpectRepeatInvalid;
}

class TimedEventDataMockRepresentation extends DASHIF\RepresentationInterface
{
    private $case;
    public function __construct($testCase)
    {
        $this->case = $testCase;
    }

    public function getTopLevelBoxNames()
    {
        $boxNames = array();
        switch ($this->case) {
            case TimedEventDataTestCases::NoEmsgBox:
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                break;
            case TimedEventDataTestCases::BoxOrderNotParsed:
            case TimedEventDataTestCases::OnlyEmsgBox:
                $boxNames[] = 'emsg';
                break;
            case TimedEventDataTestCases::ExpectRepeatValid:
                $boxNames[] = 'moof';
                $boxNames[] = 'emsg';
                $boxNames[] = 'mdat';
                $boxNames[] = 'emsg';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                break;
            case TimedEventDataTestCases::ExpectRepeatValidOutOfOrder:
                $boxNames[] = 'moof';
                $boxNames[] = 'emsg';
                $boxNames[] = 'emsg';
                $boxNames[] = 'mdat';
                $boxNames[] = 'emsg';
                $boxNames[] = 'emsg';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                break;
            case TimedEventDataTestCases::ExpectRepeatValidOutOfOrder2:
                $boxNames[] = 'moof';
                $boxNames[] = 'emsg';
                $boxNames[] = 'mdat';
                $boxNames[] = 'emsg';
                $boxNames[] = 'emsg';
                $boxNames[] = 'emsg';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                break;
            case TimedEventDataTestCases::ExpectRepeatValidDueToEndOfStream:
                $boxNames[] = 'moof';
                $boxNames[] = 'emsg';
                $boxNames[] = 'mdat';
                break;
            case TimedEventDataTestCases::ExpectRepeatInvalid:
                $boxNames[] = 'moof';
                $boxNames[] = 'emsg';
                $boxNames[] = 'mdat';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                break;
        }
        return $boxNames;
    }

    public function getEmsgBoxes() : array|null {
      $res = array();
      switch ($this->case) {
        case TimedEventDataTestCases::OnlyEmsgBox:
        case TimedEventDataTestCases::ExpectRepeatValidDueToEndOfStream:
        case TimedEventDataTestCases::ExpectRepeatInvalid:
          $res[] = $this->getBox(0, 0, 0);
          break;
        case TimedEventDataTestCases::ExpectRepeatValid:
          $res[] = $this->getBox(0, 0, 0);
          $res[] = $this->getBox(0, 0, 0);
          break;
        case TimedEventDataTestCases::ExpectRepeatValidOutOfOrder:
        case TimedEventDataTestCases::ExpectRepeatValidOutOfOrder2:
          $res[] = $this->getBox(0, 0, 0);
          $res[] = $this->getBox(1, 0, 0);
          $res[] = $this->getBox(1, 0, 0);
          $res[] = $this->getBox(0, 0, 0);
          break;
      }
      return $res;
    }

    private function getBox($presentationTime, $timeScale, $eventDuration){
          $box = new DASHIF\Boxes\EventMessage();
          $box->presentationTime = $presentationTime;
          $box->timeScale = $timeScale;
          $box->eventDuration = $eventDuration;
          return $box;

    }
}

class TimedEventDataMockValidator extends DASHIF\ValidatorInterface
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
        return new TimedEventDataMockRepresentation($r);
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

class TimedEventDataTestModule extends DASHIF\ModuleCTAWave
{
    public function __construct()
    {
        parent::__construct();
    }
}

final class WaveCmafTimedEventDataTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['validatorWrapper'] = new DASHIF\ValidatorWrapper(false);
        $GLOBALS['validatorWrapper']->addValidator(new TimedEventDataMockValidator());
        $GLOBALS['logger'] = new DASHIF\ModuleLogger();
        $this->module = new TimedEventDataTestModule();
    }


    public function testNoBoxes(): void
    {
        $this->assertNull($GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TimedEventDataTestCases::INVALID),
            $this->module,
            'timedEventData',
            [DASHIF\ValidatorFlags::PreservesOrder]
        ));
    }
    public function testBoxOrderNoEmsg(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
          array(0,0,TimedEventDataTestCases::NoEmsgBox),
            $this->module,
            'timedEventData'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testBoxOrderNotParsed(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TimedEventDataTestCases::BoxOrderNotParsed),
            $this->module,
            'timedEventData'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testOnlyEmsgBox(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TimedEventDataTestCases::OnlyEmsgBox),
            $this->module,
            'timedEventData'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testExpectRepeatValid(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TimedEventDataTestCases::ExpectRepeatValid),
            $this->module,
            'timedEventData'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testExpectRepeatValidOutOfOrder(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TimedEventDataTestCases::ExpectRepeatValidOutOfOrder),
            $this->module,
            'timedEventData'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testExpectRepeatValidOutOfOrder2(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TimedEventDataTestCases::ExpectRepeatValidOutOfOrder2),
            $this->module,
            'timedEventData'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testExpectRepeatValidDueToEndOfStream(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TimedEventDataTestCases::ExpectRepeatValidDueToEndOfStream),
            $this->module,
            'timedEventData'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testExpectRepeatInvalid(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,TimedEventDataTestCases::ExpectRepeatInvalid),
            $this->module,
            'timedEventData'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }

}
