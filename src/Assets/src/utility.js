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
			Craft.cp.displayError(response.responseJSON.error);
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