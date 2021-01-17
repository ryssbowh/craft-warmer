if (typeof Craft.CacheWarmer === typeof undefined) {
    Craft.CacheWarmer = {};
}

Craft.CacheWarmer.Modal = Garnish.Modal.extend({
	$closeBtn: null,
	$progress: null,
	$results: null,
	init: function(container, settings) {
		this.setSettings(settings, Garnish.Modal.defaults);
		this.$shade = $('<div class="' + this.settings.shadeClass + '"/>');
		this.$shade.appendTo(Garnish.$bod);
		this.$closeBtn = $('#cachewarmer-modal .close');
		this.$progress = $('#cachewarmer-modal .progress');
		this.$results = $('#cachewarmer-modal .results');
		this.$progressNumber = $('#cachewarmer-modal .progressNumbers .current');
		this.setContainer(container);
		this.addListener(this.$closeBtn, 'click', 'hide');
		this.addListener(this.$closeBtn, 'click', 'reset');
		Garnish.Modal.instances.push(this);
	},
	getWidth: function() {
		return 400;
	},
	getHeight: function() {
		return 200;
	},
	updateProgress(current, max)
	{
		if (current > max) {
			current = max;
		}
		let percent = current/max * 100;
		this.$progress.css('width', percent+'%');
		this.$progressNumber.html(current);
	},
	reset()
	{
		this.$progress.css('width', 0);
		this.$progressNumber.html(0);
		this.$results.html('');
	},
	showResults(results)
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
	}
});

$(document).ready(function(){
	new Craft.CacheWarmer.Modal('#cachewarmer-modal', { autoShow: false });
	let cachewarmer = new CacheWarmer(max_execution_time, total_urls, $('#cachewarmer-modal').data('modal'));
	$('.warmthemup').click(function(){
		$('#cachewarmer-modal').data('modal').show();
		cachewarmer.lock().done(function(){
			cachewarmer.run().done(function(results){
				$('#cachewarmer-modal').data('modal').showResults(results);
			});
		}).fail(function(response){
			Craft.cp.displayError(response.responseJSON.error);
		});
	});
});