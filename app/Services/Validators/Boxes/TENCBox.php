<?php

namespace App\Services\Validators\Boxes;

class TENCBox
{
    public function __construct()
    {
        $this->isEncrypted = false;
        $this->ivSize = 0;
        $this->iv = '';
        $this->kid = '';
        $this->cryptByteBlock = 0;
        $this->skipByteBlock = 0;
    }
    public bool $isEncrypted;
    public int $ivSize;
    public string $iv;
    public string $kid;
    public int $cryptByteBlock;
    public int $skipByteBlock;
}
