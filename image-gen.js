jQuery(document).ready( function($) {

	$('.colorpicker').wpColorPicker({
		change: function( event, ui ) {
			$(this).val( ui.color.toString() );
		}
	});

	$('#save-defaults').click( function(ev) {
		ev.preventDefault();
		$form = $(this).closest('form');
		$.post( imageGen.ajaxUrl, {
			action: 'image_gen_defaults',
			args: $form.serialize()
		}, function( response ) {
			// console.log( response );
		}, 'json' );
	});

	$('#image-gen').submit( function(ev) {
		ev.preventDefault();
		var $form = $(this);
		$.post( imageGen.ajaxUrl, {
			action: 'image_gen',
			args: $form.serialize()
		}, function( response ) {
			// console.log( response );
			$form.next('img').remove();
			$form.after( response.data );
			$('img.attachment-full').css('background-image', 'url('+ imageGen.checker + ')' );
		}, 'json' );

	});


});