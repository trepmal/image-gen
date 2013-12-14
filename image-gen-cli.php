<?php

/**
 * Generate Images
 */
class Image_Gen_CLI extends WP_CLI_Command {

	/**
	 * Generate an Image
	 *
	 * ## OPTIONS
	 *
	 * <title>
	 * : The title for the image, as will appear in the Media Library
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
 	 *
	 * ## EXAMPLES
	 *
	 *     wp image-gen create "CLI Image" --text="Fancy That" --width=400 --fontcolor=c0ffee
	 *
	 */
	public function create( $args = array(), $assoc_args = array() ) {
		list( $title ) = $args;

		$id = image_gen__create_image( $title, $assoc_args );

		WP_CLI::success( "Image ID $id created.");

	}



}

WP_CLI::add_command( 'image-gen', 'Image_Gen_CLI' );