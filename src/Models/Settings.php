<?php

namespace Ryssbowh\CacheWarmer\Models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    public $sites = [];
    public $ignore = [];
    public $enableFrontUrl = false;
    public $frontUrl = 'warm-cache';
    public $sitemaps = [];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['sites', 'each', 'rule' => ['string']],
            ['ignore', 'each', 'rule' => ['string']],
            ['sitemaps', 'each', 'rule' => ['string']],
            [['frontUrl'], 'string'],
            ['enableFrontUrl', 'boolean'],
        ];
    }

    /**
     * Get the ignore setting for a site uid
     * @param  string $uid
     * @return string
     */
    public function getIgnore(string $uid): string
    {
        return $this->ignore[$uid] ?? '';
    }

    /**
     * Get the sitemap setting for a site uid
     * @param  string $uid
     * @return string
     */
    public function getSitemap(string $uid): string
    {
        return $this->sitemaps[$uid] ? ltrim($this->sitemaps[$uid], '/') : 'sitemap.xml';
    }

    /**
     * @inheritDoc
     */
    public function validate($attributeNames = NULL, $clearErrors = true)
    {
        foreach ($this->sites as $key => $site) {
            if ($site == '') {
                unset($this->sites[$key]);
            }
        }
        return parent::validate($attributeNames, $clearErrors);
    }
}