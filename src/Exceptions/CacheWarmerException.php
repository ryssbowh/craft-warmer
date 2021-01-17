<?php 

namespace Ryssbowh\CacheWarmer\Exceptions;

use craft\models\Site;

class CacheWarmerException extends \Exception
{
	public static function locked()
	{
		throw new static(\Craft::t('cachewarmer', 'Cache warming process is already happening, aborting.'));
	}
}