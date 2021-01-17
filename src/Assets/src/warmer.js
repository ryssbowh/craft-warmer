class CacheWarmer {
	constructor(maxExecutionTime, totalUrls, progresser)
	{
		this.limit = false;
		this.locked = true;
		this.progresser = progresser;
		this.processLimit = 1;
		if (!Number.isInteger(totalUrls)) {
			throw 'Total urls must be an integer';
		} else {
			this.totalUrls = totalUrls;
		}
		if (!Number.isInteger(maxExecutionTime)) {
			throw 'maxExecutionTime must be an integer';
		} else {
			this.maxExecutionTime = maxExecutionTime;
			if (maxExecutionTime > 0) {
				this.limit = 1;
				// this.limit = maxExecutionTime/2;
			}
		}
	}

	lock()
	{
		let _this = this;
		return $.ajax({
			url: Craft.getCpUrl('cachewarmer/lock-if-can-run'),
			dataType: 'json'
		}).done(function(){
			_this.locked = true;
		});
	}

	unlock()
	{
		let _this = this;
		return $.ajax({
			url: Craft.getCpUrl('cachewarmer/unlock'),
			dataType: 'json'
		}).done(function(){
			_this.locked = false;
		});
	}

	crawlBatch(current)
	{
		let data = {limit: this.limit, current: current};
		return $.ajax({
			url: Craft.getCpUrl('cachewarmer/crawl'),
			data: data
		});
	}

	sleep(seconds)
	{
		return new Promise(resolve => setTimeout(resolve, seconds*1000));
	}

	run()
	{
		if (!this.locked) {
			throw "The warner must be locked before being run";
		}
		let started = 0;
		let crawled = 0;
		let running = 0;
		let promises = [];
		let messages = {};
		let _this = this;
		while (this.totalUrls > started) {
			if (running >= this.processLimit) {
				console.log('sleeping');
				await this.sleep(1);
				console.log('wakup');
				continue;
			}
			promises.push(this.crawlBatch(started).done(function(data){
				crawled += _this.limit;
				messages = {...messages, ...data};
				running--;
				if (_this.progresser) {
					_this.progresser.updateProgress(crawled, _this.totalUrls);
				}
			}));
			running ++;
			started += this.limit;
		}
		let promise = $.Deferred();
		$.when(...promises).done(function(){
			_this.unlock();
			promise.resolve(messages);
		});
		return promise;
	}
}

window.CacheWarmer = CacheWarmer;