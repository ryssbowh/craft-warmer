<?php

namespace Ryssbowh\CacheWarmer;

use Ryssbowh\CacheWarmer\Models\Settings;
use Ryssbowh\CacheWarmer\Services\CacheWarmerService;
use craft\base\Plugin;

class CacheWarmer extends Plugin
{
    public static $plugin;

    public $hasCpSettings = true;

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
