<?php 

namespace Ryssbowh\CraftWarmer\Observers;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Ryssbowh\CraftWarmer\Events\GuzzleEvent;
use Ryssbowh\PhpCacheWarmer\Observer;
use craft\base\Component;

class GuzzleObserver extends Component implements Observer
{
	const EVENT_ON_FULFILLED = 'fulfilled';
	const EVENT_ON_REJECTED = 'rejected';

	protected $urls = [];

	public function onFulfilled(Response $response, string $url)
	{
		$this->urls[$url] = $response->getStatusCode();
		$event = new GuzzleEvent([
			'message' => \Craft::t('craftwarmer', 'Visited {url} : {code}', ["url" => $url, "code" => $response->getStatusCode()]),
			'code' => $response->getStatusCode(),
			'url' => $url
		]);
        $this->trigger(self::EVENT_ON_FULFILLED, $event); 
	}

	public function onRejected(RequestException $reason, string $url)
	{
		$this->urls[$url] = $reason->getResponse()->getStatusCode();
		$event = new GuzzleEvent([
			'message' => \Craft::t('craftwarmer', 'Error visiting {url} : {code}', ['url' => $url, 'code' => $reason->getResponse()->getStatusCode()]),
			'code' => $reason->getResponse()->getStatusCode(),
			'url' => $url
		]);
        $this->trigger(self::EVENT_ON_REJECTED, $event); 
	}

	public function getUrls(): array
	{
		return $this->urls;
	}
}