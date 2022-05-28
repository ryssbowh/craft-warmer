<?php

namespace Ryssbowh\CraftWarmer\Models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    public $sites = [];
    public $ignore = [];
    public $enableFrontUrl = false;
    public $disableLocking = false;
    public $emailMe = false;
    public $maxProcesses = 1;
    public $maxUrls = 25;
    public $concurrentRequests = 25;
    public $frontUrl = 'warm-caches';
    public $secretKey = 'H78d@sd92';
    public $email = '';
    public $userAgent = '';
    public $sitemaps = [];
    public $autoWarmElements = false;

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            ['sites', 'each', 'rule' => ['string']],
            ['ignore', 'each', 'rule' => ['string']],
            ['sitemaps', 'each', 'rule' => ['string']],
            ['maxProcesses', 'integer', 'min' => 1],
            ['concurrentRequests', 'integer', 'min' => 1],
            ['maxUrls', 'integer', 'min' => 1],
            [['frontUrl', 'userAgent', 'email'], 'string'],
            [['frontUrl', 'secretKey'], 'required', 'when' => function($model) {
                return $model->enableFrontUrl;
            }],
            [['disableLocking', 'enableFrontUrl', 'emailMe'], 'boolean'],
            [['concurrentRequests', 'maxProcesses', 'maxUrls'], 'required'],
            ['email', 'email'],
            ['email', 'required', 'when' => function($model) {
                return $model->emailMe;
            }]
        ];
    }

    public function validateEmail()
    {

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
        return isset($this->sitemaps[$uid]) ? ltrim($this->sitemaps[$uid], '/') : 'sitemap.xml';
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

    public function getEmail(): string
    {
        return \Craft::parseEnv($this->email);
    }
}
