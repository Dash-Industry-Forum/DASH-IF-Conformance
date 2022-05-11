<?php

declare(strict_types=1);

namespace DASHIF\Tests;

require_once('./Utils/moduleLogger.php');
require_once('./tests/testCaseHelper.php');

$modules = [];

use PHPUnit\Framework\TestCase;


final class ModuleLoggerTest extends TestCase
{
    public function testLoggerExists(): void
    {
        global $logger;
        $this->assertTrue(isset($logger));
    }

    public function testEmptyArray(): void
    {
        global $logger;
        $res = $logger->asArray();
        $this->assertIsArray($res);
        $this->assertContains('verdict', array_keys($res));
        $this->assertContains('source', array_keys($res));
        $this->assertContains('entries', array_keys($res));
        $this->assertContains('enabled_modules', array_keys($res));
    }
     /**
     * @dataProvider caseProvider
     */
    public function testDataFromProvider(array $arr): void
    {
        $this->assertTrue($arr['expect']);
    }

    public function caseProvider(): TestCaseHelper
    {
        return new TestCaseHelper('./tests/testCases.json', 'Utility');
    }
}
