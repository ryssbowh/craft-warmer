<?php

namespace Ryssbowh\CraftWarmer;

use Ryssbowh\CraftWarmer\Models\Settings;
use Ryssbowh\CraftWarmer\Services\CraftWarmerService;
use Ryssbowh\CraftWarmer\Utility;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Utilities;
use craft\web\UrlManager;
use craft\web\View;
use putyourlightson\logtofile\LogToFile;
use yii\base\Event;
use yii\web\Response;

class CraftWarmer extends Plugin
{
    public static $plugin;

    public $hasCpSettings = true;

    public $controllerNamespace = 'Ryssbowh\\CraftWarmer\\Controllers';

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->setComponents([
            'warmer' => CraftWarmerService::class
        ]);

        if (\Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'Ryssbowh\\CraftWarmer\\Console';
        }

        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = Utility::class;
            }
        );

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['craftwarmer/batch'] = 'craftwarmer/warm/batch';
            $event->rules['craftwarmer/initiate'] = 'craftwarmer/warm/initiate';
            $event->rules['craftwarmer/unlock'] = 'craftwarmer/warm/unlock';
            $event->rules['craftwarmer/terminate'] = 'craftwarmer/warm/terminate';
        });

        $settings = $this->getSettings();
        if ($settings->enableFrontUrl) {
            Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) use ($settings) {
                $event->rules[$settings->frontUrl] = 'craftwarmer/warm/front';
                $event->rules[$settings->frontUrl.'/nojs'] = 'craftwarmer/warm/front-no-js';
                $event->rules[$settings->frontUrl.'/batch'] = 'craftwarmer/warm/batch-front';
                $event->rules[$settings->frontUrl.'/initiate'] = 'craftwarmer/warm/initiate-front';
                $event->rules[$settings->frontUrl.'/unlock'] = 'craftwarmer/warm/unlock';
                $event->rules[$settings->frontUrl.'/terminate'] = 'craftwarmer/warm/terminate-front';
            });
            Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function (RegisterTemplateRootsEvent $event) {
                $event->roots['craftwarmer'] = __DIR__ . '/templates';
            });
        }
    }

    /**
     * Log on a separate file
     * 
     * @param  $message
     * @param  string $type
     */
    public static function log($message, $type = 'log')
    {
        $requestType = self::$plugin->warmer->getRequestType();
        LogToFile::$type($requestType.': '.$message, 'craftwarmer');
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
        return \Craft::$app->view->renderTemplate(
            'craftwarmer/settings',
            [
                'settings' => $this->getSettings(),
                'sites' => $sites
            ]
        );
    }
}
