# Cache warmer for Craft 3.5

Build up your caches for your Craft site with a cache warmer.

The warmer will look at your sites(s) sitemap.xml and crawl them, building the caches (image transforms, imager, html etc) and making the site load faster for your user. This can be useful when clearing the caches after a release for example, or anytime you clear a site's caches.

Some urls can be ignored in the backend's settings, for each site.

There are several ways to trigger it :
- With the utility control panel menu
- Console command : `php craft cachewarmer/warm`
- Through a front end url that you can enable in the settings :
	- visit that url with a browser
	- or use curl : the url will have to be suffixed by 'nojs', so if you define a front url 'warm', you'll need to curl `http://mysite.com/warm/nojs`

The front end and the control panel triggers will benefit from parallel execution, making the process quicker. The amount of processes and the amount of urls one process will crawl are editable in settings.
You can also let the system decide the amount of urls to crawl in each process according to your php setting 'max_execution_time', in which case it will assume one url takes 2 seconds to crawl.

The parallel execution does not work for console or curl requests. The system will try to set your max_execution_time setting (if allowed by your server), and will crawl all urls.

A locking system will prevent several instances of the cache warming.

##Installation

Install through composer `composer require ryssbowh/craft-warmer` or using the Craft store.
Activate site(s) in the settings.

##Requirements

- php-curl extension.
- php 7
- Craft 3.5


Icons made by [Freepik](http://www.freepik.com/)