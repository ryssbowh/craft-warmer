<?php

namespace Ryssbowh\CacheWarmer;

use Craft;
use GitWrapper\Exception\GitException;
use Ryssbowh\CacheWarmer\Assets\UtilityAsset;
use Ryssbowh\Git\Assets\GitAsset;

class Utility extends \craft\base\Utility
{
	/**
	 * @inheritDoc
	 */
	public static function displayName (): string
	{
		return Craft::t('cachewarmer', 'Cache Warmer');
	}

	/**
	 * @inheritDoc
	 */
	public static function id(): string
	{
		return 'cachewarmer';
	}

	/**
	 * @inheritDoc
	 */
	public static function iconPath ()
	{
		return Craft::getAlias('@Ryssbowh/CacheWarmer/icon_utility.svg');
	}

	/**
	 * @inheritDoc
	 */
	public static function contentHtml (): string
	{
		\Craft::$app->view->registerAssetBundle(UtilityAsset::class);

		return Craft::$app->view->renderTemplate('cachewarmer/utility', [
			'sites' => CacheWarmer::$plugin->warmer->getCrawlableSites(),
			'urls' => CacheWarmer::$plugin->warmer->getUrls(),
			'total_urls' => CacheWarmer::$plugin->warmer->getTotalUrls(),
			'max_execution_time' => ini_get('max_execution_time')
		]);
	}

}
