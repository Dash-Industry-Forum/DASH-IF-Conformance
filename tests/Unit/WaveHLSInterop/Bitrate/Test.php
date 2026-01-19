<?php

namespace Tests\Unit\WaveHLSInterop\Bitrate;

use Tests\TestCase;
use App\Services\ModuleReporter;
use App\Services\Manifest\Representation;
use App\Modules\Wave\Segments\Bitrate;

class Test extends TestCase
{
    private Representation $mockRepresentation;
    private ModuleReporter $reporter;
    private Bitrate $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepresentation = new Representation(new \DOMElement("mockDOM"), 0, 0, 0);
        $this->reporter = app(ModuleReporter::class);
        $this->subject = new Bitrate();
    }


    public function testNoRepresentations(): void
    {
        $this->subject->validateBitrate($this->mockRepresentation, []);

        $this->assertEquals($this->reporter->verdict(), "FAIL");
    }

    public function testValidBitrates(): void
    {
        $this->subject->validateBitrate($this->mockRepresentation, [
            new Segment(CaseEnum::Valid1),
            new Segment(CaseEnum::Valid2),
        ]);

        $this->assertEquals($this->reporter->verdict(), "PASS");
    }

    public function testInvalidBitrates(): void
    {
        $this->subject->validateBitrate($this->mockRepresentation, [
            new Segment(CaseEnum::Valid1),
            new Segment(CaseEnum::Valid2),
            new Segment(CaseEnum::Invalid1),
        ]);

        $this->assertEquals($this->reporter->verdict(), "FAIL");
    }

    public function testNoDuration(): void
    {
        $this->subject->validateBitrate($this->mockRepresentation, [
            new Segment(CaseEnum::Valid1),
            new Segment(CaseEnum::Valid2),
            new Segment(CaseEnum::Invalid2),
        ]);

        $this->assertEquals($this->reporter->verdict(), "FAIL");
    }
}
