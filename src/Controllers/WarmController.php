<?php 

namespace Ryssbowh\CraftWarmer\Controllers;

use Ryssbowh\CraftWarmer\Assets\FrontAsset;
use Ryssbowh\CraftWarmer\CraftWarmer;
use Ryssbowh\CraftWarmer\Exceptions\CraftWarmerException;
use Ryssbowh\CraftWarmer\Observers\GuzzleObserver;
use craft\web\Controller;
use yii\base\Event;
use yii\web\NotFoundHttpException;

class WarmController extends Controller
{
	protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

	/**
	 * Front request
	 */
	public function actionFront()
	{
		$this->checkSecret(\Craft::$app->request->getRequiredQueryParam('secret'));

		$this->view->registerAssetBundle(FrontAsset::class);

		$service = CraftWarmer::$plugin->warmer;
		$settings = CraftWarmer::$plugin->getSettings();

		return $this->renderTemplate('craftwarmer/front', [
			'sites' => $service->getCrawlableSites(),
			'urls' => $service->getUrls(false, true),
			'totalUrls' => $service->getTotalUrls(),
			'processLimit' => $settings->maxProcesses,
			'urlLimit' => $settings->maxUrls,
			'disableLocking' => $settings->disableLocking,
			'locked' => $service->isLocked(),
			'secret' => \Craft::$app->request->getRequiredQueryParam('secret'),
			'logs' => $service->getLastRunLogs(),
			'logDate' => $service->getLastRunDate()
		]);
	}

	/**
	 * Front request without javascript (curl, wget etc)
	 */
	public function actionFrontNoJs()
	{
		$this->checkSecret();
		$service = CraftWarmer::$plugin->warmer;
		try {
			$safe = $service->initiateWarmer('nojs');
			$total = $service->getTotalUrls();
			if (!$safe) {
				$this->response->data .= \Craft::t('craftwarmer', 'Warning : Unable to change your max execution time ({time} seconds), it might be too small to visit {number} urls', ['time' => ini_get('max_execution_time'), 'number' => $total])  . PHP_EOL;
			}
			$this->response->data .= \Craft::t('craftwarmer', "Visiting {number} urls ...", ['number' => $total]) . PHP_EOL;
			Event::on(GuzzleObserver::class, GuzzleObserver::EVENT_ON_FULFILLED, function ($event) {
				$this->response->data .= $event->message . PHP_EOL;
			});
			Event::on(GuzzleObserver::class, GuzzleObserver::EVENT_ON_REJECTED, function ($event) {
				$this->response->data .= $event->message . PHP_EOL;
			});
			$service->warmAll();
			$service->unlock();
		} catch (\Exception $e) {
			$this->response->data .= $e->getMessage() . PHP_EOL;
			$this->response->setStatusCode(500);
			$service->unlock();
			CraftWarmer::log('nojs request failed');
			return $this->response;
		}
		$this->response->data .= \Craft::t('craftwarmer', 'Finished, {number} urls were visited', ['number' => $total]) . PHP_EOL;
		return $this->response;
	}

	/**
	 * Warms a batch of urls (front request)
	 */
	public function actionBatchFront()
	{
		$this->checkSecret();
		return $this->actionBatch();
	}

	/**
	 * Crawl a batch of urls;
	 */
	public function actionBatch()
	{
		$this->requireAcceptsJson();
		$offset = \Craft::$app->request->getQueryParam('offset', 0);
		CraftWarmer::$plugin->warmer->setRequestType('ajax');
		$urlCodes = CraftWarmer::$plugin->warmer->warmBatch($offset);
		return $this->asJson($urlCodes);
	}

	/**
	 * Initiate the warmer (front request)
	 */
	public function actionInitiateFront()
	{
		$this->checkSecret();
		return $this->actionInitiate();
	}

	/**
	 * Initiate the warmer
	 */
	public function actionInitiate()
	{
		$this->requireAcceptsJson();
		$service = CraftWarmer::$plugin->warmer;
		$service->initiateWarmer('ajax');
		$date = new \DateTime;
		return $this->asJson(['success' => true, 'date' => $date->format('d-m-Y H:i')]);
	}

	/**
	 * Unlocks the warmer
	 */
	public function actionUnlock()
	{
		$this->requireAcceptsJson();
		$service = CraftWarmer::$plugin->warmer;
		$service->setRequestType('ajax');
		if ($service->isLocked()) {
			$service->unlock();
			$message = \Craft::t('craftwarmer', 'The lock has been removed');
		} else {
			$message = \Craft::t('craftwarmer', 'The warmer is not locked');
		}
		return $this->asJson([
			'message' => $message
		]);
	}

	protected function checkSecret(string $secret = null)
	{
		if($secret === null) {
			$secret = \Craft::$app->request->getRequiredParam('secret');
		}
		$settings = CraftWarmer::$plugin->getSettings();
		$secret2 = \Craft::$app->security->hashData($settings->secretKey);
		$secret = \Craft::$app->security->hashData($secret);
		if ($secret2 != $secret) {
			throw CraftWarmerException::wrongSecret();
		}
	}
}