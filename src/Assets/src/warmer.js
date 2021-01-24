if (typeof Craft.CraftWarmer === typeof undefined) {
    Craft.CraftWarmer = {};
}

Craft.CraftWarmer.Warmer = class CraftWarmer {
	constructor({totalUrls, urlLimit, processLimit, isAdmin = false, secret = false, urlPath = 'craftwarmer'}, observer)
	{
		this.observer = observer;
		this.secret = secret;
		this.urlLimit = urlLimit;
		this.totalUrls = totalUrls;
		this.processLimit = processLimit;
		this.isAdmin = isAdmin;
		this.urlPath = urlPath;
		if (!Number.isInteger(totalUrls)) {
			throw 'Total urls must be an integer';
		}
		if (!Number.isInteger(processLimit)) {
			throw 'Limit process must be an integer';
		}
		if (!Number.isInteger(urlLimit)) {
			throw 'urlLimit must be an integer';
		}
	}

	getAjaxUrl(url)
	{
		url = this.urlPath + '/' + url;
		if (this.isAdmin) {
			return Craft.getCpUrl(url);
		}
		return Craft.getSiteUrl(url);
	}

	getAjaxData(data = {})
	{
		if (Craft.csrfTokenName) {
			data[Craft.csrfTokenName] = Craft.csrfTokenValue;
		}
		if (this.secret) {
			data.secret = this.secret;
		}
		return data;
	}

	reset()
	{
		this.callsRunning = 0;
		this.urlsDone = 0;
		this.queue = [];
		this.ajaxCalls = [];
		this.promise = $.Deferred();
		this.finishing = false;
	}

	initiate()
	{
		let _this = this;
		return $.ajax({
			url: _this.getAjaxUrl('initiate'),
			dataType: 'json',
			method: 'POST',
			data: _this.getAjaxData()
		});
	}

	unlock()
	{
		let _this = this;
		return $.ajax({
			url: _this.getAjaxUrl('unlock'),
			dataType: 'json',
			method: 'POST',
			data: _this.getAjaxData()
		});
	}

	terminate()
	{
		let _this = this;
		return $.ajax({
			url: _this.getAjaxUrl('terminate'),
			dataType: 'json',
			method: 'POST',
			data: _this.getAjaxData()
		});
	}

	startBatch(data)
	{
		let _this = this;
		return $.ajax({
			url: _this.getAjaxUrl('batch'),
			data: _this.getAjaxData(data),
			dataType: 'json',
			method: 'POST',
		});
	}

	updateRunningCalls()
	{
		this.callsRunning--;
		this.checkQueue();
	}

	checkQueue()
	{
		let _this = this;
		if (this.queue.length && this.callsRunning < this.processLimit) {
			let data = this.queue.shift();
			this.callsRunning++;
			this.ajaxCalls.push(this.startBatch(data)
				.done(function(data){
					_this.urlsDone += _this.urlLimit;
					if (_this.urlsDone > _this.totalUrls) {
						_this.urlsDone = _this.totalUrls;
					}
					if (_this.observer) {
						_this.observer.updateProgress(_this.urlsDone, data);
					}
					_this.updateRunningCalls();
				}).fail(function(response){
					if (response.responseJSON) {
						Craft.cp.displayError(response.responseJSON.error);
					}
					_this.updateRunningCalls();
			}));
		}
		if (!this.queue.length && this.callsRunning == 0 && !this.finishing) {
			this.finishing = true;
			$.when(..._this.ajaxCalls).then(function(){
				_this.terminate().done(function(){
					_this.promise.resolve(_this.messages);
					_this.unbindWindowClosing();
				});
			});
		}
	}

	buildQueue()
	{
		let current = 0;
		while (this.totalUrls > current) {
			this.queue.push({offset: current});
			current += this.urlLimit;
			this.checkQueue();
		}
	}

	bindWindowClosing()
	{
		let _this = this;
		$(window).bind("beforeunload", function() {
			_this.abortAll();
		    _this.terminate();
		});
	}

	unbindWindowClosing()
	{
		$(window).off("beforeunload");
	}

	abortAll()
	{
		for (var i = this.ajaxCalls.length - 1; i >= 0; i--) {
			this.ajaxCalls[i].abort();
		}
	}

	stop()
	{
		this.queue = [];
		this.abortAll();
		return this.terminate();
	}

	run()
	{
		this.reset();
		let _this = this;
		this.initiate().done(function(data){
			_this.observer.initiated(data);
			_this.bindWindowClosing();
			_this.buildQueue();
		}).fail(function(data){
			_this.promise.reject(data);
		});
		return this.promise;
	}
}

$(function() {
	new Craft.CraftWarmer.Modal('#craftwarmer-modal', {total_urls: warmer_settings.totalUrls});
	let modal = $('#craftwarmer-modal').data('modal');
	let craftwarmer = new Craft.CraftWarmer.Warmer(warmer_settings, modal);
	$('.warmthemup').click(function(){
		modal.show();
		craftwarmer.run().done(function(){
			modal.hide();
			Craft.cp.displayNotice(Craft.t('craftwarmer', 'Warmup process was successful'));
		}).fail(function(response){
			modal.hide();
			if (response.responseJSON) {
				Craft.cp.displayError(response.responseJSON.error);
			}
			if (!warmer_settings.disableLocking) {
				$('.break-lock').fadeIn('fast');
			}
		});
	});
	$('.break-lock button').click(function(){
		craftwarmer.unlock().done(function(data){
			Craft.cp.displayNotice(data.message);
			$('.break-lock').fadeOut('fast');
		});
	});
	$('#craftwarmer-modal .close').click(function(e){
		e.preventDefault();
		modal.stopping();
		craftwarmer.stop().done(function(data){
			modal.hide();
		});
	});
});