<?php 

namespace Ryssbowh\CraftWarmer\Exceptions;

use craft\models\Site;

class CraftWarmerException extends \Exception
{
	public static function locked()
	{
		throw new static(\Craft::t('craftwarmer', 'Cache warming process is locked (already running), you can break the lock if you think something is wrong'));
	}
}