<?php

namespace App\Tasks;

use App\Traits\TransportProviderTrait;
use App\Utils\Logger;

class TransportProvidersTask extends Task
{

    use TransportProviderTrait;

    public function getName(): string
    {
        return 'Transport providers';
    }

    public function execute(): void
    {
        $providers = $this->getProperty('transport_providers', []);
        foreach ($providers as $provider) {
            if (
                empty($provider['username']) ||
                empty($provider['api_key']) ||
                $this->hasProvider($provider['name'])
            ) {
                continue;
            }
            if ($this->addProvider($provider)) {
                Logger::info("Added provider: '{$provider['name']}'");
            }
        }
    }

}