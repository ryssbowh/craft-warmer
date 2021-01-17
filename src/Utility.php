<?php

namespace Ryssbowh\CacheWarmer;

use Craft;
use GitWrapper\Exception\GitException;
use Ryssbowh\CacheWarmer\Assets\WarmerAsset;
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
		\Craft::$app->view->registerAssetBundle(WarmerAsset::class);

		$settings = CacheWarmer::$plugin->getSettings();
		$service = CacheWarmer::$plugin->warmer;

		return Craft::$app->view->renderTemplate('cachewarmer/utility', [
			'sites' => $service->getCrawlableSites(),
			'urls' => $service->getUrls(),
			'total_urls' => $service->getTotalUrls(),
			'max_execution_time' => ini_get('max_execution_time'),
			'max_processes' => $settings->maxProcesses,
			'max_urls' => $settings->maxUrls,
			'locked' => !$service->canRun()
		]);
	}

}
