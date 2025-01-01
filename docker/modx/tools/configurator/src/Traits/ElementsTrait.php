<?php

namespace App\Traits;

trait ElementsTrait
{
    protected \modX $modx;


    protected function createModTemplate(string $name, string $content = '', int $categoryId = 0, array $params = []):? object
    {
        if (!$template = $this->findModTemplate($name)) {
            $template = $this->modx->newObject('modTemplate');
            $template->fromArray($params);
            $template->set('templatename', $name);
            $template->set('content', $content);
            $template->set('category', $categoryId);
            return $template->save() ? $template : null;
        }
        return $template;
    }

    protected function findModTemplate(string $name): ?object
    {
        return $this->modx->getObject('modTemplate', ['templatename' => $name]);
    }

    protected function getModTemplate(int $id): ?object
    {
        return $this->modx->getObject('modTemplate', $id);
    }

    protected function createModChunk(string $name, string $content = '', int $categoryId = 0, array $params = []):? object
    {
        if (!$chunk = $this->findModChunk($name)) {
            $chunk = $this->modx->newObject('modChunk');
            $chunk->fromArray($params);
            $chunk->set('name', $name);
            $chunk->set('snippet', $content);
            $chunk->set('category', $categoryId);
            return $chunk->save() ? $chunk : null;
        }
        return $chunk;
    }

    protected function findModChunk(string $name): ?object
    {
        return $this->modx->getObject('modChunk', ['name' => $name]);
    }

    protected function createModCategory(string $name, int $parent = 0, array $params = []):? object
    {
        if (!$category = $this->findModCategory($name)) {
            $category = $this->modx->newObject('modCategory');
            $category->fromArray($params);
            $category->set('name', $name);
            $category->set('parent', $parent);
            return $category->save() ? $category : null;
        }
        return $category;
    }

    protected function findModCategory(string $name): ?object
    {
        return $this->modx->getObject('modCategory', ['name' => $name]);
    }

}