<?php

namespace Ryssbowh\CacheWarmer;

use Ryssbowh\CacheWarmer\Models\Settings;
use Ryssbowh\CacheWarmer\Services\CacheWarmerService;
use Ryssbowh\CacheWarmer\Utility;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Utilities;
use craft\web\UrlManager;
use craft\web\View;
use yii\base\Event;

class CacheWarmer extends Plugin
{
    public static $plugin;

    public $hasCpSettings = true;

    public $controllerNamespace = 'Ryssbowh\\CacheWarmer\\Controllers';

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->setComponents([
            'warmer' => CacheWarmerService::class
        ]);

        if (\Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'Ryssbowh\\CacheWarmer\\Console';
        }

        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = Utility::class;
            }
        );

        Event::on(
            CacheWarmer::class,
            CacheWarmer::EVENT_AFTER_SAVE_SETTINGS,
            function () {
                CacheWarmer::$plugin->warmer->buildCache();
            }
        );

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['cachewarmer/crawl'] = 'cachewarmer/warm/crawl';
            $event->rules['cachewarmer/lock-if-can-run'] = 'cachewarmer/warm/lock-if-can-run';
            $event->rules['cachewarmer/unlock'] = 'cachewarmer/warm/unlock';
        });

        $settings = $this->getSettings();
        if ($settings->enableFrontUrl) {
            Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) use ($settings) {
                $event->rules[$settings->frontUrl] = 'cachewarmer/warm/front';
            });
            Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function (RegisterTemplateRootsEvent $event) {
                $event->roots['cachewarmer'] = __DIR__ . '/templates';
            });
        }
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
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
    	// dd($sites);
        return \Craft::$app->view->renderTemplate(
            'cachewarmer/settings',
            [
                'settings' => $this->getSettings(),
                'sites' => $sites
            ]
        );
    }
}
