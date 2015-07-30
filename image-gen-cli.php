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


	/**
	 * Attach Images
	 *
	 * ## OPTIONS
	 *
	 * <count>
	 * : Number of posts to attach an image to. Default 100
	 *
	 * [--post-type=<post-type>]
	 * : Post type to attach images to. Default 'post'
	 *
	 * [--order=<order>]
	 * : Order used to get the posts to attach images to.
	 * Options - DESC, ASC, RAND. Default random order.
	 *
	 * [--size=<size>]
	 * : Image size, either thumbnail, medium, large, full, random or size set with add_image_size().
	 * Default none (uses the post thumbnail size set by theme).
	 *
	 * [--include-attached]
	 * : Include already attached images to attach to posts.
	 *
	 * [--insert]
	 * : Insert image in post content instead of adding as a post thumbnail.
	 *
	 * [--linkto=<linkto>]
	 * : Link to file (size) or attachment page if --insert is used
	 * Options - 'file', 'post' or 'none'. Default file.
	 *
	 * [--align=<align>]
	 * : Aligment of image if --insert is used.
	 * Options - 'center', 'left', 'right', 'random'. Default none.
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     wp image-gen attach 10 --align=random  --size=random
	 *
	 */
	public function attach( $args = array(), $assoc_args = array() ) {
		list( $count ) = $args;

		image_gen__attach_images( $count, $assoc_args );
	}
}

WP_CLI::add_command( 'image-gen', 'Image_Gen_CLI' );