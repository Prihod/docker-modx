<?php

namespace App\Traits;

use modX;
use MODX\Revolution\modSystemSetting;

trait OptionTrait
{
    protected modX $modx;

    protected function setOption(string $key, string $value, $clearCache = false): bool
    {
        if (!$setting = $this->modx->getObject(modSystemSetting::class, ['key' => $key])) {
            return false;
        }

        $setting->set('value', $value);
        $saved = $setting->save();
        if ($saved) {
            $this->modx->setOption($key, $value);
            if ($clearCache) {
                $this->modx->cacheManager->refresh(['system_settings' => []]);
            }
        }

        return $saved;
    }

    protected function getOption(string $key, $default = null)
    {
        if (!$setting = $this->modx->getObject(modSystemSetting::class, ['key' => $key])) {
            return $default;
        }
        $value = $setting->get('value');
        if (is_string($value)) {
            $value = $this->normalizePath($value);
            $value = $this->normalizeUrl($value);
        }

        return $value;
    }

    private function normalizePath(string $path): string
    {
        return str_replace([
            '{base_path}',
            '{core_path}',
            '{assets_path}',
        ], [
            MODX_BASE_PATH,
            MODX_CORE_PATH,
            MODX_ASSETS_PATH,
        ], $path);
    }

    private function normalizeUrl(string $url): string
    {
        return str_replace([
            '{base_url}',
            '{core_url}',
            '{assets_url}',
        ], [
            MODX_BASE_URL,
            MODX_CORE_URL,
            MODX_ASSETS_URL,
        ], $url);
    }
}
