# Cache warmer for Craft 3.5

Build up your caches for your Craft site with a cache warmer.

The warmer will look at your sites(s) sitemap.xml and crawl them, building the caches (image transforms, imager, html etc) and making the site load faster for your user. This can be useful when clearing the caches after a release for example, or anytime you clear a site's caches.

Some urls can be ignored in the backend's settings.

There are 3 ways to trigger it :
- Through the utility menu
- command : `php craft cachewarmer/warm`
- Or enable the front end url in the settings and visit that url

Front end and Command trigger will be subject to the max execution time your php is allowed to have. If it's too low the warmer will try to adjust it, but in some cases it's not possible, the server prevents it.

The utility trigger will take into account this setting and will behave accordingly (by spawning multiple smaller crawls).

##Installation

Install through composer `composer require ryssbowh/craft-warmer`
Activate site(s) in the settings.

##Requirements

- php-curl extension.
- php 7
- Craft 3.5

Icons made by [Freepik](http://www.freepik.com/)