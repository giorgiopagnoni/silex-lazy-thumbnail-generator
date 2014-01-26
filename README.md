Lazy thumbnail generator for Silex
==================================

Create on-demand thumbnail from existing image with [image-workshop][1] library

How to use
----------
If you have a directory in your web root, called images, with a lot of images,
then you can use this provider to create (and optionally save) the thumbnail from the original images.

for better performance, you can turn off on the fly image processing, then the first time
when there is no thumbnail image is available in the target directory, Silex take care of the request,
next time, when there is an image there, the web-server (whatever it is) serve the file.
(Normally the rewrite is done when the original file is not available)

```php

$app->register(
    new \Cybits\Silex\Provider\LazyThumbnailGenerator()
    );

$app['lazy.thumbnail.mount_paths'] = array(
            //Each key for one mount point
            '/images' => array(
                //Allowed extension for file, comma separated
                'allowed_ext' => 'jpg,jpeg,png',
                //array of allowed size for generating thumbnail,
                // In width.height format. * means any size
                'allowed_size' => array('*.*'),
                //Max size allowed. optional, but take care!
                'max_size' => '512.512',
                // Do not save the cache, default
                'on_the_fly' => true,
                //Route name to bind
                'route_name' => 'lazy_image_thumbnail_images'
            )
        );
// If the web root is different thn the index.php directory then absolute path to the web root is required

//$app['lazy.thumbnail.web_root'] = '/path/to/web/root'
```
lazy.thumbnail.mount_paths
--------------------------

Array of mounts, each mount need a key here, the key is real folder name in web root with '/' prefix.

```allowed_ext``` is the comma separated allowed extension. defaults are ```jpg,jpeg,png,gif```

```allowed_size``` is array of allowed size in width.height format. for example for allowing the ```256x256``` and
```512x512``` you should use ```array('256.256', '512.512')``` for any size you can use ```*``` for example ```100.*```
means any request for width 100 is allowed.
also there is an optional parameter for maximum allowed size is available, ```max_size``` is the
```max_width.max_height```  .

REMEMBER: its important to prevent access to any size. higher size, means more memory usage and its dangerous.

if you want to use the caching mechanism, then use the ```on_the_fly``` and set it to true (default is false)
for using this route with UrlGeneratorServiceProvider, you can assign a name to this route with ```route_name```

lazy.thumbnail.web_root
-----------------------

Normally the web root is the index.php location, but if not (in case of using php-fpm it could happen)
set the full path of web root to this parameter.

Calling the route
-----------------

This is simple, assuming you have an ```/images``` route (and folder in web root) and there is an image with name
```image.jpg```  in that folder and you are allowed to use jpg image and the ```256x256``` size is available,
then fetching this route:

```
http://example.com/images/265x256/image.jpg
```

show a ```256x256``` thumbnail from the original image. if you turn off the on the fly option then the image is
created in ```/path/to/web/root/images/256x256/image.jpg``` any new request to this file, is handled by the web server
now.

How to contribute?
------------------

Pull request are more than welcome, specially on this README :)

Credits
-------

The original idea is from this: [cgray/nailed][2]

[1]: http://phpimageworkshop.com
[2]: https://github.com/cgray/nailed