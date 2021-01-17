<?php 

namespace Ryssbowh\CacheWarmer\Exceptions;

use craft\models\Site;

class CacheWarmerException extends \Exception
{
	public static function locked()
	{
		throw new static(\Craft::t('cachewarmer', 'Cache warming process is locked (already running), you can break the lock if you think something is wrong'));
	}
}