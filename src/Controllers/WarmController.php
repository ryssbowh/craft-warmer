<?php 

namespace Ryssbowh\CacheWarmer\Controllers;

use Ryssbowh\CacheWarmer\Assets\FrontAsset;
use Ryssbowh\CacheWarmer\CacheWarmer;
use Ryssbowh\CacheWarmer\Exceptions\CacheWarmerException;
use craft\web\Controller;

class WarmController extends Controller
{
	protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

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
			'locked' => $service->isLocked()
		]);
	}

	/**
	 * Front request without javascript (curl, wget etc)
	 */
	public function actionFrontNoJs()
	{
		$service = CacheWarmer::$plugin->warmer;
		if ($service->isLocked()) {
			$this->response->data = \Craft::t('cachewarmer',"Cache warming process is already happening, aborting.") . PHP_EOL;
        	$this->response->setStatusCode(403);
        	return $this->response;
		}
		$service->lock();
		try {
			$urls = $service->getUrls(true);
			$total = sizeof($urls);
			$safe = $service->setExecutionTime($total);
			if (!$safe) {
				$this->response->data .= \Craft::t('cachewarmer', 'Warning : Your max execution time is {time} seconds, which might be too small to crawl {number} urls', ['time' => ini_get('max_execution_time'), 'number' => $total])  . PHP_EOL;
			}
			$this->response->data .= \Craft::t('cachewarmer', "Crawling {number} urls ...", ['number' => $total]) . PHP_EOL;
			foreach ($urls as $url) {
				$code = $service->crawlOne($url);
				$this->response->data .= \Craft::t('cachewarmer', 'Crawled {url} : {code}', ["url" => $url, "code" => $code]) . PHP_EOL;
			}
		} catch (\Exception $e) {
			$this->response->data .= \Craft::t('cachewarmer', 'Error : {error}', ['error' => $e->getMessage()]) . PHP_EOL;
			$this->response->setStatusCode(500);
		}
		$service->unlock();
		return $this->response;
	}

	/**
	 * Crawl a batch of urls;
	 */
	public function actionCrawl()
	{
		$limit = \Craft::$app->request->getQueryParam('limit', false);
		$current = \Craft::$app->request->getQueryParam('current', 0);
		$urlCodes = $this->doCrawl($limit, $current);
		return $this->asJson($urlCodes);
	}

	/**
	 * Locks the warmer if not locked already
	 */
	public function actionLockIfCanRun()
	{
		$service = CacheWarmer::$plugin->warmer;
		if (!$service->isLocked()) {
			$service->lock();
			return $this->asJson(['success' => true]);
		}
		throw CacheWarmerException::locked();
	}

	/**
	 * Unlocks the warmer
	 */
	public function actionUnlock()
	{
		$service = CacheWarmer::$plugin->warmer;
		if ($service->isLocked()) {
			$service->unlock();
			$message = \Craft::t('cachewarmer', 'The lock has been removed');
		} else {
			$message = \Craft::t('cachewarmer', 'The warmer is not locked');
		}
		return $this->asJson([
			'message' => $message
		]);
	}

	/**
	 * Executes crawl
	 * @param  int|false      $limit
	 * @param  int|integer    $current
	 * @return array
	 */
	protected function doCrawl($limit, int $current = 0): array
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