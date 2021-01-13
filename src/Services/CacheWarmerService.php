<?php 

namespace Ryssbowh\CacheWarmer\Services;

use Ryssbowh\CacheWarmer\CacheWarmer;
use Ryssbowh\CacheWarmer\Exceptions\CacheWarmerException;
use Ryssbowh\CacheWarmer\Models\Settings;
use craft\base\Component;
use craft\models\Site;

class CacheWarmerService extends Component
{
	public function getUrls(Site $site): array
	{
		if (!$url = $site->getBaseUrl()) {
			throw CacheWarmerException::noUrl($site);
		}
		$sitemap = $url."sitemap.xml";
		$sitemapContent = $this->curl($sitemap);
		dump($sitemapContent);
		return [];
	}

	public function getCrawlableSites(): array
	{
		$sites = [];
		foreach ($this->getSettings()->sites as $uid) {
			$sites[] = \Craft::$app->sites->getSiteByUid($uid);
		}
		return $sites;
	}

	protected function curl(string $url)
	{
		$c = curl_init(); 
		curl_setopt($c,CURLOPT_URL, $url);
		curl_setopt($c,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($c,CURLOPT_HEADER, false); 
		$result = curl_exec($c);
		curl_close($c);
		return $result;
	}

	protected function getSettings(): Settings
	{
		return CacheWarmer::$plugin->getSettings();
	}
}