<?php

namespace Ryssbowh\CraftWarmer\Jobs;

use Ryssbowh\CraftWarmer\CraftWarmer;
use craft\queue\BaseJob;

class WarmUrls extends BaseJob
{
    public $urls;

    public function execute($queue)
    {
        CraftWarmer::$plugin->warmer->autoWarm($this->urls);
    }

    public function getDescription()
    {
        return \Craft::t('craftwarmer', 'Warming pages caches');
    }
}