<?php
/*
 * Plugin Name: Image Gen
 * Plugin URI: trepmal.com
 * Description: Image generator.
 * Version:
 * Author: Kailey Lampert
 * Author URI: kaileylampert.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * TextDomain: image-gen
 * DomainPath:
 * Network:
 */


/**
 * Get defaults
 *
 * @return array Defaults for image generation
 */
function image_gen__get_defaults() {
	$core_defaults = array(
		'width'         => 150,
		'height'        => 150,
		'lowgrey'       => 120,
		'highgrey'      => 150,
		'alpha'         => 0,
		'blurintensity' => 2,
		'filename'      => uniqid(),

		'text'          => array(),
		'linespacing'   => 10,
		'textsize'      => 40,
		'font'          => plugin_dir_path( __FILE__ ) . '/fonts/SourceSansPro-BoldIt.otf',
		'fontcolor'     => array(0, 80, 80),
	);

	return get_option( 'image_gen_defaults', $core_defaults );
}

/**
 * Create Generator Page
 *
 * @return void
 */
function image_gen__menu() {
	add_media_page( __( 'Image Gen', 'image-gen' ), __( 'Image Gen', 'image-gen' ), 'edit_posts', 'image_gen', 'image_gen__page' );
}
add_action( 'admin_menu', 'image_gen__menu' );

/**
 * Build Generator Page
 *
 * @return void
 */
function image_gen__page() {
	wp_enqueue_script( 'wp-color-picker' );
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'image-gen', plugins_url('image-gen.js', __FILE__ ), array('jquery', 'wp-color-picker') );
	wp_localize_script( 'image-gen', 'imageGen', array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'checker' => plugins_url('white-checker.gif', __FILE__ ),
	) );
	?><div class="wrap">
	<h2><?php _e( 'Image Gen', 'image-gen' ); ?></h2>

	<form method="post" id="image-gen">

	<?php $defaults = image_gen__get_defaults(); ?>

	<p><label><?php _e( 'Title', 'image-gen' ); ?><input value="" name="gen[title]" type="text" /></label></p>
	<p><label><?php _e( 'Text', 'image-gen' ); ?></label><textarea name="gen[text]"><?php echo $defaults['text']; ?></textarea></p>
	<p><label><?php _e( 'Width', 'image-gen' ); ?><input value="<?php echo $defaults['width']; ?>" name="gen[width]" type="number" min="0" /></label></p>
	<p><label><?php _e( 'Height', 'image-gen' ); ?><input value="<?php echo $defaults['height']; ?>" name="gen[height]" type="number" min="0" /></label></p>
	<p><label><?php _e( 'Low Grey', 'image-gen' ); ?><input value="<?php echo $defaults['lowgrey']; ?>" name="gen[lowgrey]" type="number" min="0" max="255" /></label></p>
	<p><label><?php _e( 'High Grey', 'image-gen' ); ?><input value="<?php echo $defaults['highgrey']; ?>" name="gen[highgrey]" type="number" min="0" max="255" /></label></p>
	<p><label><?php _e( 'Blur Intensity', 'image-gen' ); ?><input value="<?php echo $defaults['blurintensity']; ?>" name="gen[blurintensity]" type="number" min="0" /></label></p>
	<p><label><?php _e( 'Alpha', 'image-gen' ); ?><input value="<?php echo $defaults['alpha']; ?>" name="gen[alpha]" type="number" min="0" max="127"/></label></p>

	<p><label><?php _e( 'Size', 'image-gen' ); ?><input value="<?php echo $defaults['textsize']; ?>" name="gen[textsize]" type="number" min="0" /></label></p>
	<p><label><?php _e( 'Linespacing', 'image-gen' ); ?><input value="<?php echo $defaults['linespacing']; ?>" name="gen[linespacing]" type="number" /></label></p>
	<p><label><?php _e( 'Font', 'image-gen' ); ?><select name="gen[font]"><?php

	$fontlist = glob( plugin_dir_path(__FILE__).'/fonts/*.otf' );
	// allow separate plugins to add/edit fonts. Should be True Type.
	$fontlist = apply_filters( 'image_gen_fontlist', $fontlist );

	foreach( $fontlist as $font ) {
		$f = basename( $font );
		$s = selected( $font, $defaults['font'], false );
		echo "<option value='$font'$s>$f</option>";
	}
	?></select></label></p>
	<p><label><?php _e( 'Font color', 'image-gen' ); ?><input value="<?php echo image_gen_convert_array_to_hex( $defaults['fontcolor'] ); ?>" name="gen[fontcolor]" type="text" class="colorpicker" /></label></p>
	<p>
	<?php submit_button( __( 'Generate', 'image-gen' ), 'primary', 'submit', false ); ?>
	<?php submit_button( __( 'Save options as default', 'image-gen' ), 'small', 'save-defaults', false ); ?>
	</p>
	</form>

	</div><?php
}

/**
 * Convert RGB array to Hex Code
 *
 * @param array $input Array
 * @return string Proper hex value
 */
function image_gen_convert_array_to_hex( $input ) {
	$input = array_map( 'dechex', $input );
	foreach( $input as $k => $v)
		$input[ $k ] = zeroise( $v, 2 );
	return '#'.implode($input);
}

/**
 * Convert Hex Code to RGB array
 *
 * @param string $input hex code
 * @return array RGB values
 */
function image_gen_convert_hex_to_array( $input ) {

	$input = str_replace('#', '', $input );
	// assuming a 6 digit hex, divide into array
	$hex_array = str_split( $input, 2 );
	// convert to decimal value
	return array_map('hexdec', $hex_array );

}

/**
 * Convert Grey value to RGB array
 *
 * @param string $input hex code
 * @return array RGB values
 */
function image_gen_convert_grey_to_array( $input ) {

	return array( $input, $input, $input );

}

/**
 * Ajax Callback
 *
 * Fix POST args as needed, Save as default.
 *
 * @return void
 */
function image_gen__image_gen_cb() {
	parse_str( $_POST['args'], $args );
	$args = $args['gen'];

	// we separate our title from the rest here
	$title = empty( $args['title'] ) ? 'image' : $args['title'];
	unset( $args['title'] );

	// coming from ajax, we'll have our color in the wrong format - fix it here
	$args['fontcolor'] = image_gen_convert_hex_to_array( $args['fontcolor'] );

	$id = image_gen__create_image( $title, $args );
	$img = wp_get_attachment_image( $id, 'full' );
	$img = str_replace( '.png', '.png?'.uniqid(), $img );

	wp_send_json_success( $img );
}
add_action( 'wp_ajax_image_gen', 'image_gen__image_gen_cb' );

/**
 * Ajax Callback
 *
 * Fix POST args as needed, pass along to creation function
 * Send back img html
 *
 * @return void
 */
function image_gen__image_gen_defaults_cb() {
	parse_str( $_POST['args'], $args );
	$args = $args['gen'];

	// we separate our title from the rest here
	$title = $args['title'];
	unset( $args['title'] );

	// coming from ajax, we'll have our color in the wrong format - fix it here
	$args['fontcolor'] = image_gen_convert_hex_to_array( $args['fontcolor'] );

	$args = wp_parse_args( $args, image_gen__get_defaults() );

	update_option( 'image_gen_defaults', $args );

	wp_send_json_success( $args );
}
add_action( 'wp_ajax_image_gen_defaults', 'image_gen__image_gen_defaults_cb' );

/**
 * Create Image
 *
 * Creates image and saves to WP
 *
 * @param string $title Title of image in Media Library
 * @param array $args Array of rules for the generated image
 * @return int ID of image
 */
function image_gen__create_image( $title, $args=array() ) {

	$name = sanitize_title( $title ). '.png';

	$path = image_gen__build_image( $args );
	$id = image_gen__move_to_wp( $path, $name, $title );

	return $id;
}

/**
 * Move image to WP Media Library
 *
 * @param string $path Path to image
 * @param string $name Filename for moved image
 * @param string $title Title of image in Media Library
 * @return int ID of image
 */
function image_gen__move_to_wp( $path, $name, $title ) {

	$file_array = array();
	$file_array['tmp_name'] = $path;
	$file_array['name'] = $name;

	$image = wp_handle_sideload( $file_array, array('test_form'=>false) );

	$id = wp_insert_attachment( array('post_title' => $title, 'guid' => $image['url'], 'post_mime_type' => $image['type'] ), $image['file'] );

	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $image['file'] ) );

	return $id;

}

/**
 * Build image
 *
 * @param array $args Array of rules for the generated image
 *
 * [--width=<width>]
 * : Width for the image in pixels, default 150
 *
 * [--height=<height>]
 * : Height for the image in pixels, default 150
 *
 * [--lowgrey=<lowgrey>]
 * : Lower grey value (0-255), default 120
 *
 * [--highgrey=<highgrey>]
 * : Higher grey value (0-255), default 150
 *
 * [--alpha=<alpha>]
 * : Alpha transparancy (0-127), default 0
 *
 * [--blurintensity=<blurintensity>]
 * : How often to apply the blur effect, default 2
 *
 * [--filename=<filename>]
 * : old value
 *
 * [--text=<text>]
 * : Text to place on the image, default empty
 *
 * [--linespacing=<linespacing>]
 * : Linespacing in pixels, default 10
 *
 * [--textsize=<textsize>]
 * : Text size in pixels, default 40
 *
 * [--font=<font>]
 * : Path to font true type file, default {plugin-path}/fonts/SourceSansPro-BoldIt.otf
 *
 * [--fontcolor=<fontcolor>]
 * : Font color. Either RGB as an array or a hexcode string, default array(0, 80, 80),
 *
 * @return string Path of generated image
 */
function image_gen__build_image( $args = array() ) {

	$args = wp_parse_args( $args, image_gen__get_defaults() );

	$args = apply_filters( 'image_gen_build_args', $args );

	// image
	$args['width'] = intval( $args['width'] );
	$args['height'] = intval( $args['height'] );
	$args['lowgrey'] = intval( $args['lowgrey'] );
	$args['highgrey'] = intval( $args['highgrey'] );
	$args['alpha'] = intval( $args['alpha'] );
	$args['blurintensity'] = intval( $args['blurintensity'] );
	if ( count( explode('.', $args['filename'] ) ) < 2 ) $args['filename'] .= '.png';
	$wp_upload_dir = wp_upload_dir();
	$args['filename'] = trailingslashit( $wp_upload_dir['path'] ) . $args['filename'];

	// text
	$args['text'] = is_array( $args['text'] ) ? $args['text'] : explode( "\n", $args['text'] );
	$args['text'] = array_map( 'trim', $args['text'] );
	$args['text'] = array_filter( $args['text'] );
	$args['linespacing'] = intval( $args['linespacing'] );
		if ( count( $args['text'] ) < 2 ) $args['linespacing'] = 0;
	$args['textsize'] = intval( $args['textsize'] );
	$args['font'] = $args['font'];

	$args['fontcolor'] = is_array( $args['fontcolor'] ) ? $args['fontcolor'] : image_gen_convert_hex_to_array( $args['fontcolor'] );
	list( $fontcolorR, $fontcolorG, $fontcolorB ) = array_map( 'intval', $args['fontcolor'] );

	extract( $args );

	// alright, lets make an image
	$im = imagecreatetruecolor( $width, $height );

	// allow alpha transparency
	imagealphablending($im, false);
	imagesavealpha($im, true);

	// make base image transparent
	$black = imagecolorallocate( $im, 0, 0, 0 );
	imagecolortransparent( $im, $black );

	// add noise. pixel by pixel
	$im = apply_filters_ref_array( 'image_gen_image', array( &$im, $args ) );

	for( $i = 1; $i < $blurintensity; $i++ )
		imagefilter( $im, IMG_FILTER_GAUSSIAN_BLUR );

	$textcolor = imagecolorallocate( $im, $fontcolorR, $fontcolorG, $fontcolorB );

	$angle = 0;

	$boxes = array(); // text box boundaries

	// we're stacking multiple lines - get the total height
	$total_textbox_height = 0;
	foreach( $text as $t ) {
		// https://gist.github.com/trepmal/7940059
		$_box = imageftbbox( $textsize, $angle, $font, $t );
		$boxes[] = $_box;

		$total_textbox_height += $_box[3] - $_box[5] + $linespacing;
	}

	// now go back through each line, and place it on the image
	$tth = $total_textbox_height;
	foreach ( $text as $k => $t ) {
		$_box = $boxes[ $k ]; // our bounding boxes were calculated above, just retrieve them

		$box_width = $_box[4] - $_box[6];
		$box_height = $_box[3] - $_box[5] + $linespacing;
		$tth -= $box_height;

		$from_side = ($width - $box_width)/2;
		// magic math to get vertical centering
		$from_top = ($height + $total_textbox_height)/2  - $tth - $linespacing/2;

		// add text to image
		imagealphablending($im, true); // must be set to make sure font renders properly
		imagettftext( $im, $textsize, $angle, $from_side, $from_top, $textcolor, $font, $t );

	}

	imagepng( $im, $filename );
	imagedestroy( $im );
	return $filename;
}


/*
	Sample image filters below. For example

		`add_filter( 'image_gen_image', 'image_gen_style_noisy_image', 10, 2 );`

	Use only one at a time.
*/

/**
 * Filter callback. Make the image background noisy
 *
 * @param image resource $im GD Image Resource
 * @param array $args Arguments for image creation
 * @return GD image resource
 */
function image_gen_style_noisy_image( $im, $args ) {

		for( $i = 0; $i < $args['width']; $i++ ) {
			for ($j = 0; $j < $args['height']; $j++ ) {
				$rand = rand( $args['lowgrey'], $args['highgrey'] ); // grey
				$color = imagecolorallocatealpha( $im, $rand, $rand, $rand, $args['alpha'] );
				imagesetpixel( $im, $i, $j, $color );
			}
		}
	return $im;
}
// add_filter( 'image_gen_image', 'image_gen_style_noisy_image', 10, 2 );

/**
 * Filter callback. Give image background random vertical stripes
 *
 * @param image resource $im GD Image Resource
 * @param array $args Arguments for image creation
 * @return GD image resource
 */
function image_gen_style_random_vert_stripes_image( $im, $args ) {

		for( $i = 0; $i < $args['width']; $i++ ) {
			$rand = rand( $args['lowgrey'], $args['highgrey'] ); // grey
			for ($j = 0; $j < $args['height']; $j++ ) {
				$color = imagecolorallocatealpha( $im, $rand, $rand, $rand, $args['alpha'] );
				imagesetpixel( $im, $i, $j, $color );
			}
		}
	return $im;
}
// add_filter( 'image_gen_image', 'image_gen_style_random_vert_stripes_image', 10, 2 );

/**
 * Filter callback. Give image background random horizontal stripes
 *
 * @param image resource $im GD Image Resource
 * @param array $args Arguments for image creation
 * @return GD image resource
 */
function image_gen_style_random_horz_stripes_image( $im, $args ) {

		for( $i = 0; $i < $args['height']; $i++ ) {
			$rand = rand( $args['lowgrey'], $args['highgrey'] ); // grey
			for ($j = 0; $j < $args['width']; $j++ ) {
				$color = imagecolorallocatealpha( $im, $rand, $rand, $rand, $args['alpha'] );
				imagesetpixel( $im, $j, $i, $color );
			}
		}
	return $im;
}
// add_filter( 'image_gen_image', 'image_gen_style_random_horz_stripes_image', 10, 2 );

/**
 * Filter callback. Give image background dark-to-light horizontal gradient
 *
 * @param image resource $im GD Image Resource
 * @param array $args Arguments for image creation
 * @return GD image resource
 */
function image_gen_style_low_to_high_grady_image( $im, $args ) {

	if ( $args['lowgrey'] == $args['highgrey'] ) {
		return image_gen_style_solid_image( $im, $args );
	}

	for( $i = 0; $i < $args['height']; $i++ ) {
		$grey = $args['lowgrey'] - ( ( ( $args['lowgrey'] - $args['highgrey'] ) / $args['width'] ) * $i );
		for ($j = 0; $j < $args['width']; $j++ ) {
			$color = imagecolorallocatealpha( $im, $grey, $grey, $grey, $args['alpha'] );
			imagesetpixel( $im, $j, $i, $color );
		}
	}

	return $im;
}
add_filter( 'image_gen_image', 'image_gen_style_low_to_high_grady_image', 10, 2 );

/**
 * Filter callback. Give image solid background
 *
 * @param image resource $im GD Image Resource
 * @param array $args Arguments for image creation
 * @return GD image resource
 */
function image_gen_style_solid_image( $im, $args ) {

	// for( $i = 0; $i < $args['height']; $i++ ) {
	// 	$grey = $args['highgrey'] - ( ( ( $args['highgrey'] - $args['lowgrey'] ) / $args['height'] ) * $i );
	// 	for ($j = 0; $j < $args['width']; $j++ ) {
	// 		$color = imagecolorallocatealpha( $im, $grey, $grey, $grey, $args['alpha'] );
	// 		imagesetpixel( $im, $j, $i, $color );
	// 	}
	// }
	$color = imagecolorallocatealpha( $im, $args['lowgrey'], $args['lowgrey'], $args['lowgrey'], $args['alpha'] );
	imagefilledrectangle( $im, 0, 0, $args['width'], $args['height'], $color );

	return $im;
}
// add_filter( 'image_gen_image', 'image_gen_style_solid_image', 10, 2 );

/**
 * Filter callback. Give image background light-to-dark horizontal gradient
 *
 * @param image resource $im GD Image Resource
 * @param array $args Arguments for image creation
 * @return GD image resource
 */
function image_gen_style_high_to_low_grady_image( $im, $args ) {

	for( $i = 0; $i < $args['height']; $i++ ) {
		$grey = $args['highgrey'] - ( ( ( $args['highgrey'] - $args['lowgrey'] ) / $args['height'] ) * $i );
		for ($j = 0; $j < $args['width']; $j++ ) {
			$color = imagecolorallocatealpha( $im, $grey, $grey, $grey, $args['alpha'] );
			imagesetpixel( $im, $j, $i, $color );
		}
	}

	return $im;
}
// add_filter( 'image_gen_image', 'image_gen_style_high_to_low_grady_image', 10, 2 );

/**
 * Filter callback. Give image background colored vertical gradient
 *
 * NOTE: the default generator form doesn't (yet) offer a color field for this
 *       so the color must be set manually or you'll still end up with grey
 *       you can uncomment and play with the randomly selected colors
 *
 * @param image resource $im GD Image Resource
 * @param array $args Arguments for image creation
 * @return GD image resource
 */
function image_gen_color_grady_vert_image( $im, $args ) {

	$top = $args['lowgrey'];
	// $top = array( rand(0,255), rand(0,255), rand(0,255) );
	$top = is_array( $top ) ? $top : image_gen_convert_grey_to_array( $top );

	$bottom = $args['highgrey'];
	// $bottom = array( rand(0,255), rand(0,255), rand(0,255) );
	$bottom = is_array( $bottom ) ? $bottom : image_gen_convert_grey_to_array( $bottom );

	$w = $args['width'];
	$h = $args['height'];

	for( $x=0; $x <= $h; $x++ ) { // go down

		$r = $top[0] - ( ( ( $top[0] - $bottom[0] ) / $h ) * $x );
		$g = $top[1] - ( ( ( $top[1] - $bottom[1] ) / $h ) * $x );
		$b = $top[2] - ( ( ( $top[2] - $bottom[2] ) / $h ) * $x );

		for( $y=0; $y <= $w; $y++) { // and across
			$col=imagecolorallocate( $im, $r, $g, $b );
			imagesetpixel( $im, $y-1, $x-1, $col );
		}
	}
	return $im;
}
// add_filter( 'image_gen_image', 'image_gen_color_grady_vert_image', 10, 2 );

/**
 * Filter callback. Give image background colored horizontal gradient
 *
 * NOTE: the default generator form doesn't (yet) offer a color field for this
 *       so the color must be set manually or you'll still end up with grey
 *       you can uncomment and play with the randomly selected colors
 *
 * @param image resource $im GD Image Resource
 * @param array $args Arguments for image creation
 * @return GD image resource
 */
function image_gen_color_grady_horz_image( $im, $args ) {

	$left = $args['lowgrey'];
	// $left = array( rand(0,255), rand(0,255), rand(0,255) );
	$left = array(200, 20, 20 );
	$left = is_array( $left ) ? $left : image_gen_convert_grey_to_array( $left );

	$right = $args['highgrey'];
	// $right = array( rand(0,255), rand(0,255), rand(0,255) );
	$right = array(20, 200, 20 );
	$right = is_array( $right ) ? $right : image_gen_convert_grey_to_array( $right );

	$w = $args['width'];
	$h = $args['height'];

	for( $x=0; $x <= $w; $x++ ) { // go down

		$r = $left[0] - ( ( ( $left[0] - $right[0] ) / $w ) * $x );
		$g = $left[1] - ( ( ( $left[1] - $right[1] ) / $w ) * $x );
		$b = $left[2] - ( ( ( $left[2] - $right[2] ) / $w ) * $x );

		for( $y=0; $y <= $h; $y++) { // and across
			$col=imagecolorallocate( $im, $r, $g, $b );
			imagesetpixel( $im, $x-1, $y-1, $col );
		}
	}
	return $im;
}
// add_filter( 'image_gen_image', 'image_gen_color_grady_horz_image', 10, 2 );

if ( defined('WP_CLI') && WP_CLI ) {
	include plugin_dir_path( __FILE__ ) . '/image-gen-cli.php';
}