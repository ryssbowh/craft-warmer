<?php 

namespace Ryssbowh\CraftWarmer\console\controllers;

use Ryssbowh\CraftWarmer\CraftWarmer;
use Ryssbowh\CraftWarmer\Observers\GuzzleObserver;
use craft\console\Controller;
use craft\helpers\Console;
use yii\base\Event;
use yii\console\ExitCode;

class WarmController extends Controller
{
	/**
	 * Crawl all enabled sites to warm up caches
	 * @return int
	 */
	public function actionIndex()
	{
		$service = CraftWarmer::$plugin->warmer;
		try {
			$safe = $service->initiateWarmer('console');
			$total = $service->getTotalUrls();
			if (!$safe) {
				$this->stdout(\Craft::t('craftwarmer', 'Warning : Unable to change your max execution time ({time} seconds), it might be too small to visit {number} urls', ['time' => ini_get('max_execution_time'), 'number' => $total]) . PHP_EOL);
			}
			$this->stdout(\Craft::t('craftwarmer', "Visiting {number} urls ...", ['number' => $total]) . PHP_EOL);
			Event::on(GuzzleObserver::class, GuzzleObserver::EVENT_ON_FULFILLED, function ($event) {
				$this->stdout($event->message . PHP_EOL, Console::FG_GREEN);
			});
			Event::on(GuzzleObserver::class, GuzzleObserver::EVENT_ON_REJECTED, function ($event) {
				$this->stderr($event->message . PHP_EOL, Console::FG_RED);
			});
			$service->warmAll();
			$service->terminate();
		} catch (\Exception $e) {
			$this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED);
			CraftWarmer::log('Console request failed');
			$service->unlock();
			return ExitCode::UNSPECIFIED_ERROR;
		}
		$this->stdout(\Craft::t('craftwarmer', 'Finished, {number} urls were visited', ['number' => $total]) . PHP_EOL);
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
}