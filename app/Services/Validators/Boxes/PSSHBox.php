<?php

namespace App\Services\Validators\Boxes;

class PSSHBox
{
    public function __construct()
    {
        $this->systemId = "";
        $this->keys = array();
        $this->data = array();
    }
    public string $systemId;
    /**
     * @var array<string> $keys
     **/
    public array $keys;
    /**
     * @var array<string> $data
     **/
    public array $data;
}
