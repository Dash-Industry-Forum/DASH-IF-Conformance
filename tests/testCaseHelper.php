<?php

namespace DASHIF\Tests;

class TestCaseHelper implements \Iterator
{
    private $label;
    private $end;
    private $valid;
    private $json;
    private $index;


    public function __construct(string $fileName, string $label)
    {
        $this->valid = true;
        $this->end = false;
        $this->label = $label;
        $contents = file_get_contents($fileName);
        if ($contents === false) {
            $this->valid = false;
            return;
        }
        $this->json = json_decode($contents, true);
        if ($this->json === null) {
            $this->valid = false;
            return;
        }
    }

    public function rewind(): void
    {
        $this->end = false;
        $this->index = 0;
    }

    public function valid(): bool
    {
        return $this->valid && !$this->end;
    }

    public function key(): int
    {
        return $this->index;
    }

    public function current(): array
    {
        return [$this->json[$this->index]];
    }

    public function next(): void
    {
        $this->index ++;
        for (; $this->index < count($this->json); $this->index++) {
            if (in_array($this->label, $this->json[$this->index]['labels'])) {
                break;
            }
        }
        if ($this->index == count($this->json)) {
            $this->end = true;
        }
    }
}
