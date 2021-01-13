<?php

namespace Ryssbowh\CacheWarmer\Models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    public $sites = [];
    public $ignore = '';
    public $sitemaps = [];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['sites', 'each', 'rule' => ['string']],
            ['sitemaps', 'each', 'rule' => ['string']],
            ['ignore', 'string']
        ];
    }

    public function getSitemap(string $uid)
    {
        return $this->sitemaps[$uid] ?? '';
    }
}
