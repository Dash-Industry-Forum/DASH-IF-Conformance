<?php

namespace Tests\Unit\WaveHLSInterop;

use Tests\TestCase;
//
use App\Services\ModuleReporter;
use App\Services\Manifest\Representation;
//
use App\Modules\Wave\Segments\Bitrate;

class BitrateTest extends TestCase
{
    /**
     * A basic unit test example.
     */

    private Representation $mockRepresentation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepresentation = new Representation(
            new \DOMElement("mockDOM"),
            0,
            0,
            0
        );
    }


    public function testExample(): void
    {
        $reporter = app(ModuleReporter::class);

        $bitrate = new Bitrate();

        $this->assertEquals(count($reporter->knownContexts()), 1);
    }

    public function testNoRepresentations(): void
    {
        $reporter = app(ModuleReporter::class);

        $bitrate = new Bitrate();
        $bitrate->validateBitrate($this->mockRepresentation, []);

        $this->assertEquals($reporter->verdict(), "FAIL");
    }

    public function testValidBitrates(): void
    {
        $reporter = app(ModuleReporter::class);

        $bitrate = new Bitrate();
        $bitrate->validateBitrate($this->mockRepresentation, [
            new BitrateSegment(BitrateEnum::Valid1),
            new BitrateSegment(BitrateEnum::Valid2),
        ]);

        $this->assertEquals($reporter->verdict(), "PASS");
    }

    public function testInvalidBitrates(): void
    {
        $reporter = app(ModuleReporter::class);

        $bitrate = new Bitrate();
        $bitrate->validateBitrate($this->mockRepresentation, [
            new BitrateSegment(BitrateEnum::Valid1),
            new BitrateSegment(BitrateEnum::Valid2),
            new BitrateSegment(BitrateEnum::Invalid1),
        ]);

        $this->assertEquals($reporter->verdict(), "FAIL");
    }

    public function testNoDuration(): void
    {
        $reporter = app(ModuleReporter::class);

        $bitrate = new Bitrate();
        $bitrate->validateBitrate($this->mockRepresentation, [
            new BitrateSegment(BitrateEnum::Valid1),
            new BitrateSegment(BitrateEnum::Valid2),
            new BitrateSegment(BitrateEnum::Invalid2),
        ]);

        $this->assertEquals($reporter->verdict(), "FAIL");
    }
}
