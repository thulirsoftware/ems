<?php

namespace App\Services\AI\Tools;

interface AITool
{
    public function metadata(): array;

    /** @return string[] */
    public function actors(): array;

    public function execute(array $arguments): mixed;
}
