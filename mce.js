(function() {
	tinymce.PluginManager.add('twd_mce_button', function( editor, url ) {
	   	// function getValues() {
	    // 	return editor.settings.cptPostsList;
	   	// }
		editor.addButton('twd_mce_button', {
			text: '{ TWD CPT Posts }',
			icon: false,
			tooltip: 'CPT List',
			onclick: function() {
				editor.windowManager.open( {
					title: 'Insert List',
					width: 400,
					height: 100,
					body: [
						{
							type: 'listbox',
							name: 'listboxName',
							label: 'TWD CPT Posts',
							'values': editor.settings.cptPostsList
						}
					],
					onsubmit: function( e ) {
						editor.insertContent( '[twd_post_title id="' + e.data.listboxName + '"]');
					}
				});
			}
		});
	});
})();