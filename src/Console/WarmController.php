<?php 

namespace Ryssbowh\CacheWarmer\Console;

use Ryssbowh\CacheWarmer\CacheWarmer;
use craft\console\Controller;
use yii\console\ExitCode;

class WarmController extends Controller
{
	/**
	 * Crawl all sites to warm up caches
	 * @return int
	 */
	public function actionIndex()
	{
		$service = CacheWarmer::$plugin->warmer;
		if (!$service->canRun()) {
			$this->stdout(\Craft::t('cachewarmer',"Cache warming process is already happening, aborting.") . PHP_EOL);
			return ExitCode::IOERR;
		}
		$service->lock();
		try {
			$data = $service->getUrls();
			$total = $service->getTotalUrls();
			$safe = $service->setExecutionTime($total);
			if (!$safe) {
				$this->stdout(\Craft::t('cachewarmer', 'Warning : Your max execution time is {time} seconds, which might be too small to crawl {number} urls', ['time' => ini_get('max_execution_time'), 'number' => $total]));
			}
			$this->stdout(\Craft::t('cachewarmer', "Crawling {number} urls ...", ['number' => $total]) . PHP_EOL);
			foreach ($data as $urls) {
				foreach ($urls as $url) {
					$code = $service->crawlOne($url);
					$this->stdout(\Craft::t('cachewarmer', 'Crawled {url} : {code}', ["url" => $url, "code" => $code]) . PHP_EOL);
				}
			}
		} catch (\Exception $e) {
			$this->stderr(\Craft::t('cachewarmer', 'Error : {error}', ['error' => $e->getMessage()]) . PHP_EOL);
			$service->unlock();
			return ExitCode::UNSPECIFIED_ERROR;
		}
		$service->unlock();
		return ExitCode::OK;
	}
}