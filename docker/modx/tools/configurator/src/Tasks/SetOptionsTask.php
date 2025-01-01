<?php

namespace App\Tasks;

use App\Traits\OptionTrait;
use App\Utils\Logger;

class SetOptionsTask extends Task
{
    use OptionTrait;

    public function getName(): string
    {
        return 'Set Options';
    }

    public function execute(): void
    {
        $options = $this->getProperty('set_options', []);
        if (empty($options)) {
            return;
        }
        foreach ($options as $key => $val) {
            if ($this->setOption($key, $val)) {
                Logger::info("Set option: '{$key}' value: '{$val}'");
            } else {
                Logger::error("Error set option: '{$key}' value: '{$val}'");
            }
        }
    }
}