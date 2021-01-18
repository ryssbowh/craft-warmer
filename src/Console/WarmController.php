<?php 

namespace Ryssbowh\CraftWarmer\Console;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Ryssbowh\CraftWarmer\CraftWarmer;
use Ryssbowh\CraftWarmer\Services\Crawler;
use Ryssbowh\Phpcraftwarmer\Observer;
use craft\console\Controller;
use craft\helpers\Console;
use yii\console\ExitCode;

class WarmController extends Controller implements Observer
{
	/**
	 * Crawl all enabled sites to warm up caches
	 * @return int
	 */
	public function actionIndex()
	{
		$service = CraftWarmer::$plugin->warmer;
		if ($service->isLocked()) {
			$this->stdout(\Craft::t('craftwarmer',"Cache warming process is already happening, aborting.") . PHP_EOL);
			return ExitCode::IOERR;
		}
		$service->lock();
		$urls = $service->getUrls(true);
		$total = sizeof($urls);
		$safe = $service->setExecutionTime();
		if (!$safe) {
			$this->stdout(\Craft::t('craftwarmer', 'Warning : Your max execution time is {time} seconds, which might be too small to crawl {number} urls', ['time' => ini_get('max_execution_time'), 'number' => $total]) . PHP_EOL);
		}
		$this->stdout(\Craft::t('craftwarmer', "Crawling {number} urls ...", ['number' => $total]) . PHP_EOL);
		$crawler = new Crawler($this);
		$crawler->crawlAll($urls);
		$service->unlock();
		CraftWarmer::log('Console request : '.(memory_get_peak_usage()/1000000).' MB memory used');
		return ExitCode::OK;
	}

	/**
	 * Unlocks the warmer
	 * @return int
	 */
	public function actionUnlock()
	{
		$service = CraftWarmer::$plugin->warmer;
		if ($service->isLocked()) {
			$service->unlock();
			$this->stdout(\Craft::t('craftwarmer', 'The lock has been removed') . PHP_EOL);
		} else {
			$this->stdout(\Craft::t('craftwarmer', 'The warmer is not locked') . PHP_EOL);
		}
		return ExitCode::OK;
	}

	public function onFulfilled(Response $response, string $url)
	{
		$this->stdout(\Craft::t('craftwarmer', 'Visited {url} : {code}', ["url" => $url, "code" => $response->getStatusCode()]) . PHP_EOL, Console::FG_GREEN);
	}

	public function onRejected(RequestException $reason, string $url)
	{
		$this->stderr(\Craft::t('craftwarmer', 'Error visiting {url} : {code}', ['url' => $url, 'code' => $reason->getResponse()->getStatusCode()]) . PHP_EOL, Console::FG_RED);
	}
}