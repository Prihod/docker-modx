<?php

namespace App\Traits;

use App\Utils\Logger;
use modProcessorResponse;
use modX;
use MODX\Revolution\modResource;
use MODX\Revolution\Processors\Resource\Create;

trait DocumentTrait
{
    protected modX $modx;

    protected function createModDocument(array $params = []): ?modResource
    {
        $name = $params['pagetitle'] ?? null;
        if (empty($name)) {
            return null;
        }
        if (!$doc = $this->findModDocument($name)) {
            $this->modx->error->reset();
            /** @var modProcessorResponse $response */
            $response = $this->modx->runProcessor(Create::class, $params);
            if ($response->isError()) {
                Logger::error($response->getResponse());

                return null;
            }
            $obj = $response->getObject();
            $doc = $this->getModDocument($obj['id']);
        }

        return $doc;
    }

    protected function findModDocument(string $name): ?modResource
    {
        return $this->modx->getObject(modResource::class, ['pagetitle' => $name]);
    }

    protected function getModDocument(int $id): ?modResource
    {
        return $this->modx->getObject(modResource::class, $id);
    }
}
