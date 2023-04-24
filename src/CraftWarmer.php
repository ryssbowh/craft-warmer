<?php

namespace Ryssbowh\CraftWarmer;

use Ryssbowh\CraftWarmer\Models\Settings;
use Ryssbowh\CraftWarmer\Services\CraftWarmerService;
use Ryssbowh\CraftWarmer\Utility;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\ElementHelper;
use craft\services\Elements;
use craft\services\Utilities;
use craft\utilities\ClearCaches;
use craft\web\UrlManager;
use craft\web\View;
use yii\base\Event;
use yii\web\Response;

class CraftWarmer extends Plugin
{
    public static $plugin;

    public bool $hasCpSettings = true;

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->setComponents([
            'warmer' => CraftWarmerService::class
        ]);

        $this->registerUtility();
        $this->registerUrls();
        $this->registerTemplates();
        $this->registerElementEvents();
        $this->registerClearCaches();
    }

    /**
     * @inheritDoc
     */
    public function afterSaveSettings(): void
    {
        parent::afterSaveSettings();
        $this->warmer->clearCaches();
    }

    /**
     * Register a clear cache item
     */
    protected function registerClearCaches()
    {
        Event::on(ClearCaches::class, ClearCaches::EVENT_REGISTER_CACHE_OPTIONS, function (Event $event) {
            $event->options[] = [
                'key' => 'warmer-cache',
                'label' => \Craft::t('craftwarmer', 'Warmer sitemap urls'),
                'action' => function () {
                    CraftWarmer::$plugin->warmer->clearCaches();
                }
            ];
        });
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        $sites = [];
        foreach (\Craft::$app->sites->getAllSites() as $site) {
            $sites[$site->uid] = $site->name;
        }
        return \Craft::$app->view->renderTemplate(
            'craftwarmer/settings',
            [
                'settings' => $this->getSettings(),
                'sites' => $sites
            ]
        );
    }

    protected function registerElementEvents()
    {
        if ($this->getSettings()->autoWarmElements) {
            Event::on(Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT, function ($e) {
                if (!ElementHelper::isDraftOrRevision($e->element)) {
                    CraftWarmer::$plugin->warmer->onElementSaved($e->element);
                }
            });
        }
    }

    protected function registerUrls()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules['craftwarmer/batch'] = 'craftwarmer/warm/batch';
            $event->rules['craftwarmer/initiate'] = 'craftwarmer/warm/initiate';
            $event->rules['craftwarmer/unlock'] = 'craftwarmer/warm/unlock';
            $event->rules['craftwarmer/terminate'] = 'craftwarmer/warm/terminate';
        });
        $settings = $this->getSettings();
        if ($settings->enableFrontUrl) {
            Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function (RegisterUrlRulesEvent $event) use ($settings) {
                $event->rules[$settings->frontUrl] = 'craftwarmer/warm/front';
                $event->rules[$settings->frontUrl.'/nojs'] = 'craftwarmer/warm/front-no-js';
                $event->rules[$settings->frontUrl.'/batch'] = 'craftwarmer/warm/batch-front';
                $event->rules[$settings->frontUrl.'/initiate'] = 'craftwarmer/warm/initiate-front';
                $event->rules[$settings->frontUrl.'/unlock'] = 'craftwarmer/warm/unlock';
                $event->rules[$settings->frontUrl.'/terminate'] = 'craftwarmer/warm/terminate-front';
            });
        }
    }

    protected function registerTemplates()
    {
        $settings = $this->getSettings();
        if ($settings->enableFrontUrl) {
            Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function (RegisterTemplateRootsEvent $event) {
                $event->roots['craftwarmer'] = __DIR__ . '/templates';
            });
        }
    }

    protected function registerUtility()
    {
        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = Utility::class;
            }
        );
    }
}
