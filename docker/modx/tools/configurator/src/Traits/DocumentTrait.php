<?php

namespace App\Traits;

use App\Utils\Logger;

trait DocumentTrait
{
    protected \modX $modx;


    protected function createModDocument(array $params = []): ?object
    {
        $name = $params['pagetitle'] ?? null;
        if (empty($name)) {
            return null;
        }
        if (!$doc = $this->findModDocument($name)) {
            $this->modx->error->reset();
            /** @var \modProcessorResponse $response */
            $response = $this->modx->runProcessor('resource/create', $params);
            if ($response->isError()) {
                Logger::error($response->getResponse());
                return null;
            }
            $obj = $response->getObject();
            $doc = $this->getModDocument($obj['id']);
        }
        return $doc;
    }

    protected function findModDocument(string $name): ?object
    {
        return $this->modx->getObject('modResource', ['pagetitle' => $name]);
    }

    protected function getModDocument(int $id): ?object
    {
        return $this->modx->getObject('modResource', $id);
    }
}