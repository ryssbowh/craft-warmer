<?php 

namespace Ryssbowh\CraftWarmer\Services;

use Ryssbowh\CraftWarmer\CraftWarmer;
use Ryssbowh\CraftWarmer\Exceptions\CraftWarmerException;
use Ryssbowh\CraftWarmer\Models\Settings;
use Ryssbowh\CraftWarmer\Observers\GuzzleObserver;
use Ryssbowh\PhpCacheWarmer\Warmer;
use craft\base\Component;
use craft\models\Site;
use vipnytt\SitemapParser;

class CraftWarmerService extends Component
{
	const LOCK_FILE = '@root/storage/craftwarmer/lock';

	const LOG_FILE = '@root/storage/craftwarmer/log';

	const EVENT_WARMED_ALL = 'craftwarmer.warmed_all';

	const EVENT_WARMED_BATCH = 'craftwarmer.warmed_batch';

	const URLS_CACHE_KEY = 'craftwarmer.urls';

	/**
	 * Type of current request 
	 * @var string nojs, ajax or console
	 */
	protected $requestType = '';

	public function init()
	{
		$file = $this->getLockFile();
		$folder = dirname($file);
		if (!is_dir($folder)) {
			mkdir($folder, 0755, true);
		}
	}

	/**
	 * Get request type
	 * 
	 * @return string
	 */
	public function getRequestType(): string
	{
		return $this->requestType;
	}

	/**
	 * Set request type
	 */
	public function setRequestType(string $type)
	{
		$this->requestType = $type;
	}

	/**
	 * Get all sites enabled in config
	 * 
	 * @return array
	 */
	public function getCrawlableSites(): array
	{
		$sites = [];
		foreach ($this->getSettings()->sites as $uid) {
			$sites[] = \Craft::$app->sites->getSiteByUid($uid);
		}
		return $sites;
	}

	/**
	 * Get urls and sites to index
	 * 
	 * @param  $flat do we want a flat array
	 * @return array
	 */
	public function getUrls(bool $flat = false, bool $forceCacheRebuild = false): array
	{
		if ($forceCacheRebuild) {
			$data = $this->buildCache();
		} else {
			$data = $this->getCache();
		}
		if ($flat) {
			$data2 = [];
			array_walk($data, function($array) use (&$data2){
				$data2 = $data2 + $array;
			});
			return $data2;
		}
		return $data;
	}

	/**
	 * Total number of urls to crawl
	 * 
	 * @return int
	 */
	public function getTotalUrls(): int
	{
		return sizeof($this->getUrls(true));
	}

	/**
	 * Calculate urls to crawl, and cache them.
	 * 
	 * @return array
	 */
	public function buildCache(): array
	{
		$settings = $this->getSettings();
		$userAgent = ($settings->userAgent ? $settings->userAgent : SitemapParser::DEFAULT_USER_AGENT);
		$parser = new SitemapParser($userAgent);
		$data = [];
		foreach ($settings->sites as $uid) {
			$site = \Craft::$app->sites->getSiteByUid($uid);
			if (!$url = $site->getBaseUrl()) {
				continue;
			}
			$sitemap = $settings->getSitemap($uid);
			$parser->parseRecursive($url.$sitemap);
		    $data[$site->id] = $this->ignoreUrls(array_keys($parser->getUrls()), $site);
		}
		\Craft::$app->cache->set(self::URLS_CACHE_KEY, $data);
		return $data;
	}

	/**
	 * Get urls cached on disk
	 * 
	 * @return array
	 */
	protected function getCache(): array
	{
		$cache = \Craft::$app->cache->get(self::URLS_CACHE_KEY, false);
		if ($cache === false) {
			return $this->buildCache();
		}
		return $cache;
	}

	/**
	 * Is the warmer locked
	 * 
	 * @return bool
	 */
	public function isLocked(): bool
	{
		return file_exists($this->getLockFile());
	}

	/**
	 * Initiate the warmer, locks it and build the urls
	 * 
	 * @param $type nojs, console or ajax
	 * @return bool has the execution time been set properly
	 */
	public function initiateWarmer(string $type): bool
	{
		if (!$this->getSettings()->disableLocking and $this->isLocked()) {
			throw CraftWarmerException::locked();
		}
		$this->requestType = $type;
		$this->resetLog();
		$this->lock();
		$this->buildCache();
		return $this->setExecutionTime();
	}

	/**
	 * Warms all the urls
	 * 
	 * @return  array url => code
	 */
	public function warmAll(): array
	{
		$this->requireLock();
		$time = microtime(true);
		$urls = $this->getUrls(true);
		CraftWarmer::log('Warming '.$this->getTotalUrls().' urls');
		$settings = $this->getSettings();
		$observer = new GuzzleObserver;
		$warmer = new Warmer($settings->concurrentRequests, $this->getGuzzleOptions(), $observer);
		$warmer->addUrls($urls);
		$promise = $warmer->warm()->wait();
		$this->writeLog($observer->getUrls());
		$this->trigger(self::EVENT_WARMED_ALL);
		CraftWarmer::log('Warmed '.sizeof($observer->getUrls()).' in '.(microtime(true) - $time).' seconds. '.(memory_get_peak_usage()/1000000).' MB memory used');
		return $observer->getUrls();
	}

	/**
	 * Warm one batch of urls
	 * 
	 * @param  int    $offset
	 * @return  array url => code
	 */
	public function warmBatch(int $offset): array
	{
		$this->requireLock();
		$settings = $this->getSettings();
		$limit = $settings->maxUrls;
		$time = microtime(true);
		CraftWarmer::log('Warming '.$limit.' urls, starting at '.$offset);
		$observer = new GuzzleObserver;
		$warmer = new Warmer($settings->concurrentRequests, $this->getGuzzleOptions(), $observer);
		$warmer->addUrls(array_slice($this->getUrls(true), $offset, $limit));
		$warmer->warm()->wait();
		$this->writeLog($observer->getUrls());
		$this->trigger(self::EVENT_WARMED_BATCH);
		CraftWarmer::log('Warmed '.sizeof($observer->getUrls()).' in '.(microtime(true) - $time).' seconds. '.(memory_get_peak_usage()/1000000).' MB memory used');
		return $observer->getUrls();
	}

	/**
	 * Write the lock file so processes dont overlap and builds the urls in cache
	 */
	public function lock()
	{
		if ($this->getSettings()->disableLocking) {
			return;
		}
		CraftWarmer::log('locking cache warmer');
		file_put_contents($this->getLockFile(), time());
	}

	/**
	 * Delete the lock file
	 */
	public function unlock()
	{
		if ($this->isLocked() and !$this->getSettings()->disableLocking) {
			CraftWarmer::log('unlocking cache warmer');
			unlink($this->getLockFile());
		}
	}

	/**
	 * Setting max execution time to infinite
	 * 
	 * @return bool
	 */
	public function setExecutionTime(): bool
	{
		$success = set_time_limit(0);
		CraftWarmer::log('Setting max_execution_time : '.($success ? 'success' : 'failed').'. Current value : '.ini_get('max_execution_time'));
		return $success;
	}

	/**
	 * Get last run logs
	 * 
	 * @return array
	 */
	public function getLastRunLogs(): array
	{
		$file = $this->getLogFile();
		if (!file_exists($file)) {
			return [];
		}
		return json_decode(file_get_contents($file), true);
	}

	/**
	 * Date of the last run
	 * 
	 * @return DateTime|null
	 */
	public function getLastRunDate(): ?\DateTime
	{
		if (!file_exists($this->getLogFile())) {
			return null;
		}
		$date = new \DateTime();
		return $date->setTimestamp(filemtime($this->getLogFile()));
	}

	/**
	 * Throws exception if lock is not set
	 */
	public function requireLock()
	{
		if (!$this->getSettings()->disableLocking and !$this->isLocked()) {
			throw CraftWarmerException::notLocked();
		}
	}

	/**
	 * Get options to be used by guzzle
	 * @return array
	 */
	protected function getGuzzleOptions(): array
	{
		$settings = CraftWarmer::$plugin->getSettings();
		$options = [];
		if ($settings->userAgent) {
			$options['headers'] = [
				'User-Agent' => $settings->userAgent
			];
		}
		return $options;
	}

	/**
	 * Write an array of url => code to the log file
	 * 
	 * @param  array  $urls
	 */
	protected function writeLog(array $urls)
	{
		$log = $this->getLastRunLogs();
		$log = array_merge($log, $urls);
		file_put_contents($this->getLogFile(), json_encode($log));
	}

	/**
	 * Get log file path
	 * 
	 * @return string
	 */
	protected function getLogFile(): string
	{
		return \Craft::getAlias(self::LOG_FILE);
	}

	/**
	 * resets log file
	 */
	protected function resetLog()
	{
		file_put_contents($this->getLogFile(), json_encode([]));
	}

	/**
	 * Filters out urls to ignore
	 * 
	 * @param  array  $urls
	 * @param  Site   $site
	 * @return array
	 */
	protected function ignoreUrls(array $urls, Site $site): array
	{
		$ignores = $this->getSettings()->getIgnore($site->uid);
		$ignores = str_replace("\r\n", PHP_EOL, $ignores);
		$ignores = explode(PHP_EOL, $ignores);
		foreach ($ignores as $ignore) {
			if (substr($ignore, 0, 1) == '/' and substr($ignore, -1) == '/') {
				//regular expression
				foreach ($urls as $key => $url) {
					if (preg_match($ignore, $url)) {
						unset($urls[$key]);
					}
				}
			} else {
				foreach ($urls as $key => $url) {
					if ($url == $ignore) {
						unset($urls[$key]);
					}
				}
			}
		}
		return $urls;
	}

	/**
	 * Get lock file full path
	 * 
	 * @return string
	 */
	protected function getLockFile(): string
	{
		return \Craft::getAlias(self::LOCK_FILE);
	}

	/**
	 * get plugin settings
	 * 
	 * @return Settings
	 */
	protected function getSettings(): Settings
	{
		return craftwarmer::$plugin->getSettings();
	}
}