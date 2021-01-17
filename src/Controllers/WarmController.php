<?php 

namespace Ryssbowh\CacheWarmer\Controllers;

use Ryssbowh\CacheWarmer\Assets\FrontAsset;
use Ryssbowh\CacheWarmer\CacheWarmer;
use Ryssbowh\CacheWarmer\Exceptions\CacheWarmerException;
use craft\web\Controller;

class WarmController extends Controller
{
	/**
	 * Front request
	 */
	public function actionFront()
	{
		$this->view->registerAssetBundle(FrontAsset::class);

		$settings = CacheWarmer::$plugin->getSettings();
		$service = CacheWarmer::$plugin->warmer;

		return $this->renderTemplate('cachewarmer/front', [
			'sites' => $service->getCrawlableSites(),
			'urls' => $service->getUrls(),
			'total_urls' => $service->getTotalUrls(),
			'max_execution_time' => ini_get('max_execution_time'),
			'max_processes' => $settings->maxProcesses,
			'max_urls' => $settings->maxUrls,
			'locked' => !$service->canRun()
		]);
	}

	public function actionCrawl()
	{
		$limit = \Craft::$app->request->getQueryParam('limit', false);
		$current = \Craft::$app->request->getQueryParam('current', 0);
		$urlCodes = $this->doCrawl($limit, $current);
		return $this->asJson($urlCodes);
	}

	public function actionLockIfCanRun()
	{
		$service = CacheWarmer::$plugin->warmer;
		if ($service->canRun()) {
			$service->lock();
			return $this->asJson(['success' => true]);
		}
		throw CacheWarmerException::locked();
	}

	public function actionUnlock()
	{
		CacheWarmer::$plugin->warmer->unlock();
		return $this->asJson([
			'message' => \Craft::t('cachewarmer', 'The lock has been removed')
		]);
	}

	protected function doCrawl($limit, int $current = 0)
	{
		$service = CacheWarmer::$plugin->warmer;
		$urlCodes = [];
		$done = 0;
		$urls = array_slice($service->getUrls(true), $current);
		foreach ($urls as $url) {
			if ($limit and $done >= $limit) {
				return $urlCodes;
			}
			$urlCodes[$url] = $service->crawlOne($url);
			$done++;
		}
		return $urlCodes;
	}
}