<?php

namespace App\Traits;

trait PropertiesTrait
{
    protected array $properties = [];

    public function setProperty(string $key, $value): void
    {
        $this->properties[$key] = $value;
    }

    public function getProperty(string $key, $default = null, bool $skipEmpty = false)
    {
        $value = $this->properties[$key] ?? $default;
        return ($skipEmpty && empty($value)) ? $default : $value;
    }

    public function hasProperty(string $key): bool
    {
        return isset($this->properties[$key]);
    }

    public function unsetProperty(string $key): void
    {
        unset($this->properties[$key]);
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties = []): void
    {
        $this->properties = array_merge($this->properties, $properties);
    }

    public function setDefaultProperties(array $properties = []): array
    {
        $this->properties = array_merge($properties, $this->properties);
        return $this->properties;
    }
}