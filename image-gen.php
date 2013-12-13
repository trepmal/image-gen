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


		$wp_upload_dir = wp_upload_dir();


		// image
		$width = intval( $args['width'] );
		$height = intval( $args['height'] );
		$lowgrey = intval( $args['lowgrey'] );
		$highgrey = intval( $args['highgrey'] );
		$alpha = intval( $args['alpha'] );
		$blurintensity = intval( $args['blurintensity'] );
		if ( count( explode('.', $args['filename'] ) ) < 2 ) $args['filename'] .= '.png';
		$filename = trailingslashit( $wp_upload_dir['path'] ) . $args['filename'];

		// text
		$text = is_array( $args['text'] ) ? $args['text'] : explode( "\n", $args['text'] );
		$text = array_map( 'trim', $text );
		$text = array_filter( $text );
		$linespacing = intval( $args['linespacing'] );
			if ( count( $text ) < 2 ) $linespacing = 0;
		$textsize = intval( $args['textsize'] );
		$font = $args['font'];

		list( $fontcolorR, $fontcolorG, $fontcolorB ) = array_map( 'intval', $args['fontcolor'] );

		// alright, lets make an image
		$im = imagecreatetruecolor( $width, $height );

		// make base image transparent
		$black = imagecolorallocate( $im, 0, 0, 0 );
		imagecolortransparent( $im, $black );

		// add noise. pixel by pixel
		for( $i = 0; $i < $width; $i++ ) {
			for ($j = 0; $j < $height; $j++ ) {
				$rand = rand( $lowgrey, $highgrey ); // grey
				$color = imagecolorallocatealpha( $im, $rand, $rand, $rand, $alpha );
				imagesetpixel( $im, $i, $j, $color );
			}
		}

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
			$from_top = ($height + $total_textbox_height)/2 - ($tth - $box_height/2) - $textsize/2;
			// $tth -= $box_height;

			// add text to image
			imagettftext( $im, $textsize, $angle, $from_side, $from_top, $textcolor, $font, $t );

		}

		// header('Content-Type: image/png');
		imagepng( $im, $filename );
		return $filename;
	}

}

if ( ! function_exists( 'printer') ) {
	function printer( $input ) {
		echo '<pre>' . print_r( $input, true ) . '</pre>';
	}
}