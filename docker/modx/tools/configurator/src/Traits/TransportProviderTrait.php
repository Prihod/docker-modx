<?php

namespace App\Traits;

use App\Utils\Logger;
use Exception;
use modProcessorResponse;
use modX;
use MODX\Revolution\Processors\Workspace\Providers\Create as CreateProvider;
use MODX\Revolution\Transport\modTransportPackage;
use MODX\Revolution\Transport\modTransportProvider;
use Psr\Http\Message\ResponseInterface;

trait TransportProviderTrait
{
    protected modX $modx;

    protected ?array $transportProviders = null;

    protected function getPackage(string $name): ?object
    {
        $name = trim($name);
        $q = $this->modx->newQuery(modTransportPackage::class);
        $q->where([
            'package_name:=' => $name,
            'OR:package_name:=' => strtolower($name),
        ]);

        return $this->modx->getObject(modTransportPackage::class, $q);
    }

    protected function installPackage(string $name, array $options = []): bool
    {
        $providerName = $options['provider'] ?? 'modx.com';
        $providers = $this->getTransportProviders();
        $provider = $providers[$providerName] ?? null;
        if (!$provider) {
            Logger::error("Provider '{$providerName}' for package '{$name}' not found!");

            return false;
        }

        $this->modx->getVersionData();
        $productVersion = $this->modx->version['code_name'] . '-' . $this->modx->version['full_version'];
        /** @var ResponseInterface|bool $response */
        $response = $provider->request('package', 'GET', [
            'supports' => $productVersion,
            'query' => $name,
        ]);

        if (!empty($response)) {
            $foundPackages = simplexml_load_string($response->getBody()->getContents());
            foreach ($foundPackages as $foundPackage) {
                /** @var modTransportPackage $foundPackage */
                /** @noinspection PhpUndefinedFieldInspection */
                if (preg_match('#^' . $name . '\b#i', (string) $foundPackage->name)) {
                    $sig = explode('-', (string) $foundPackage->signature);
                    $versionSignature = explode('.', $sig[1]);
                    /** @noinspection PhpUndefinedFieldInspection */
                    $url = $foundPackage->location;
                    $dst = $this->modx->getOption('core_path') . 'packages/' . $foundPackage->signature . '.transport.zip';
                    if (!$this->downloadPackage($url, $dst)) {
                        Logger::error("Could not download package '{$name}'!");

                        return false;
                    }

                    $package = $this->modx->newObject(modTransportPackage::class);
                    $package->set('signature', $foundPackage->signature);
                    /** @noinspection PhpUndefinedFieldInspection */
                    $package->fromArray([
                        'created' => date('Y-m-d H:i:s'),
                        'updated' => null,
                        'state' => 1,
                        'workspace' => 1,
                        'provider' => $provider->get('id'),
                        'source' => $foundPackage->signature . '.transport.zip',
                        'package_name' => $name,
                        'version_major' => $versionSignature[0],
                        'version_minor' => empty($versionSignature[1]) ? 0 : $versionSignature[1],
                        'version_patch' => empty($versionSignature[2]) ? 0 : $versionSignature[2],
                    ]);

                    if (!empty($sig[2])) {
                        $r = preg_split('/(\d+)/', $sig[2], -1, PREG_SPLIT_DELIM_CAPTURE);
                        if (is_array($r) && $r !== []) {
                            $package->set('release', $r[0]);
                            $package->set('release_index', ($r[1] ?? '0'));
                        } else {
                            $package->set('release', $sig[2]);
                        }
                    }
                    if ($package->save() && $package->install()) {
                        return true;
                    }
                    Logger::error("Could not install package '{$name}'!");

                    return false;
                }
            }
            Logger::error("Could not find package '{$name}' in '{$providerName}' repository!");
        }

        return false;
    }

    protected function downloadPackage(string $url, string $dst): bool
    {
        try {
            $file = false;
            if (ini_get('allow_url_fopen')) {
                $file = @file_get_contents($url);
            } elseif (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 180);
                $safeMode = @ini_get('safe_mode');
                $openBasedir = @ini_get('open_basedir');
                if (empty($safeMode) && empty($openBasedir)) {
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                }
                $file = curl_exec($ch);
                curl_close($ch);
            }

            if ($file) {
                file_put_contents($dst, $file);

                return file_exists($dst);
            }
        } catch (Exception $e) {
            Logger::error($e->getMessage());
        }

        return false;
    }

    protected function addProvider(array $data): bool
    {
        $this->modx->error->reset();
        /** @var modProcessorResponse $response */
        $response = $this->modx->runProcessor(CreateProvider::class, $data);
        if ($response->isError()) {
            Logger::error($response->getResponse());

            return false;
        }

        return true;
    }

    protected function hasProvider(string $name): bool
    {
        $providers = $this->getTransportProviders();

        return isset($providers[$name]);
    }

    protected function getTransportProviders(): array
    {
        if ($this->transportProviders !== null) {
            return $this->transportProviders;
        }

        $this->transportProviders = [];
        /** @var modTransportProvider $provider */
        $list = $this->modx->getCollection(modTransportProvider::class);
        foreach ($list as $provider) {
            $this->transportProviders[$provider->get('name')] = $provider;
        }

        return $this->transportProviders;
    }
}
