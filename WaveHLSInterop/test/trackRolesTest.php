<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../module.php';
require_once __DIR__ . '/../../Utils/MPDHandler.php';
require_once __DIR__ . '/../../Utils/ValidatorInterface.php';
require_once __DIR__ . '/../../Utils/moduleLogger.php';
require_once __DIR__ . '/../../Utils/boxes/boxes.php';

enum TrackRoleTestCases
{
    case INVALID;
    case MainRoleNoMPD;
    case MainRole;
    case MainRoleOnlyMPD;
    case MainRoleNonDash;
}

class TrackRoleMockRepresentation extends DASHIF\RepresentationInterface
{
    private $case;
    public function __construct($testCase)
    {
        $this->case = $testCase;
        $this->periodIndex = 0;
        $this->adaptationIndex = 0;
    }

    public function getKindBoxes(): array|null
    {
        $res = array();
        switch ($this->case) {
          case TrackRoleTestCases::MainRoleNoMPD:
            $res[] = $this->constructRole("urn:mpeg:dash:role:2011", 'main');
            break;
          case TrackRoleTestCases::MainRole:
            $res[] = $this->constructRole("urn:mpeg:dash:role:2011", 'main');
            break;
          case TrackRoleTestCases::MainRoleOnlyMPD:
            $res[] = $this->constructRole("urn:mpeg:dash:role:2011", 'alternate');
            break;
          case TrackRoleTestCases::MainRoleNonDash:
            $res[] = $this->constructRole("some:other:namesapce", 'main');
            break;
          default: 
            $res = null;
            break;
        }
        return $res;

    }

    function constructRole($scheme, $value){
      $mainBox = new DASHIF\Boxes\KINDBox();
      $mainBox->schemeURI = $scheme;
      $mainBox->value = $value;
      return $mainBox;
    }
}

class TrackRoleMockValidator extends DASHIF\ValidatorInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "Mock";
        $this->enabled = true;
        $this->flags[] = DASHIF\ValidatorFlags::ExtractKind;
    }

    public function getRepresentation($p, $a, $r)
    {
      //We abuse $r to denote a testcase;
        return new TrackRoleMockRepresentation($r);
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

class TrackRoleTestModule extends DASHIF\ModuleWaveHLSInterop
{
    public function __construct()
    {
        parent::__construct();
    }
}

class TrackRoleMockMPD extends DASHIF\MPDHandler {
  public function __construct(){
    parent::__construct("");
    $this->case = TrackRoleTestCases::INVALID;
  }

  public function setCase($case){
    $this->case = $case;
  }

  public function getRoles($period, $adaptation){
    $res = array();
    switch ($this->case) {
      case TrackRoleTestCases::MainRoleNoMPD:
        break;
      case TrackRoleTestCases::MainRole:
        $res[] = $this->constructRole('urn:mpeg:dash:role:2011', 'main');
        break;
      case TrackRoleTestCases::MainRoleOnlyMPD:
        $res[] = $this->constructRole('urn:mpeg:dash:role:2011', 'main');
        break;
      case TrackRoleTestCases::MainRoleNonDash:
        $res[] = $this->constructRole('some:other:namespace', 'main');
        break;
      default: 
        break;
    }
    return $res;
  }

  function constructRole($scheme, $value){
    return array(
      'schemeIdUri' => $scheme,
      'value' => $value
    );

  }

}

final class WaveTrackRoleTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['validatorWrapper'] = new DASHIF\ValidatorWrapper(false);
        $GLOBALS['validatorWrapper']->addValidator(new TrackRoleMockValidator());
        $GLOBALS['logger'] = new DASHIF\ModuleLogger();
        $GLOBALS['mpdHandler'] = new TrackRoleMockMPD();
        $this->module = new TrackRoleTestModule();
    }


    public function testInvalid(): void
    {
        $thisCase = TrackRoleTestCases::INVALID;
        $GLOBALS['mpdHandler']->setCase($thisCase);
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,$thisCase),
            $this->module,
            'trackRoles'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testMainRoleNoMPD(): void
    {
        $thisCase = TrackRoleTestCases::MainRoleNoMPD;
        $GLOBALS['mpdHandler']->setCase($thisCase);
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,$thisCase),
            $this->module,
            'trackRoles'
        );
        $this->assertEquals('WARN', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testMainRole(): void
    {
        $thisCase = TrackRoleTestCases::MainRole;
        $GLOBALS['mpdHandler']->setCase($thisCase);
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,$thisCase),
            $this->module,
            'trackRoles'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testMainRoleOnlyMPD(): void
    {
        $thisCase = TrackRoleTestCases::MainRoleNoMPD;
        $GLOBALS['mpdHandler']->setCase($thisCase);
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,$thisCase),
            $this->module,
            'trackRoles'
        );
        $this->assertEquals('WARN', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testMainRoleNonDash(): void
    {
        $thisCase = TrackRoleTestCases::MainRoleNonDash;
        $GLOBALS['mpdHandler']->setCase($thisCase);
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,$thisCase),
            $this->module,
            'trackRoles'
        );
        $this->assertEquals('WARN', $GLOBALS['logger']->asArray()['verdict']);
    }
}
