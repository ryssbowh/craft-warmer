<?php

namespace Ryssbowh\CraftWarmer\Assets;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class FrontAsset extends AssetBundle
{
    /**
     * Initializes the bundle.
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = __DIR__ . "/dist";

        $this->css = [
            'front.css'
        ];

        $this->depends = [
            UtilityAsset::class
        ];

        parent::init();
    }
}