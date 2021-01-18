<?php 

namespace Ryssbowh\CraftWarmer\Controllers;

use Ryssbowh\CraftWarmer\Assets\FrontAsset;
use Ryssbowh\CraftWarmer\CraftWarmer;
use Ryssbowh\CraftWarmer\Exceptions\craftwarmerException;
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

		$settings = CraftWarmer::$plugin->getSettings();
		$service = CraftWarmer::$plugin->warmer;

		return $this->renderTemplate('craftwarmer/front', [
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
		$service = CraftWarmer::$plugin->warmer;
		if ($service->isLocked()) {
			$this->response->data = \Craft::t('craftwarmer',"Cache warming process is already happening, aborting.") . PHP_EOL;
        	$this->response->setStatusCode(403);
        	return $this->response;
		}
		$service->lock();
		try {
			$urls = $service->getUrls(true);
			$total = sizeof($urls);
			$safe = $service->setExecutionTime($total);
			if (!$safe) {
				$this->response->data .= \Craft::t('craftwarmer', 'Warning : Your max execution time is {time} seconds, which might be too small to crawl {number} urls', ['time' => ini_get('max_execution_time'), 'number' => $total])  . PHP_EOL;
			}
			$this->response->data .= \Craft::t('craftwarmer', "Crawling {number} urls ...", ['number' => $total]) . PHP_EOL;
			foreach ($urls as $url) {
				$code = $service->crawlOne($url);
				$this->response->data .= \Craft::t('craftwarmer', 'Crawled {url} : {code}', ["url" => $url, "code" => $code]) . PHP_EOL;
			}
		} catch (\Exception $e) {
			$this->response->data .= \Craft::t('craftwarmer', 'Error : {error}', ['error' => $e->getMessage()]) . PHP_EOL;
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
		$service = CraftWarmer::$plugin->warmer;
		if (!$service->isLocked()) {
			$service->lock();
			return $this->asJson(['success' => true]);
		}
		throw craftwarmerException::locked();
	}

	/**
	 * Unlocks the warmer
	 */
	public function actionUnlock()
	{
		$service = CraftWarmer::$plugin->warmer;
		if ($service->isLocked()) {
			$service->unlock();
			$message = \Craft::t('craftwarmer', 'The lock has been removed');
		} else {
			$message = \Craft::t('craftwarmer', 'The warmer is not locked');
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
		$service = CraftWarmer::$plugin->warmer;
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