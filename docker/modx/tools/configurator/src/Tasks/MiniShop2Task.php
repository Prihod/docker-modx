<?php

namespace App\Tasks;

use App\Traits\DocumentTrait;
use App\Traits\ElementsTrait;
use App\Traits\OptionTrait;
use App\Traits\TransportProviderTrait;
use App\Utils\Logger;

// Demo data from https://modstore.pro/packages/utilities/msdemodata
class MiniShop2Task extends Task
{
    use OptionTrait;
    use ElementsTrait;
    use DocumentTrait;
    use TransportProviderTrait;

    protected array $pageIds = [];
    protected array $templateIds = [];
    protected array $demoVendorIds = [];
    protected array $demoCategoryIds = [];

    public function getName(): string
    {
        return 'MiniShop2';
    }

    public function execute(): void
    {

        if (!$this->getPackage('miniShop2')) {
            Logger::error("MiniShop2 is not installed!");
            return;
        }

        $this->createTemplates();
        $this->createPages();
        $this->setMs2DefaultOptions();
        $config = $this->getDemoConfig();
        if (empty($config['enable'])) {
            return;
        }

        $this->importDemo();
    }

    protected function resetDemo(): void
    {
        $this->modx->removeCollection('msVendor', []);

        $classKey = 'msCategory';
        $q = $this->modx->newQuery('msCategory');
        $q->where([
            'parent:!=' => 0,
        ]);

        $iterator = $this->modx->getIterator($classKey, $q);
        foreach ($iterator as $category) {
            $category->remove();
        }


    }

    protected function importDemo(): void
    {
        $config = $this->getDemoConfig();

        if (!empty($config['reset'])) {
            Logger::info('Start reset Demo for MiniShop2...');
            $this->resetDemo();
            Logger::info('Finish reset Demo for MiniShop2...');
        }

        Logger::info('Start import Demo data for MiniShop2...');
        if (!empty($config['vendors'])) {
            Logger::info('Start import vendors...');
            $count = $this->importDemoVendors();
            Logger::info('Finish import vendors. Added ' . $count . ' vendors');
        }

        if (!empty($config['categories'])) {
            Logger::info('Start import categories...');
            $count = $this->importDemoCategories();
            Logger::info('Finish import categories. Added ' . $count . ' categories');
        }

        if (!empty($config['products'])) {
            Logger::info('Start import products...');
            $count = $this->importDemoProducts();
            Logger::info('Start import products. Added ' . $count . ' products');
        }

        Logger::info('Finis import Demo data for MiniShop2');
    }

    protected function importDemoVendors(): int
    {
        try {
            $count = 0;
            $fields = [];
            $file = $this->getDataDemoPath() . 'vendors.csv';
            $this->readCsv($file, function (array $row) use (&$fields, &$count) {
                if (empty($fields)) {
                    $fields = $row;
                } else {
                    $data = $this->prepareVendorData($row, $fields);
                    if ($vendor = $this->createVendor($data)) {
                        $this->demoVendorIds[$data['csv_id']] = $vendor->get('id');
                        $count++;
                    }
                }
            });
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
        }
        return $count;
    }

    protected function createVendor(array $data): ?object
    {
        /** @var \modProcessorResponse $response */
        $response = $this->getMs2()->runProcessor('mgr/settings/vendor/create', $data);
        if ($response->isError()) {
            Logger::error($response->getResponse());
            return null;
        }
        $obj = $response->getObject();
        return $this->modx->getObject('msVendor', $obj['id']);
    }

    protected function prepareVendorData(array $data, array $fields = []): array
    {
        $defaultData = [
            'name' => '',
            'country' => '',
            'logo' => '',
            'address' => '',
            'phone' => '',
            'fax' => '',
            'email' => '',
            'description' => ''
        ];

        if (!empty($fields)) {
            $data = array_combine($fields, $data);
        }

        $data = array_merge($defaultData, $data);
        if (!empty($data['logo'])) {
            $sourceFile = $this->getDataDemoPath() . $data['logo'];
            $targetFile = $this->getImagePath() . $data['logo'];
            $targetPath = dirname($targetFile);

            if (!file_exists($targetPath)) {
                $this->modx->cacheManager->writeTree($targetPath);
            }

            if (file_exists($sourceFile)) {
                if (copy($sourceFile, $targetFile)) {
                    $data['logo'] = str_replace(MODX_BASE_PATH, '', $targetFile);
                } else {
                    Logger::error("Error copy file form {$sourceFile} to {$targetFile}");
                }
            } else {
                Logger::error("File {$sourceFile} does not exist");
            }
        }

        return $data;
    }

    protected function importDemoCategories(): int
    {
        try {
            $count = 0;
            $fields = [];
            $file = $this->getDataDemoPath() . 'categories.csv';
            $this->readCsv($file, function (array $row) use (&$count, &$fields) {
                if (empty($fields)) {
                    $fields = $row;
                } else {
                    $data = $this->prepareCategoryData($row, $fields);
                    if ($doc = $this->createModDocument($data)) {
                        $this->demoCategoryIds[$data['csv_id']] = $doc->get('id');
                        $count++;
                    }
                }
            });
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
        }
        return $count;
    }

    protected function prepareCategoryData(array $data, array $fields = []): array
    {
        $template = $this->modx->getOption('ms2_template_category_default', $this->modx->getOption('default_template'));
        $defaultData = [
            'pagetitle' => '',
            'longtitle' => '',
            'content' => '',
            'description' => '',
            'introtext' => '',
            'class_key' => 'msCategory',
            'createdby' => 1,
            'hidemenu' => false,
            'template' => $template,
            'parent' => $this->pageIds['category'] ?? 0,
            'createdon' => 0,
            'publishedon' => 0,
            'published' => true,
            'alias' => '',
        ];

        if (!empty($fields)) {
            $data = array_combine($fields, $data);
        }

        $data = array_merge($defaultData, $data);
        if (!empty($data['parent_id']) && isset($this->demoCategoryIds[$data['parent_id']])) {
            $data['parent'] = $this->demoCategoryIds[$data['parent_id']];
        }

        return $data;
    }

    protected function importDemoProducts(): int
    {
        try {
            $count = 0;
            $file = $this->getDataDemoPath() . 'products.csv';
            $this->readCsv($file, function (array $row) use (&$count, &$fields) {
                if (empty($fields)) {
                    $fields = $row;
                } else {
                    if ($data = $this->prepareProductData($row, $fields)) {
                        if ($doc = $this->createModDocument($data)) {
                            $this->importProductImages($doc->get('id'), $data['images'] ?? '');
                            $count++;
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
        }
        return $count;
    }

    protected function prepareProductData(array $data, array $fields = []): array
    {
        $source = $this->modx->getOption('ms2_product_source_default', 1);
        $template = $this->modx->getOption('ms2_template_product_default', $this->modx->getOption('default_template'));
        //$this->modx->setOption('ms2_product_source_default', $source);
        $defaultData = [
            'pagetitle' => '',
            'longtitle' => '',
            'content' => '',
            'description' => '',
            'introtext' => '',
            'class_key' => 'msProduct',
            'createdby' => 1,
            'hidemenu' => true,
            'source' => $source,
            'template' => $template,
            'parent' => $this->pageIds['category'] ?? 0,
            'createdon' => 0,
            'publishedon' => 0,
            'published' => true,
            'alias' => '',
        ];

        if (!empty($fields)) {
            $countKeys = count($fields);
            $countValues = count($data);
            if ($countKeys !== $countValues) {
                Logger::error("Error array combine! count keys: {$countKeys} not equal values: {$countValues}");
                Logger::debug("Keys:\n" . print_r($fields));
                Logger::debug("Values:\n" . print_r($data));
                return [];
            }
            $data = array_combine($fields, $data);
        }

        $data = array_merge($defaultData, $data);
        if (!empty($data['parent_id']) && isset($this->demoCategoryIds[$data['parent_id']])) {
            $data['parent'] = $this->demoCategoryIds[$data['parent_id']];
        }

        if (!empty($data['vendor_id']) && isset($this->demoVendorIds[$data['vendor_id']])) {
            $data['vendor'] = $this->demoVendorIds[$data['vendor_id']];
        }

        return $data;
    }

    protected function importProductImages(int $productId, string $images, string $separator = ','): void
    {
        if (empty($images)) {
            return;
        }

        $images = explode($separator, $images);
        $path = $this->getDataDemoPath();
        foreach ($images as $filename) {
            $file = $path . $filename;
            if (!file_exists($file)) {
                Logger::error("Could not import image {$filename} to gallery. File {$file} not found on server.");
            } else {
                /** @var \modProcessorResponse $response */
                $response = $this->getMs2()->runProcessor('mgr/gallery/upload', [
                    'id' => $productId, 'name' => basename($filename), 'file' => $file
                ]);
                if ($response->isError()) {
                    Logger::error($response->getResponse());
                }
            }
        }
    }

    protected function createPages(): void
    {
        $config = $this->getProperty('ms2', []);
        $pages = $config['pages'] ?? [];

        if (empty($pages)) {
            return;
        }

        $path = $this->getDataPath() . 'pages/';
        foreach ($pages as $key => $page) {
            if (empty($page['content'])) {
                $file = $path . $key . '.tpl';
                if (file_exists($file)) {
                    $page['content'] = file_get_contents($file);
                }
            }

            if (
                !empty($page['template']) &&
                is_string($page['template']) &&
                isset($this->templateIds[$page['template']])
            ) {
                $page['template'] = $this->templateIds[$page['template']];
            }

            if ($doc = $this->createModDocument($page)) {
                $this->pageIds[$key] = $doc->get('id');
                Logger::info("Created MiniShop2 page '{$doc->get('pagetitle')}'. ID:{$doc->get('id')}");
            } else {
                $info = print_r($page, 1);
                Logger::error("Error create MiniShop2 page '{$info}'");
            }
        }
    }

    protected function createTemplates(): void
    {
        $this->setDefaultTemplateBootstrap();
        $config = $this->getProperty('ms2', []);
        $templates = $config['templates'] ?? [];
        $defaultTemplateContent = $this->getDefaultTemplateContent();
        foreach ($templates as $name) {
            $template = $this->createTemplate($name, '', $defaultTemplateContent);
            if ($template) {
                $this->templateIds[$name] = $template->get('id');
                Logger::info("Created MiniShop2 template '{$name}'. ID:{$template->get('id')}");
            } else {
                Logger::error("Error create MiniShop2 template '{$name}'");
            }
        }
    }

    protected function createTemplate(string $name, string $content = '', string $defaultContent = ''): ?object
    {
        $path = $this->getDataPath() . 'templates/';
        $file = $path . $name . '.tpl';
        if (file_exists($file)) {
            $content = trim(file_get_contents($file));
            if ($name === 'product') {
                $content = $this->prepareProductTemplateContent($content, $defaultContent);
            }
        }
        $content = $content ?: $defaultContent;
        return $this->createModTemplate($name, $content);
    }

    protected function prepareProductTemplateContent(string $content, string $defaultContent = ''): string
    {
        if ($content) {
            if (strpos($defaultContent, '[[$Content]]') !== false) {
                $content = str_replace('[[$Content]]', $content, $defaultContent);
            } elseif (strpos($defaultContent, '[[*content]]') !== false) {
                $content = str_replace('[[*content]]', $content, $defaultContent);
            }
        }
        return $content;
    }

    protected function setDefaultTemplateBootstrap(): void
    {
        if (!$this->getPackage('Theme.Bootstrap')) {
            return;
        }

        $defaultTemplateId = (int)$this->getOption('default_template', 1);
        if ($defaultTemplateId === 1 && $template = $this->findModTemplate('Bootstrap')) {
            $this->setOption('default_template', $template->get('id'));
        }
    }

    protected function getDefaultTemplateContent(): string
    {
        $id = (int)$this->getOption('default_template', 1);
        if ($template = $this->getModTemplate($id)) {
            return $template->get('content');
        }
        return '';
    }

    protected function setMs2DefaultOptions()
    {
        if (!empty($this->templateIds['category'])) {
            $this->setOption('ms2_template_category_default', $this->templateIds['category']);
        }

        if (!empty($this->templateIds['product'])) {
            $this->setOption('ms2_template_product_default', $this->templateIds['product']);
        }
    }

    protected function getMs2(): ?\miniShop2
    {
        return $this->modx->getService('miniShop2');
    }

    protected function getDataPath(): string
    {
        return $this->getStoragePath() . 'ms2/';
    }

    protected function getDataDemoPath(): string
    {
        return $this->getStoragePath() . 'ms2/demo/';
    }

    protected function getDemoConfig()
    {
        $config = $this->getProperty('ms2', []);
        return $config['demo'] ?? [];
    }
}