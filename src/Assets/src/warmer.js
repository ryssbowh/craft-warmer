class CacheWarmer {
	constructor(maxExecutionTime, totalUrls, urlLimit, processLimit, progressBar, isAdmin)
	{
		this.progressBar = progressBar;
		this.urlLimit = false;
		this.totalUrls = totalUrls;
		this.processLimit = processLimit;
		this.isAdmin = isAdmin;
		if (!Number.isInteger(totalUrls)) {
			throw 'Total urls must be an integer';
		}
		if (!Number.isInteger(processLimit)) {
			throw 'Limit process must be an integer';
		}
		if (!Number.isInteger(maxExecutionTime)) {
			throw 'maxExecutionTime must be an integer';
		}
		if (urlLimit) {
			this.urlLimit = urlLimit;
		} else if(maxExecutionTime > 0) {
			this.urlLimit = maxExecutionTime/2;
		}
	}

	getUrl(url)
	{
		if (this.isAdmin) {
			return Craft.getCpUrl(url);
		}
		return Craft.getSiteUrl(url);
	}

	reset()
	{
		this.callsRunning = 0;
		this.urlsDone = 0;
		this.queue = [];
		this.ajaxCalls = [];
		this.messages = {};
		this.promise = $.Deferred();
		this.finishing = false;
	}

	lock()
	{
		let _this = this;
		return $.ajax({
			url: _this.getUrl('cachewarmer/lock-if-can-run'),
			dataType: 'json'
		});
	}

	unlock()
	{
		let _this = this;
		return $.ajax({
			url: _this.getUrl('cachewarmer/unlock'),
			dataType: 'json'
		});
	}

	crawlBatch(data)
	{
		let _this = this;
		return $.ajax({
			url: _this.getUrl('cachewarmer/crawl'),
			data: data
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
		if (this.queue.length && this.callsRunning <= this.processLimit) {
			let data = this.queue.shift();
			this.callsRunning++;
			this.ajaxCalls.push(this.crawlBatch(data)
				.done(function(data){
					_this.urlsDone += _this.urlLimit;
					_this.messages = {..._this.messages, ...data};
					if (_this.progressBar) {
						_this.progressBar.updateProgress(_this.urlsDone, _this.totalUrls);
					}
					_this.updateRunningCalls();
				}).fail(function(){
					_this.updateRunningCalls();
			}));
		}
		if (!this.queue.length && !this.finishing) {
			this.finishing = true;
			$.when(..._this.ajaxCalls).done(function(){
				_this.unlock().done(function(){
					_this.promise.resolve(_this.messages);
					_this.unbindWindowClosing();
				});
			});
		}
	}

	buildQueue()
	{
		let current = 0;
		let data = {};
		while (this.totalUrls > current) {
			this.queue.push({limit: this.urlLimit, current: current});
			current += this.urlLimit;
		}
		this.checkQueue();
	}

	bindWindowClosing()
	{
		let _this = this;
		$(window).bind("beforeunload", function() { 
		    _this.unlock();
		});
	}

	unbindWindowClosing()
	{
		$(window).off("beforeunload");
	}

	run()
	{
		this.reset();
		let _this = this;
		this.lock().done(function(){
			_this.bindWindowClosing();
			_this.buildQueue();
		}).fail(function(data){
			_this.promise.reject(data);
		});
		return this.promise;
	}
}

window.CacheWarmer = CacheWarmer;