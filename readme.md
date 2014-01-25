Image Gen
=========

This was created from a half-formed idea I had. As such, it doesn't have a clear use as-is, but should be extendable should I or you get around to that.

Essentially, you can fill in some options and generate an image. The image will then be added to the Media Library.

This image was actually generated:

![actual generated image](example-1.png)

This one too:

![actual generated image](example-2.png)

The currently-ugly options screen:

![options, yes they're ugly](options.png)

```
SYNOPSIS

  wp image-gen create <title> [--width=<width>] [--height=<height>]
[--lowgrey=<lowgrey>] [--highgrey=<highgrey>] [--alpha=<alpha>]
[--blurintensity=<blurintensity>] [--filename=<filename>] [--text=<text>]
[--linespacing=<linespacing>] [--textsize=<textsize>] [--font=<font>]
[--fontcolor=<fontcolor>]

OPTIONS

  <title>
    The title for the image, as will appear in the Media Library

  [--width=<width>]
    Width for the image in pixels, default 150

  [--height=<height>]
    Height for the image in pixels, default 150

  [--lowgrey=<lowgrey>]
    Lower grey value (0-255), default 120

  [--highgrey=<highgrey>]
    Higher grey value (0-255), default 150

  [--alpha=<alpha>]
    Alpha transparancy (0-127), default 0

  [--blurintensity=<blurintensity>]
    How often to apply the blur effect, default 2

  [--filename=<filename>]
    old value

  [--text=<text>]
    Text to place on the image, default empty

  [--linespacing=<linespacing>]
    Linespacing in pixels, default 10

  [--textsize=<textsize>]
    Text size in pixels, default 40

  [--font=<font>]
    Path to font true type file, default
    {plugin-path}/fonts/SourceSansPro-BoldIt.otf

  [--fontcolor=<fontcolor>]
    Font color. Either RGB as an array or a hexcode string, default array(0,
    80, 80),


EXAMPLES

    wp image-gen create "CLI Image" --text="Fancy That" --width=400 --fontcolor=c0ffee
```