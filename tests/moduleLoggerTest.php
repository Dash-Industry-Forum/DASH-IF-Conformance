<?php declare(strict_types=1);

require_once('./Utils/moduleLogger.php');

$modules = [];

use PHPUnit\Framework\TestCase;

final class moduleLoggerTest extends TestCase
{
  public function testLoggerExists(): void {
    global $logger;
    $this->assertTrue(isset($logger));
  }

  public function testEmptyArray(): void {
    global $logger;
    $res = $logger->asArray();
    $this->assertIsArray($res);
    $this->assertContains('verdict', array_keys($res));
    $this->assertContains('source', array_keys($res));
    $this->assertContains('entries', array_keys($res));
    $this->assertContains('enabled_modules', array_keys($res));
  }
}
