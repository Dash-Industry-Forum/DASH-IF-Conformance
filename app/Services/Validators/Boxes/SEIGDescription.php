<?php

namespace App\Services\Validators\Boxes;

class SEIGDescription
{
    public bool $isEncrypted;
    public int $ivSize;
    public string $kid;
    public int $constantIvSize;
    public string $constantIv;
}
