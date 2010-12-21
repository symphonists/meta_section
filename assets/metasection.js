(function($) {
	$(function() {
		var create_button = $("h2").find("a.create");
		var config_button = $("<a/>", {href: '#', title: 'Configurações', 'class': 'black button', text: 'Configurações'});
		var meta = $("<div/>", {id: "meta_section"});
		
		var section_handle = $("script[src*='?section=']").attr("src").match("section=([A-Za-z0-9-_]+)$")[1];

		var meta_section_url = Symphony.WEBSITE + '/symphony/publish/' + section_handle + '/?output-filtering=yes';

		var iframe = $("<iframe/>", {src: meta_section_url, frameborder: 0, border: 0, width: "100%", height: "25%"});
		iframe.appendTo(meta);
		
		$("ul#nav").after(meta);
		
		config_button.toggle(function() {
			meta.show(0);

			$(this).animate({opacity: 0.5}, 'fast');
		}, function() {
			meta.hide(0);
			
			$(this).animate({opacity: 1}, 'fast');			
		});
		
		create_button.after(config_button);
	});
})(jQuery);