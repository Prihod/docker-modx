<?php

namespace App\Tasks;

use App\Traits\InitializeTrait;
use App\Traits\PropertiesTrait;
use Exception;
use modX;

abstract class Task implements TaskInterface
{
    use PropertiesTrait;
    use InitializeTrait;

    public function __construct(modX $modx, array $properties = [])
    {
        $this->initialize($modx, $properties);
    }

    protected function getStoragePath(): string
    {
        return dirname(__FILE__, 3) . '/storage/';
    }

    protected function getImagePath(): string
    {
        $path = MODX_ASSETS_PATH . 'images/';
        if (!file_exists($path)) {
            $this->modx->cacheManager->writeTree($path);
        }

        return $path;
    }

    protected function readCsv(string $file, callable $callback, string $delimiter = ';'): void
    {
        if (!file_exists($file) || !is_readable($file)) {
            throw new Exception("File not found or not readable: $file");
        }

        if (($handle = fopen($file, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, ';', '"', escape: '\\')) !== false) {
                $callback($row);
            }
            fclose($handle);
        } else {
            throw new Exception("Failed to open file: $file");
        }
    }

    protected function saveArrayToCsv(string $file, array $data): void
    {
        if (($handle = fopen($file, 'w')) !== false) {
            foreach ($data as $row) {
                fputcsv($handle, $row, ';', escape: '\\');
            }
            fclose($handle);

            return;
        }

        throw new Exception("Failed to open file: $file");
    }
}
