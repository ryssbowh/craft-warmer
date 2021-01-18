<?php

namespace Ryssbowh\CraftWarmer\Assets;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class WarmerAsset extends AssetBundle
{
    /**
     * Initializes the bundle.
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = __DIR__ . "/src";

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'modal.js',
            'warmer.js',
        ];

        $this->css = [
            'modal.css',
        ];

        parent::init();
    }
}