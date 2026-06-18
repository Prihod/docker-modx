<?php

namespace App\Tasks;

use App\Traits\TransportProviderTrait;
use App\Utils\Logger;
use MODX\Revolution\Transport\modTransportPackage;

class InstallPackagesTask extends Task
{
    use TransportProviderTrait;

    public function getName(): string
    {
        return 'Install packages';
    }

    public function execute(): void
    {
        $packages = $this->getProperty('install_packages', []);
        if (empty($packages)) {
            return;
        }

        foreach ($packages as $data) {
            $name = $data['name'] ?? '';
            $version = $data['version'] ?? '';
            /** @var modTransportPackage $package */
            $package = $this->getPackage($name);
            if ($package && $package->compareVersion($version, '<=')) {
                continue;
            }

            if ($this->installPackage($name, $data)) {
                Logger::info("Installed package '{$name}'");
            }
        }
    }
}
