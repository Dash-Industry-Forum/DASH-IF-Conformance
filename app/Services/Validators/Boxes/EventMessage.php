<?php

namespace App\Services\Validators\Boxes;

class EventMessage
{
    public ?int $presentationTime;
    public ?int $timeScale;
    public ?int $eventDuration;

    //Optional fields
    public ?int $id;
    public ?string $value;
    public ?string $schemeIdUri;
    public ?string $messageData;

    public function __construct()
    {
        $this->presentationTime = null;
        $this->timeScale = null;
        $this->eventDuration = null;
        $this->id = null;
        $this->value = null;
        $this->schemeIdUri = null;
        $this->messageData = null;
    }

    public function equals(EventMessage $rhs): bool
    {
        $equal = true;
        if ($this->presentationTime != $rhs->presentationTime) {
            $equal = false;
        }
        if ($this->timeScale != $rhs->timeScale) {
            $equal = false;
        }
        if ($this->eventDuration != $rhs->eventDuration) {
            $equal = false;
        }
        return $equal && $this->equalsOptional($rhs);
    }

    private function equalsOptional(EventMessage $rhs): bool
    {
        $equal = true;
        if ($this->id !== null && $this->id != $rhs->id) {
            $equal = false;
        }
        if ($this->value !== null && $this->value != $rhs->value) {
            $equal = false;
        }
        if ($this->schemeIdUri !== null && $this->schemeIdUri != $rhs->schemeIdUri) {
            $equal = false;
        }
        if ($this->messageData !== null && $this->messageData != $rhs->messageData) {
            $equal = false;
        }
        return $equal;
    }
}
