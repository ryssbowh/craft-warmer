if (typeof Craft.CacheWarmer === typeof undefined) {
    Craft.CacheWarmer = {};
}

Craft.CacheWarmer.Modal = Garnish.Modal.extend({
	$closeBtn: null,
	$progressBar: null,
	$results: null,
	$resultsContainer: null,
	init: function(container, settings) {
		this.setSettings(settings, Garnish.Modal.defaults);
		this.$shade = $('<div class="' + this.settings.shadeClass + '"/>');
		this.$shade.insertBefore(container);
		this.$closeBtn = $('#cachewarmer-modal .close');
		this.$progressBar = new Craft.ProgressBar($('#cachewarmer-modal .progressBar'), true);
		this.$progressBar.showProgressBar();
		this.$results = $('#cachewarmer-modal .results');
		this.$resultsContainer = $('#cachewarmer-modal .results-container');
		this.$progressNumber = $('#cachewarmer-modal .progressNumbers .current');
		this.setContainer(container);
		this.addListener(this.$closeBtn, 'click', 'hide');
		Garnish.Modal.instances.push(this);
	},
	getWidth: function() {
		return 400;
	},
	getHeight: function() {
		return 200;
	},
	onFadeOut: function() {
        this.trigger('fadeOut');
        this.settings.onFadeOut();
        this.reset();
    },
	updateProgress(current, max)
	{
		if (current > max) {
			current = max;
		}
		this.$progressNumber.html(current);
		let percent = current/max * 100;
		this.$progressBar.setProgressPercentage(percent > 100 ? 100 : percent);
	},
	reset()
	{
		this.$progressBar.setProgressPercentage(0);
		this.$progressNumber.html(0);
		this.$results.html('');
		this.$resultsContainer.hide();
	},
	addResults(results)
	{
		let code;
		for (let url of Object.keys(results)) {
			code = results[url];
			let line = $('<div>'+url+'</div>');
			if (code != 200) {
				line.html(url+' : ');
				$('<span>'+code+'</span>').addClass('error').appendTo(line);
			}
			this.$results.append(line);
		}
		this.$container.css('height', 'auto');
		this.$resultsContainer.show();
	}
});