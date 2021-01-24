if (typeof Craft.CraftWarmer === typeof undefined) {
    Craft.CraftWarmer = {};
}

Craft.CraftWarmer.Modal = Garnish.Modal.extend({
	$progressBar: null,
	$logs: null,
	$logsContainer: null,
	$title: null,
	$stoppingTitle: null, 
	$lastRun: null,
	$close: null,
	init: function(container, settings) { 
		settings.hideOnEsc = false;
        settings.hideOnShadeClick = false;
		this.setSettings(settings, Garnish.Modal.defaults);
		this.$shade = $('<div class="' + this.settings.shadeClass + '"/>');
		this.$shade.insertBefore(container);
		this.$progressBar = new Craft.ProgressBar($('#craftwarmer-modal .progressBar'), true);
		this.$progressBar.showProgressBar();
		this.$progressBar.setItemCount(settings.total_urls);
		this.$progressBar.setProcessedItemCount(0);
		this.$close = $('#craftwarmer-modal .close');
		this.$stoppingTitle = $('#craftwarmer-modal .stopping-title');
		this.$title = $('#craftwarmer-modal .default-title');
		this.$logsContainer = $('#craft-warmer-log');
		this.$logs = $('#craft-warmer-log .logs');
		this.$lastRun = $('#craft-warmer-log .lastRun');
		this.setContainer(container);
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
        this.reset();
    },
    onFadeIn: function() {
        this.trigger('fadeIn');
        this.$progressBar.updateProgressBar();
    },
    initiated: function(data) {
    	let date = new Date();
    	this.$logs.html('');
    	this.$logsContainer.show();
    	this.$lastRun.html(data.date);
    },
    stopping: function() {
    	this.$title.hide();
    	this.$stoppingTitle.show();
    	this.$close.attr('disabled', true);
    },
	updateProgress(current, logs)
	{
		this.addLogs(logs);
		this.$progressBar.setProcessedItemCount(current);
		this.$progressBar.updateProgressBar();
	},
	reset()
	{
		this.$close.attr('disabled', false);
		this.$title.show();
    	this.$stoppingTitle.hide();
		this.$progressBar.setProcessedItemCount(0);
	},
	addLogs(logs)
	{
		let code;
		for (let url of Object.keys(logs)) {
			code = logs[url];
			let line = $('<p class="log">'+url+' : '+code+'</p>');
			if (code != 200) {
				line.addClass('error');
			}
			this.$logs.append(line);
		}
		this.$logsContainer.show();
	}
});