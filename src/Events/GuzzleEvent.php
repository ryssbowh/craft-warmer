<?php 

namespace Ryssbowh\CraftWarmer\Events;

use yii\base\Event;

class GuzzleEvent extends Event
{
	public $message;
	public $url;
	public $code;
}