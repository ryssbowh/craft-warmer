<?php 

namespace Ryssbowh\CacheWarmer\Exceptions;

use craft\models\Site;

class CacheWarmerException extends \Exception
{
	public static function noUrl(Site $site)
	{
		throw new static(\Craft::t('site', 'Impossible to fetch site\'s sitemap, {site} has no url', ['site' => $site->name]));
	}
}