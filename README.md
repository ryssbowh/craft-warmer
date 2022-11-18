# Cache warmer for Craft 4

Build up your caches for your Craft site with a cache warmer.

The warmer will look at your sites(s) sitemap.xml and visit them, building up the caches (image transforms, imager, html etc) and making the site load faster for your user. This can be useful when clearing the caches after a release for example, or anytime you clear a site's caches, or make changes in your content.

There are several ways to trigger it :
- With the utility control panel menu
- Console command
- Through a front end url

The front end and the control panel triggers can benefit from parallel execution, making the process quicker.

A locking system can prevent several instances of the cache warming.

The warmer will tell you which http code returns each of your urls, so you can quickly spot any issues.

Elements urls (and related elements urls) can be warmed automatically when they are saved.

## Installation

- `composer require ryssbowh/craft-warmer:^2.0`

## Requirements

- Craft ^4.0
- If you server doesn't allow max_execution_time to be changed, you will be facing issues on large sites for the command line and curl

## Documentation

[Plugin documentation](https://puzzlers.run/plugins/cache-warmer)