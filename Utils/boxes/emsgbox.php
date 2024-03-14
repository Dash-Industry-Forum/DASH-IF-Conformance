<?php

namespace DASHIF\Boxes;

class EventMessage
{
    public $presentationTime;
    public $timeScale;
    public $eventDuration;

    //Optional fields
    public $id;
    public $value;
    public $schemeIdUri;
    public $messageData;

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

    public function equals($rhs): bool
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

    private function equalsOptional($rhs): bool
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
