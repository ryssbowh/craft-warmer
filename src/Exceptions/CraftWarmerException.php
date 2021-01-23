<?php 

namespace Ryssbowh\CraftWarmer\Exceptions;

use craft\models\Site;

class CraftWarmerException extends \Exception
{
	public static function locked()
	{
		throw new static(\Craft::t('craftwarmer', 'Cache warming process is locked (already running), you can break the lock if you want to force the execution'));
	}

	public static function notLocked()
	{
		throw new static(\Craft::t('craftwarmer', 'Cache warming process must be locked before running'));
	}

	public static function wrongSecret()
	{
		throw new static(\Craft::t('craftwarmer', 'Invalid secret key'));
	}
}