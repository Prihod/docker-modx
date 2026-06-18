<?php

declare(strict_types=1);

namespace App\Tasks;

interface TaskInterface
{
    public function execute(): void;

    public function getName(): string;
}
