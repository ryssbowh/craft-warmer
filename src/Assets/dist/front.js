"use strict";function _toConsumableArray(t){return _arrayWithoutHoles(t)||_iterableToArray(t)||_unsupportedIterableToArray(t)||_nonIterableSpread()}function _nonIterableSpread(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}function _unsupportedIterableToArray(t,e){if(t){if("string"==typeof t)return _arrayLikeToArray(t,e);var r=Object.prototype.toString.call(t).slice(8,-1);return"Map"===(r="Object"===r&&t.constructor?t.constructor.name:r)||"Set"===r?Array.from(t):"Arguments"===r||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(r)?_arrayLikeToArray(t,e):void 0}}function _iterableToArray(t){if("undefined"!=typeof Symbol&&Symbol.iterator in Object(t))return Array.from(t)}function _arrayWithoutHoles(t){if(Array.isArray(t))return _arrayLikeToArray(t)}function _arrayLikeToArray(t,e){(null==e||e>t.length)&&(e=t.length);for(var r=0,a=new Array(e);r<e;r++)a[r]=t[r];return a}function _classCallCheck(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}function _defineProperties(t,e){for(var r=0;r<e.length;r++){var a=e[r];a.enumerable=a.enumerable||!1,a.configurable=!0,"value"in a&&(a.writable=!0),Object.defineProperty(t,a.key,a)}}function _createClass(t,e,r){return e&&_defineProperties(t.prototype,e),r&&_defineProperties(t,r),t}function _typeof(t){return(_typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t})(t)}function warm(e,t){e.show(),t.run().done(function(){e.hide(),Craft.cp.displayNotice(Craft.t("craftwarmer","Warmup process was successful"))}).fail(function(t){e.hide(),Craft.cp.displayError(t.responseJSON.error),warmer_settings.disableLocking||$(".break-lock").fadeIn("fast")})}"undefined"===_typeof(Craft.CraftWarmer)&&(Craft.CraftWarmer={}),Craft.CraftWarmer.Warmer=function(){function i(t,e){var r=t.totalUrls,a=t.urlLimit,n=t.processLimit,s=t.isAdmin,t=t.secret;if(_classCallCheck(this,i),this.observer=e,this.secret=t,this.urlLimit=a,this.totalUrls=r,this.processLimit=n,this.isAdmin=s,!Number.isInteger(r))throw"Total urls must be an integer";if(!Number.isInteger(n))throw"Limit process must be an integer";if(!Number.isInteger(a))throw"urlLimit must be an integer"}return _createClass(i,[{key:"getUrl",value:function(t){return this.isAdmin?Craft.getCpUrl(t):Craft.getSiteUrl(t)}},{key:"reset",value:function(){this.callsRunning=0,this.urlsDone=0,this.queue=[],this.ajaxCalls=[],this.promise=$.Deferred(),this.finishing=!1}},{key:"getAjaxData",value:function(){var t=0<arguments.length&&void 0!==arguments[0]?arguments[0]:{};return Craft.csrfTokenName&&(t[Craft.csrfTokenName]=Craft.csrfTokenValue),this.secret&&(t.secret=this.secret),t}},{key:"initiate",value:function(){return $.ajax({url:this.getUrl("craftwarmer/initiate"),dataType:"json",method:"POST",data:this.getAjaxData()})}},{key:"unlock",value:function(){return $.ajax({url:this.getUrl("craftwarmer/unlock"),dataType:"json",method:"POST",data:this.getAjaxData()})}},{key:"startBatch",value:function(t){return $.ajax({url:this.getUrl("craftwarmer/batch"),data:this.getAjaxData(t),dataType:"json",method:"POST"})}},{key:"updateRunningCalls",value:function(){this.callsRunning--,this.checkQueue()}},{key:"checkQueue",value:function(){var t,e=this;this.queue.length&&this.callsRunning<this.processLimit&&(t=this.queue.shift(),this.callsRunning++,this.ajaxCalls.push(this.startBatch(t).done(function(t){e.urlsDone+=e.urlLimit,e.urlsDone>e.totalUrls&&(e.urlsDone=e.totalUrls),e.observer&&e.observer.updateProgress(e.urlsDone,t),e.updateRunningCalls()}).fail(function(t){Craft.cp.displayError(t.responseJSON.error),e.updateRunningCalls()}))),this.queue.length||0!=this.callsRunning||this.finishing||(this.finishing=!0,(t=$).when.apply(t,_toConsumableArray(e.ajaxCalls)).then(function(){e.unlock().done(function(){e.promise.resolve(e.messages),e.unbindWindowClosing()})}))}},{key:"buildQueue",value:function(){for(var t=0;this.totalUrls>t;)this.queue.push({offset:t}),t+=this.urlLimit,this.checkQueue()}},{key:"bindWindowClosing",value:function(){var t=this;$(window).bind("beforeunload",function(){t.abortAll(),t.unlock()})}},{key:"unbindWindowClosing",value:function(){$(window).off("beforeunload")}},{key:"abortAll",value:function(){for(var t=this.ajaxCalls.length-1;0<=t;t--)this.ajaxCalls[t].abort()}},{key:"stop",value:function(){return this.queue=[],this.abortAll(),this.unlock()}},{key:"run",value:function(){this.reset();var e=this;return this.initiate().done(function(t){e.observer.initiated(t),e.bindWindowClosing(),e.buildQueue()}).fail(function(t){e.promise.reject(t)}),this.promise}}]),i}(),"undefined"===_typeof(Craft.CraftWarmer)&&(Craft.CraftWarmer={}),Craft.CraftWarmer.Modal=Garnish.Modal.extend({$progressBar:null,$logs:null,$logsContainer:null,$title:null,$stoppingTitle:null,$lastRun:null,$close:null,init:function(t,e){e.hideOnEsc=!1,e.hideOnShadeClick=!1,this.setSettings(e,Garnish.Modal.defaults),this.$shade=$('<div class="'+this.settings.shadeClass+'"/>'),this.$shade.insertBefore(t),this.$progressBar=new Craft.ProgressBar($("#craftwarmer-modal .progressBar"),!0),this.$progressBar.showProgressBar(),this.$progressBar.setItemCount(e.total_urls),this.$progressBar.setProcessedItemCount(0),this.$close=$("#craftwarmer-modal .close"),this.$stoppingTitle=$("#craftwarmer-modal .stopping-title"),this.$title=$("#craftwarmer-modal .default-title"),this.$logsContainer=$("#craft-warmer-log"),this.$logs=$("#craft-warmer-log .logs"),this.$lastRun=$("#craft-warmer-log .lastRun"),this.setContainer(t),Garnish.Modal.instances.push(this)},getWidth:function(){return 400},getHeight:function(){return 200},onFadeOut:function(){this.trigger("fadeOut"),this.reset()},onFadeIn:function(){this.trigger("fadeIn"),this.$progressBar.updateProgressBar()},initiated:function(t){new Date;this.$logs.html(""),this.$logsContainer.show(),this.$lastRun.html(t.date)},stopping:function(){this.$title.hide(),this.$stoppingTitle.show(),this.$close.attr("disabled",!0)},updateProgress:function(t,e){this.addLogs(e),this.$progressBar.setProcessedItemCount(t),this.$progressBar.updateProgressBar()},reset:function(){this.$close.attr("disabled",!1),this.$title.show(),this.$stoppingTitle.hide(),this.$progressBar.setProcessedItemCount(0)},addLogs:function(t){for(var e=0,r=Object.keys(t);e<r.length;e++){var a=r[e],n=t[a],a=$('<p class="log">'+a+" : "+n+"</p>");200!=n&&a.addClass("error"),this.$logs.append(a)}this.$logsContainer.show()}}),$(function(){new Craft.CraftWarmer.Modal("#craftwarmer-modal",{total_urls:warmer_settings.totalUrls});var e=$("#craftwarmer-modal").data("modal"),r=new Craft.CraftWarmer.Warmer(warmer_settings,e);$(".warmthemup").click(function(){warm(e,r)}),$(".break-lock button").click(function(){r.unlock().done(function(t){Craft.cp.displayNotice(t.message),$(".break-lock").fadeOut("fast")})}),warm(e,r),$("#craftwarmer-modal .close").click(function(t){t.preventDefault(),e.stopping(),r.stop().done(function(t){e.hide()})})});