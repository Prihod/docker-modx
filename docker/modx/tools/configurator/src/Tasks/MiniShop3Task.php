<?php

namespace App\Tasks;

use App\Traits\DocumentTrait;
use App\Traits\ElementsTrait;
use App\Traits\OptionTrait;
use App\Traits\TransportProviderTrait;
use App\Utils\Logger;
use MiniShop3\MiniShop3;
use MiniShop3\Model\msCategory;
use MiniShop3\Model\msVendor;
use modProcessorResponse;
use MODX\Revolution\modTemplate;
use MS3DemoData\Processors\Generate as GenerateDemoData;

// Demo data from https://modstore.pro/packages/utilities/ms3demodata
class MiniShop3Task extends Task
{
    use OptionTrait;
    use ElementsTrait;
    use DocumentTrait;
    use TransportProviderTrait;

    protected array $pageIds = [];

    protected array $templateIds = [];

    public function getName(): string
    {
        return 'MiniShop3';
    }

    public function execute(): void
    {

        if (!$this->getPackage('MiniShop3')) {
            Logger::error('MiniShop3 is not installed!');

            return;
        }

        $this->createTemplates();
        $this->createPages();
        $this->setMs3DefaultOptions();
        $config = $this->getDemoConfig();
        if (empty($config['enable'])) {
            return;
        }

        $this->importDemo();
    }

    protected function resetDemo(): void
    {
        $this->modx->removeCollection(msVendor::class, []);

        $classKey = msCategory::class;
        $q = $this->modx->newQuery($classKey);
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
        if (!$this->getPackage('ms3DemoData')) {
            Logger::info('Start Install Package ms3DemoData');
            if (!$this->installMs3DemoData()) {
                Logger::ERROR('Error in install package ms3DemoData');
                Logger::ERROR('Aborting import demo data for MiniShop3');

                return;
            }
            $this->modx->reloadConfig();
        }

        $config = $this->getDemoConfig();
        if (!empty($config['reset'])) {
            Logger::info('Start reset demo for MiniShop3...');
            $this->resetDemo();
            Logger::info('Finish reset demo for MiniShop3...');
        }

        $params = [
            'generate_customers' => $config['customers'] ?? false,
            'generate_orders' => $config['orders'] ?? false,
            'package' => $config['data_size'] ?? 'S',
            'parent_id' => $this->getRootMs3CategoryId(),
            'category_template' => $this->getRootMs3CategoryId(),
            'product_template' => $this->getRootMs3CategoryId(),
            'createdby' => $this->modx->user ? (int)$this->modx->user->get('id') : 1,
        ];

        Logger::info('Start import demo data for MiniShop3...');

        /** @var modProcessorResponse $response */
        $response = $this->modx->runProcessor(GenerateDemoData::class, $params);
        if ($response->isError()) {
            Logger::error('Failed to import demo data for MiniShop3: ' . $response->getResponse());
        }

        Logger::info('Finis import demo data for MiniShop3');
    }

    protected function createPages(): void
    {
        $config = $this->getProperty('ms3', []);
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

            if (($doc = $this->createModDocument($page)) instanceof \MODX\Revolution\modResource) {
                $this->pageIds[$key] = $doc->get('id');
                Logger::info("Created MiniShop3 page '{$doc->get('pagetitle')}'. ID:{$doc->get('id')}");
            } else {
                $info = print_r($page, 1);
                Logger::error("Error create MiniShop3 page '{$info}'");
            }
        }
    }

    protected function createTemplates(): void
    {
        $config = $this->getProperty('ms3', []);
        $templates = $config['templates'] ?? [];
        $defaultTemplateContent = $this->getDefaultTemplateContent();
        foreach ($templates as $name) {
            $template = $this->createTemplate($name, '', $defaultTemplateContent);
            if ($template instanceof \MODX\Revolution\modTemplate) {
                $this->templateIds[$name] = $template->get('id');
                Logger::info("Created MiniShop3 template '{$name}'. ID:{$template->get('id')}");
            } else {
                Logger::error("Error create MiniShop3 template '{$name}'");
            }
        }
        $this->setDefaultTemplateBase();
    }

    protected function createTemplate(string $name, string $content = '', string $defaultContent = ''): ?modTemplate
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
        if ($content !== '' && $content !== '0') {
            if (str_contains($defaultContent, '[[$Content]]')) {
                $content = str_replace('[[$Content]]', $content, $defaultContent);
            } elseif (str_contains($defaultContent, '[[*content]]')) {
                $content = str_replace('[[*content]]', $content, $defaultContent);
            }
        }

        return $content;
    }

    protected function setDefaultTemplateBase(): void
    {

        $defaultTemplateId = (int)$this->getOption('default_template', 1);
        if ($defaultTemplateId === 1 && $template = $this->findModTemplate('base')) {
            $this->setOption('default_template', $template->get('id'));
        }
    }

    protected function getDefaultTemplateContent(): string
    {
        $id = (int)$this->getOption('default_template', 1);
        if (($template = $this->getModTemplate($id)) instanceof \MODX\Revolution\modTemplate) {
            return $template->get('content');
        }

        return '';
    }

    protected function setMs3DefaultOptions(): void
    {
        if (!empty($this->templateIds['catalog'])) {
            $this->setOption('ms3_template_category_default', $this->templateIds['catalog']);
        }

        if (!empty($this->templateIds['product'])) {
            $this->setOption('ms3_template_product_default', $this->templateIds['product']);
        }
        $this->setOption('ms3_order_user_groups', 'Customer');

        $this->setOption('ms3_cart_page_id', $this->findModDocument('Cart')?->get('id') ?? 0);
        $this->setOption('ms3_order_page_id', $this->findModDocument('Order')?->get('id') ?? 0);
        $this->setOption('ms3_order_redirect_thanks_id', $this->findModDocument('Thanks')?->get('id') ?? 0);

    }

    protected function getRootMs3CategoryId(): int
    {
        return $this->findModDocument('Category')?->get('id') ?? 0;
    }

    protected function getMs3(): ?MiniShop3
    {
        return $this->modx->services->get('ms3');
    }

    protected function installMs3DemoData(): bool
    {
        return $this->installPackage('ms3DemoData', [
            'provider' => 'modstore.pro',
        ]);
    }

    protected function getDataPath(): string
    {
        return $this->getStoragePath() . 'ms3/';
    }

    protected function getDemoConfig(): array
    {
        $config = $this->getProperty('ms3', []);

        return $config['demo'] ?? [];
    }
}
