jQuery(document).ready( function($) {

	$('.colorpicker').wpColorPicker({
		change: function( event, ui ) {
			$(this).val( ui.color.toString() );
		}
	});
	$('#image-gen').submit( function(ev) {
		ev.preventDefault();
		var $form = $(this);
		$.post( ajaxurl, {
			action: 'image_gen',
			args: $form.serialize()
		}, function( response ) {
			console.log( response );
			$form.next('img').remove();
			$form.after( response.data );
		}, 'json' );

	});

});