<?php

namespace Ryssbowh\CacheWarmer\Models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    public $sites = [];
    public $ignore = '';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['sites', 'each', 'rule' => 'string'],
            ['ignore', 'string']
        ];
    }
}
