<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\DVB\Segments\Codecs;
use App\Services\ModuleReporter;

class DVBCodecsTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_example(): void
    {

        $reporter = app(ModuleReporter::class);

        $codecs = new Codecs();

        $this->assertEquals(count($reporter->knownContexts()), 1);
    }
}
