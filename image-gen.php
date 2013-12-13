<?php
/*
 * Plugin Name: Image Gen
 * Plugin URI: trepmal.com
 * Description:
 * Version:
 * Author: Kailey Lampert
 * Author URI: kaileylampert.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * TextDomain: image-gen
 * DomainPath:
 * Network: false
 */

$image_gen = new Image_Gen();

class Image_Gen {

	function __construct() {
		add_action( 'admin_menu', array( &$this, 'menu' ) );
		add_action( 'wp_ajax_image_gen', array( &$this, 'image_gen_cb' ) );

	$this->defaults = array(
			'width' => 150,
			'height' => 150,
			'lowgrey' => 120,
			'highgrey' => 150,
			'alpha' => 0,
			'blurintensity' => 2,
			'filename' => uniqid(),

			'text' => array(),
			'linespacing' => 10,
			'textsize' => 40,
			'font' => plugin_dir_path( __FILE__ ) . '/fonts/SourceSansPro-BoldIt.otf',
			'fontcolor' => array(0, 80, 80),
	);

	}

	function menu() {
		add_options_page( __( 'Image Gen', 'image-gen' ), __( 'Image Gen', 'image-gen' ), 'edit_posts', __CLASS__, array( &$this, 'page' ) );
	}

	function page() {
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'image-gen', plugins_url('image-gen.js', __FILE__ ), array('jquery', 'wp-color-picker') );
		?><div class="wrap">
		<h2><?php _e( 'Image Gen', 'image-gen' ); ?></h2>

		<form method="post" id="image-gen">
		<p><label>Title<input value="" name="gen[title]" type="text" /></label></p>
		<p><label>Text</label><textarea name="gen[text]"></textarea></p>
		<p><label>Width<input value="<?php echo $this->defaults['width']; ?>" name="gen[width]" type="number" min="0" /></label></p>
		<p><label>Height<input value="<?php echo $this->defaults['height']; ?>" name="gen[height]" type="number" min="0" /></label></p>
		<p><label>Low Grey<input value="<?php echo $this->defaults['lowgrey']; ?>" name="gen[lowgrey]" type="number" min="0" max="255" /></label></p>
		<p><label>High Grey<input value="<?php echo $this->defaults['highgrey']; ?>" name="gen[highgrey]" type="number" min="0" max="255" /></label></p>
		<p><label>Blur Intensity<input value="<?php echo $this->defaults['blurintensity']; ?>" name="gen[blurintensity]" type="number" min="0" /></label></p>
		<p><label>Alpha<input value="<?php echo $this->defaults['alpha']; ?>" name="gen[alpha]" type="number" min="0" max="127"/></label></p>

		<p><label>Size<input value="<?php echo $this->defaults['textsize']; ?>" name="gen[textsize]" type="number" min="0" /></label></p>
		<p><label>Linespacing<input value="<?php echo $this->defaults['linespacing']; ?>" name="gen[linespacing]" type="number" /></label></p>
		<p><label>Font<select name="gen[font]"><?php

		$fontlist = glob( plugin_dir_path(__FILE__).'/fonts/*.otf' );
		// allow separate plugins to add/edit fonts. Should be True Type.
		$fontlist = apply_filters( 'image_gen_fontlist', $fontlist );

		foreach( $fontlist as $font ) {
			$f = basename($font);

			$s = selected( $font, $this->defaults['font'], false );
			echo "<option value='$font'$s>$f</option>";
		}
		?></select></label></p>
		<p><label>Font color <input value="<?php echo $this->_convert_array_to_hex( $this->defaults['fontcolor'] ); ?>" name="gen[fontcolor]" type="text" class="colorpicker" /></label></p>
		<?php submit_button(); ?>
		</form>

		</div><?php
	}

	/**
	 * Convert RGB array to Hex Code
	 *
	 * @param array $input Array
	 * @return string Proper hex value
	 */
	function _convert_array_to_hex( $input ) {
		$input = array_map( 'dechex', $input );
		foreach( $input as $k => $v)
			$input[ $k ] = zeroise( $v, 2 );
		return '#'.implode($input);
	}

	/**
	 * Convert Hex Code array to RGB array
	 *
	 * @param string $input hex code
	 * @return array RGB values
	 */
	function _convert_hex_to_array( $input ) {

		$input = str_replace('#', '', $input );
		// assuming a 6 digit hex, divide into array
		$hex_array = str_split( $input, 2 );
		// convert to decimal value
		return array_map('hexdec', $hex_array );

	}

	/**
	 * Ajax Callback
	 *
	 * Fix POST args as needed, pass along to creation function
	 * Send back img html
	 *
	 * @return void
	 */
	function image_gen_cb() {
		parse_str( $_POST['args'], $args );
		$args = $args['gen'];

		// we separate our title from the rest here
		$title = $args['title'];
		unset( $args['title'] );

		// coming from ajax, we'll have our color in the wrong format - fix it here
		$args['fontcolor'] = $this->_convert_hex_to_array( $args['fontcolor'] );

		$id = $this->create_image( $title, $args );
		$img = wp_get_attachment_image( $id, 'full' );

		wp_send_json_success( $img );
	}

	/**
	 * Create Image
	 *
	 * @param string $title Title of image in Media Library
	 * @param array $args Array of rules for the generated image
	 * @return int ID of image
	 */
	function create_image( $title, $args=array() ) {

		$name = sanitize_title( $title ). '.png';

		$path = $this->generate_noise( $args );
		$id = $this->move_to_wp( $path, $name, $title );

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
	function move_to_wp( $path, $name, $title ) {

		$file_array = array();
		$file_array['tmp_name'] = $path;
		$file_array['name'] = $name;

		$image = wp_handle_sideload( $file_array, array('test_form'=>false) );

		$id = wp_insert_attachment( array('post_title' => $title, 'guid' => $image['url'], 'post_mime_type' => $image['type'] ), $image['file'] );

		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $image['file'] ) );

		return $id;

	}

	/* function get_image_args( $args ) {
		$args = wp_parse_args( $args, $this->defaults );


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
		$args['text'] = array_map( 'trim', $text );
		$args['text'] = array_filter( $text );
		$args['linespacing'] = intval( $args['linespacing'] );
			if ( count( $text ) < 2 ) $args['linespacing'] = 0;
		$args['textsize'] = intval( $args['textsize'] );
		$args['font'] = $args['font'];

		return $args;

	} */

	/**
	 * Generate a noisy image
	 *
	 * @param array $args Array of rules for the generated image
	 * @return string Path of generated image
	 */
	function generate_noise( $args = array() ) {

		$args = wp_parse_args( $args, /*array(
			'width' => 150,
			'height' => 150,
			'lowgrey' => 120,
			'highgrey' => 150,
			'blurintensity' => 2,
			'filename' => uniqid(),

			'text' => array(),
			'linespacing' => 10,
			'textsize' => 40,
			'font' => plugin_dir_path( __FILE__ ) . '/SourceSansPro-Black.otf',
			'fontcolor' => array(0, 0, 0),
		)*/ $this->defaults );


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
		// for( $i = 0; $i < $width; $i++ ) {
		// 	for ($j = 0; $j < $height; $j++ ) {
		// 		$rand = rand( $lowgrey, $highgrey ); // grey
		// 		$color = imagecolorallocatealpha( $im, $rand, $rand, $rand, $alpha );
		// 		imagesetpixel( $im, $i, $j, $color );
		// 	}
		// }

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

}


/*
	Different Image Filters, below!
	Use only one at a time
*/


// add_filter( 'image_gen_image', 'noisy_image', 10, 2 );
function noisy_image( $im, $args ) {

		for( $i = 0; $i < $args['width']; $i++ ) {
			for ($j = 0; $j < $args['height']; $j++ ) {
				$rand = rand( $args['lowgrey'], $args['highgrey'] ); // grey
				$color = imagecolorallocatealpha( $im, $rand, $rand, $rand, $args['alpha'] );
				imagesetpixel( $im, $i, $j, $color );
			}
		}
	return $im;
}

// add_filter( 'image_gen_image', 'random_vert_stripes_image', 10, 2 );
function random_vert_stripes_image( $im, $args ) {

		for( $i = 0; $i < $args['width']; $i++ ) {
			$rand = rand( $args['lowgrey'], $args['highgrey'] ); // grey
			for ($j = 0; $j < $args['height']; $j++ ) {
				// $rand = rand( $args['lowgrey'], $args['highgrey'] ); // grey
				$color = imagecolorallocatealpha( $im, $rand, $rand, $rand, $args['alpha'] );
				imagesetpixel( $im, $i, $j, $color );
			}
		}
	return $im;
}

// add_filter( 'image_gen_image', 'random_horz_stripes_image', 10, 2 );
function random_horz_stripes_image( $im, $args ) {

		for( $i = 0; $i < $args['height']; $i++ ) {
			$rand = rand( $args['lowgrey'], $args['highgrey'] ); // grey
			for ($j = 0; $j < $args['width']; $j++ ) {
				// $rand = rand( $args['lowgrey'], $args['highgrey'] ); // grey
				$color = imagecolorallocatealpha( $im, $rand, $rand, $rand, $args['alpha'] );
				imagesetpixel( $im, $j, $i, $color );
			}
		}
	return $im;
}

// add_filter( 'image_gen_image', 'low_to_high_grady_image', 10, 2 );
function low_to_high_grady_image( $im, $args ) {

	$greydiff = $args['highgrey'] - $args['lowgrey'];

	$greyheight = ceil( $args['height'] / $greydiff );

		$grey = $args['lowgrey'];
		for( $i = 0; $i < $args['height']; $i++ ) {
			if ( $i > 0 && $i % $greyheight == 0 ) {
				++$grey;
			}
			for ($j = 0; $j < $args['width']; $j++ ) {
				// $grey = rand( $args['lowgrey'], $args['highgrey'] ); // grey
				$color = imagecolorallocatealpha( $im, $grey, $grey, $grey, $args['alpha'] );
				imagesetpixel( $im, $j, $i, $color );
			}
		}
	return $im;
}

add_filter( 'image_gen_image', 'high_to_low_grady_image', 10, 2 );
function high_to_low_grady_image( $im, $args ) {

	$greydiff = $args['highgrey'] - $args['lowgrey'];

	$greyheight = ceil( $args['height'] / $greydiff );

		$grey = $args['highgrey'];
		for( $i = 0; $i < $args['height']; $i++ ) {
			if ( $i > 0 && $i % $greyheight == 0 ) {
				--$grey;
			}
			for ($j = 0; $j < $args['width']; $j++ ) {
				// $grey = rand( $args['lowgrey'], $args['highgrey'] ); // grey
				$color = imagecolorallocatealpha( $im, $grey, $grey, $grey, $args['alpha'] );
				imagesetpixel( $im, $j, $i, $color );
			}
		}
	return $im;
}
