<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../module.php';
require_once __DIR__ . '/../../Utils/ValidatorInterface.php';
require_once __DIR__ . '/../../Utils/moduleLogger.php';

enum AddressableObjectTestCases
{
    case INVALID;
    case SingleValid;
    case SingleNoSidx;
    case SingleMultipleSidx;
    case SingleWrongOrder;
    case MultipleValid;
    case MultipleTooManySidx;
    case MultipleWrongOrder;
    case MultiplePossibleValid;
    case MultiplePossibleValidButWrongOrder;
}

class AddressableObjectsMockRepresentation extends DASHIF\RepresentationInterface
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
            case AddressableObjectTestCases::SingleValid:
                $boxNames[] = 'moov';
                $boxNames[] = 'sidx';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                break;
            case AddressableObjectTestCases::SingleNoSidx:
                $boxNames[] = 'moov';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                break;
            case AddressableObjectTestCases::SingleMultipleSidx:
                $boxNames[] = 'moov';
                $boxNames[] = 'sidx';
                $boxNames[] = 'sidx';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                break;
            case AddressableObjectTestCases::SingleWrongOrder:
                $boxNames[] = 'moov';
                $boxNames[] = 'moof';
                $boxNames[] = 'sidx';
                $boxNames[] = 'mdat';
                break;
            case AddressableObjectTestCases::MultipleValid:
                $boxNames[] = 'moov';
                $boxNames[] = 'sidx';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                $boxNames[] = 'sidx';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                break;
            case AddressableObjectTestCases::MultipleTooManySidx:
                $boxNames[] = 'moov';
                $boxNames[] = 'sidx';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                $boxNames[] = 'sidx';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                $boxNames[] = 'sidx';
                break;
            case AddressableObjectTestCases::MultipleWrongOrder:
                $boxNames[] = 'moov';
                $boxNames[] = 'sidx';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                $boxNames[] = 'moof';
                $boxNames[] = 'sidx';
                $boxNames[] = 'mdat';
                break;
            case AddressableObjectTestCases::MultiplePossibleValid:
                $boxNames[] = 'moov';
                $boxNames[] = 'sidx';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                break;
            case AddressableObjectTestCases::MultiplePossibleValidButWrongOrder:
                $boxNames[] = 'moov';
                $boxNames[] = 'sidx';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                $boxNames[] = 'moof';
                $boxNames[] = 'mdat';
                $boxNames[] = 'moof';
                $boxNames[] = 'sidx';
                $boxNames[] = 'mdat';
                break;
        }
        return $boxNames;
    }
}

class AddressableObjectsMockValidator extends DASHIF\ValidatorInterface
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
        return new AddressableObjectsMockRepresentation($r);
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

class AddressableObjectsTestModule extends DASHIF\ModuleWaveHLSInterop
{
    public function __construct()
    {
        parent::__construct();
    }
}

final class WaveCmafAddressableObjectTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['validatorWrapper'] = new DASHIF\ValidatorWrapper(false);
        $GLOBALS['validatorWrapper']->addValidator(new AddressableObjectsMockValidator());
        $GLOBALS['logger'] = new DASHIF\ModuleLogger();
        $this->module = new AddressableObjectsTestModule();
    }


    public function testNoBoxes(): void
    {
        $this->assertNull($GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,AddressableObjectTestCases::INVALID),
            $this->module,
            'addressableMediaObject',
            [DASHIF\ValidatorFlags::PreservesOrder]
        ));
    }

    public function testSingleValid(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,AddressableObjectTestCases::SingleValid),
            $this->module,
            'addressableMediaObject'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testSingleNoSidx(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,AddressableObjectTestCases::SingleNoSidx),
            $this->module,
            'addressableMediaObject'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testSingleMultipleSidx(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,AddressableObjectTestCases::SingleMultipleSidx),
            $this->module,
            'addressableMediaObject'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testSingleWrongOrder(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,AddressableObjectTestCases::SingleWrongOrder),
            $this->module,
            'addressableMediaObject'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testMultipleValid(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,AddressableObjectTestCases::MultipleValid),
            $this->module,
            'addressableMediaObject'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testMultipleTooManySidx(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,AddressableObjectTestCases::MultipleTooManySidx),
            $this->module,
            'addressableMediaObject'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testMultipleWrongOrder(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,AddressableObjectTestCases::MultipleWrongOrder),
            $this->module,
            'addressableMediaObject'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testMultiplePossibleValid(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,AddressableObjectTestCases::MultiplePossibleValid),
            $this->module,
            'addressableMediaObject'
        );
        $this->assertEquals('WARN', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testMultiplePossibleValidButWrongOrder(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,AddressableObjectTestCases::MultiplePossibleValidButWrongOrder),
            $this->module,
            'addressableMediaObject'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
}
