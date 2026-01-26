<?php

namespace App\Services\Validators;

//See the validators subfolder for example implementations
class ValidatorInterface
{
    public string $name = 'Interface';
    public bool $enabled = false;


    //Implementations should set a proper name, and set the enabled flag according to whether it can be run or not.
    public function __construct()
    {
    }

    //Run the validator for a specific configuration
    public function run(string $filePath): bool
    {
        return false;
    }
}
