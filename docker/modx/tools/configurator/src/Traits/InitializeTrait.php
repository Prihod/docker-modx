<?php

namespace App\Traits;

trait InitializeTrait
{
    protected \modX $modx;

    public function initialize(\modX $modx, array $properties = []): void
    {
        $this->modx = $modx;
        if ($this->hasTrait('App\Traits\PropertiesTrait')) {
            $this->setProperties($properties);
        }
    }

    protected function hasTrait(string $trait): bool
    {
        $class = static::class;
        do {
            if (in_array($trait, class_uses($class))) {
                return true;
            }
        } while ($class = get_parent_class($class));

        return false;
    }

}