<?php 

namespace Ryssbowh\CacheWarmer\Services;

use Ryssbowh\CacheWarmer\CacheWarmer;
use Ryssbowh\CacheWarmer\Exceptions\CacheWarmerException;
use Ryssbowh\CacheWarmer\Models\Settings;
use craft\base\Component;
use craft\models\Site;
use vipnytt\SitemapParser;

class CacheWarmerService extends Component
{
	const LOCK_FILE = '@root/storage/cachewarmer/lock';

	const CACHE_FILE = '@root/storage/cachewarmer/urls';

	protected $urls;

	public function init()
	{
		$file = $this->getLockFile();
		$folder = dirname($file);
		if (!is_dir($folder)) {
			mkdir($folder, 0755, true);
		}
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
	public function getUrls($flat = false): array
	{
		$data = $this->getCache();
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
	 * Cache will be a file in storage, not the Craft cache, this tool is
	 * intended to be used when cache are cleared, using Craft cache doesn't make sense
	 * 
	 * @return array
	 */
	public function buildCache(): array
	{
		$data = [];
		$settings = $this->getSettings();
		foreach ($settings->sites as $uid) {
			$site = \Craft::$app->sites->getSiteByUid($uid);
			if (!$url = $site->getBaseUrl()) {
				continue;
			}
			$urls = [];
			$parser = new SitemapParser();
			$sitemap = $settings->getSitemap($uid);
		    $parser->parseRecursive($url.$sitemap);
		    foreach ($parser->getURLs() as $url => $tags) {
		        $urls[] = $url;
		    }
		    $data[$site->id] = $this->ignoreUrls($urls, $site);
		}
		file_put_contents($this->getCacheFile(), json_encode($data));
		return $data;
	}

	/**
	 * Get urls cached on disk
	 * 
	 * @return array
	 */
	protected function getCache(): array
	{
		if ($this->urls === null) {
			$file = $this->getCacheFile();
			if (!file_exists($file)) {
				$this->urls = [];
			} else {
				$this->urls = json_decode(file_get_contents($file), true);
			}
		}
		return $this->urls;
	}

	/**
	 * Crawl one url, return http code
	 * 
	 * @param  string $url
	 * @return int
	 */
	public function crawlOne(string $url): int
	{
		return $this->curl($url);
	}

	/**
	 * Can the crawling be run
	 * 
	 * @return bool
	 */
	public function canRun(): bool
	{
		return !$this->isLocked();
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
	 * Write the lock file so processes dont overlap and builds the urls in cache
	 */
	public function lock()
	{
		CacheWarmer::log('locking cache warmer');
		file_put_contents($this->getLockFile(), time());
		$this->buildCache();
	}

	/**
	 * Delete the lock file
	 */
	public function unlock()
	{
		CacheWarmer::log('unlocking cache warmer');
		unlink($this->getLockFile());
	}

	/**
	 * Setting max execution time in case we suppose we don't have enough.
	 * We'll suppose crawling one url takes 2 seconds.
	 * 
	 * @param int $totalUrls
	 * @return bool is the execution time good enough
	 */
	public function setExecutionTime(int $totalUrls): bool
	{
		$time = (int)ini_get('max_execution_time');
		if ($time > 0 and $time < $totalUrls*2) {
			return set_time_limit($totalUrls*2);
		}
		return true;
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
	 * Get cache file full path
	 * 
	 * @return string
	 */
	protected function getCacheFile(): string
	{
		return \Craft::getAlias(self::CACHE_FILE);
	}

	/**
	 * Curl one url, return http code
	 * 
	 * @param  string $url
	 * @return int
	 */
	protected function curl(string $url): int
	{
		$c = curl_init(); 
		curl_setopt($c,CURLOPT_URL, $url);
		curl_setopt($c,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($c,CURLOPT_HEADER, true); 
		$result = curl_exec($c);
		$code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		curl_close($c);
		CacheWarmer::log('Curled '.$url.' : '.$code);
		return $code;
	}

	/**
	 * get plugin settings
	 * 
	 * @return Settings
	 */
	protected function getSettings(): Settings
	{
		return CacheWarmer::$plugin->getSettings();
	}
}