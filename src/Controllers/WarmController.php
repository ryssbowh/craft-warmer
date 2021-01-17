<?php 

namespace Ryssbowh\CacheWarmer\Controllers;

use Ryssbowh\CacheWarmer\CacheWarmer;
use Ryssbowh\CacheWarmer\Exceptions\CacheWarmerException;
use craft\web\Controller;

class WarmController extends Controller
{
	/**
	 * Crawl all sites to warm up caches for a front end request
	 */
	public function actionIndex()
	{
		$service = CacheWarmer::$plugin->warmer;
		$errors = [];
		$warnings = [];
		$urlCodes = [];
		$message = '';
		if ($service->canRun()) {
			$service->lock();
			try {
				$data = $service->getUrls();
				$total = $service->getTotalUrls();
				$safe = $service->setExecutionTime($total);
				if (!$safe) {
					$warnings[] = \Craft::t('cachewarmer', 'Warning : Your max execution time is {time} seconds, which might be too small to crawl {number} urls', ['time' => ini_get('max_execution_time'), 'number' => $total]);
				}
				$message = \Craft::t('cachewarmer', "Crawling {number} urls ...", ["number" => $total]);
				foreach ($data as $urls) {
					foreach ($urls as $url) {
						$urlCodes[$url] = $service->crawlOne($url);
					}
				}
			} catch (\Exception $e) {
				$errors[] = \Craft::t('cachewarmer', 'Error : {error}', ['error' => $e->getMessage()]);
			}
			$service->unlock();
		} else {
			$errors[] = \Craft::t('cachewarmer',"Cache warming process is already happening, aborting.");
		}
		$response = $this->renderTemplate('cachewarmer/front', [
			'message' => $message,
			'urlCodes' => $urlCodes,
			'warnings' => $warnings,
			'errors' => $errors,
		]);
		if ($errors) {
			$response->statusCode = 400;
		}
		return $response;
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
		return $this->asJson(['success' => true]);
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