<?php

namespace App\Traits;

use modX;
use MODX\Revolution\modCategory;
use MODX\Revolution\modChunk;
use MODX\Revolution\modTemplate;

trait ElementsTrait
{
    protected modX $modx;

    protected function createModTemplate(string $name, string $content = '', int $categoryId = 0, array $params = []): ?modTemplate
    {
        if (!$template = $this->findModTemplate($name)) {
            $template = $this->modx->newObject(modTemplate::class);
            $template->fromArray($params);
            $template->set('templatename', $name);
            $template->set('content', $content);
            $template->set('category', $categoryId);

            return $template->save() ? $template : null;
        }

        return $template;
    }

    protected function findModTemplate(string $name): ?modTemplate
    {
        return $this->modx->getObject(modTemplate::class, ['templatename' => $name]);
    }

    protected function getModTemplate(int $id): ?modTemplate
    {
        return $this->modx->getObject(modTemplate::class, $id);
    }

    protected function createModChunk(string $name, string $content = '', int $categoryId = 0, array $params = []): ?modChunk
    {
        if (!$chunk = $this->findModChunk($name)) {
            $chunk = $this->modx->newObject(modChunk::class);
            $chunk->fromArray($params);
            $chunk->set('name', $name);
            $chunk->set('snippet', $content);
            $chunk->set('category', $categoryId);

            return $chunk->save() ? $chunk : null;
        }

        return $chunk;
    }

    protected function findModChunk(string $name): ?modChunk
    {
        return $this->modx->getObject(modChunk::class, ['name' => $name]);
    }

    protected function createModCategory(string $name, int $parent = 0, array $params = []): ?modCategory
    {
        if (!$category = $this->findModCategory($name)) {
            $category = $this->modx->newObject(modCategory::class);
            $category->fromArray($params);
            $category->set('name', $name);
            $category->set('parent', $parent);

            return $category->save() ? $category : null;
        }

        return $category;
    }

    protected function findModCategory(string $name): ?modCategory
    {
        return $this->modx->getObject(modCategory::class, ['name' => $name]);
    }
}
