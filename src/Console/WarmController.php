<?php 

namespace Ryssbowh\CacheWarmer\Console;

use Ryssbowh\CacheWarmer\CacheWarmer;
use craft\console\Controller;
use yii\console\ExitCode;

class WarmController extends Controller
{
	const LOCK_FILE = '@root/storage/cache-warmer/lock';

	/**
	 * Crawl the site(s) to warm up caches
	 * @return int
	 */
	public function actionIndex()
	{
		// if (!$this->canRun()) {
		// 	$this->stdout(\Craft::t('site',"Lock file exists, aborting.") . "\n");
		// 	return ExitCode::IOERR;
		// }
		$this->lock();
		try {
			$sites = CacheWarmer::$plugin->warmer->getCrawlableSites();
			foreach ($sites as $site) {
				$this->stdout(\Craft::t('site', "Crawling site {site} ...", ["site" => $site->name])."\n");
				$urls = CacheWarmer::$plugin->warmer->getUrls($site);
			}
		} catch (\Exception $e) {
			$this->stderr('Error : '.$e->getMessage()."\n");
		}
		$this->unlock();
		$this->stdout("Finished\n");
		return ExitCode::OK;
	}

	/**
	 * Can the schedule be run
	 * 
	 * @return bool
	 */
	protected function canRun(): bool
	{
		if (!$this->lockExists()) {
			return true;
		}
		return false;
	}

	/**
	 * Write the lock file so processes dont overlap
	 */
	protected function lock()
	{
		$file = \Craft::getAlias(self::LOCK_FILE);
		$folder = dirname($file);
		if (!is_dir($folder)) {
			mkdir($folder, 0755, true);
		}
		file_put_contents($file, time());
	}

	/**
	 * Delete the lock file
	 */
	protected function unlock()
	{
		unlink(\Craft::getAlias(self::LOCK_FILE));
	}

	/**
	 * Does the lock file exist
	 * 
	 * @return boolean
	 */
	protected function lockExists()
	{
		return file_exists(\Craft::getAlias(self::LOCK_FILE));
	}
}