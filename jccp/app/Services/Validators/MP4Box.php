<?php

namespace App\Services\Validators;

use Illuminate\Support\Facades\Process;
use App\Services\Validators\ValidatorInterface;

class MP4Box extends ValidatorInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "MP4Box";
        $this->enabled = Process::run("MP4Box -h")->exitCode() == 0;
    }


    public function run(string $filePath): bool
    {
        $result = Process::run("MP4Box -dxml ${filePath}");
        return $result->exitCode() == 0;
    }
}
