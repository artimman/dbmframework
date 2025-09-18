<?php

declare(strict_types=1);

namespace App\Command;

use Dbm\Interfaces\CommandInterface;

/**
 * The Command class is used in application/console.php
 */
class ExampleCommand implements CommandInterface
{
    public function execute(): void
    {
        echo $this->exampleMethod();
    }

    private function exampleMethod(): string
    {
        return "\033[42mOK! \033[0m \n";
        //return "\033[41mERROR! \033[0m \n";
    }
}
