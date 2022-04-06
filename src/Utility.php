<?php

namespace Ryssbowh\CraftWarmer;

use Craft;
use Ryssbowh\CraftWarmer\Assets\UtilityAsset;

class Utility extends \craft\base\Utility
{
	/**
	 * @inheritDoc
	 */
	public static function displayName(): string
	{
		return Craft::t('craftwarmer', 'Cache Warmer');
	}

	/**
	 * @inheritDoc
	 */
	public static function id(): string
	{
		return 'craftwarmer';
	}

	/**
	 * @inheritDoc
	 */
	public static function iconPath(): ?string
	{
		return Craft::getAlias('@Ryssbowh/CraftWarmer/icon_utility.svg');
	}

	/**
	 * @inheritDoc
	 */
	public static function contentHtml(): string
	{
		\Craft::$app->view->registerAssetBundle(UtilityAsset::class);

		$settings = CraftWarmer::$plugin->getSettings();
		$service = CraftWarmer::$plugin->warmer;

		return Craft::$app->view->renderTemplate('craftwarmer/utility', [
			'sites' => $service->getCrawlableSites(),
			'urls' => $service->getUrls(false, true),
			'totalUrls' => $service->getTotalUrls(),
			'processLimit' => $settings->maxProcesses,
			'urlLimit' => $settings->maxUrls,
			'disableLocking' => $settings->disableLocking,
			'locked' => $service->isLocked(),
			'logs' => $service->getLastRunLogs(),
			'logDate' => $service->getLastRunDate()
		]);
	}

}
