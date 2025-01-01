<?php

namespace App\Tasks;

interface TaskInterface
{
    public function execute(): void;

    public function getName(): string;
}