# Cache warmer for Craft 3.5

Build up your caches for your Craft site with a cache warmer.

The warmer will look at your sites(s) sitemap.xml and visit them, building up the caches (image transforms, imager, html etc) and making the site load faster for your user. This can be useful when clearing the caches after a release for example, or anytime you clear a site's caches, or make changes in your content.

There are several ways to trigger it :
- With the utility control panel menu
- Console command : `php craft craftwarmer/warm`
- Through a front end url that you can enable in the settings :
	- visit that url with a browser
	- or use tools like curl

Some urls can be ignored in the backend's settings, for each site.

The front end and the control panel triggers can benefit from parallel execution, making the process quicker. The amount of processes and the amount of urls one process will warm are editable in settings.

The parallel execution does not work for console or curl requests. The system will try to set your max_execution_time setting (if allowed by your server), and will visit all urls.

A locking system can prevent several instances of the cache warming.

The warmer will tell you which http code returns each of your urls, so you can quickly spot any issues.

It's important that you set your config `generateTransformsBeforePageLoad` to `true` or your assets transforms won't be generated.

You can send an email after each warmer's run.

From 1.1.0 elements urls (and related elements urls) can be warmed automatically when they are saved, this will need to be enabled in the settings.

## Installation

- Install through composer `composer require ryssbowh/craft-warmer` or using the Craft store.
- set your config `generateTransformsBeforePageLoad` to `true`
- Activate site(s) in the settings.
- Make sure you have urls in your sitemap
- Start the warmer using one of the 4 available ways

## Requirements

- Craft 3.5
- If you server doesn't allow max_execution_time to be changed, you will be facing issues on large sites for the command line and curl

## Developers

Events you can subscribe on :
- `Ryssbowh\CraftWarmer\Observers\GuzzleObserver::EVENT_ON_FULFILLED` : when a url has been visited
- `Ryssbowh\CraftWarmer\Observers\GuzzleObserver::EVENT_ON_REJECTED` : when a url has failed
- `Ryssbowh\CraftWarmer\Services\CraftWarmerService::EVENT_WARMED_ALL` : when all urls have been visited at once
- `Ryssbowh\CraftWarmer\Services\CraftWarmerService::EVENT_WARMED_BATCH` : when a batch of urls have been visited

## Languages

- english
- french


Icons made by [Freepik](http://www.freepik.com/)