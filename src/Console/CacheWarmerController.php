<?php 

namespace Ryssbowh\CacheWarmer\Console;

use craft\console\Controller;
use yii\console\ExitCode;

class CacheWarmerController extends Controller
{
	const LOCK_FILE = '@root/storage/cache-warmer/lock';

	/**
	 * Sends the emails saved in database
	 * @return int
	 */
	public function actionIndex()
	{
		if (!$this->canRun()) {
			$this->stdout("Aborting.\n");
			return ExitCode::IOERR;
		}
		$this->lock();
		try {
			$this->stdout("Hello.\n");
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
		$wait = CacheWarmer::$plugin->getSettings()->forceUnlock;
		$content = file_get_contents(\Craft::getAlias(self::LOCK_FILE));
		if (($content + 60 * $wait) < time()) {
			$this->stdout("Lock is older than ".$wait." min, breaking it.\n");
			return true;
		}
		$this->stdout("Lock file exists.\n");
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