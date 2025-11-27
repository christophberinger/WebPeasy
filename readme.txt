=== WebPeasy ===
Contributors: beringer
Tags: webp, images, performance, optimization, compression
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically deliver frontend images as WebP with PHP-based URL rewriting, while leaving the Media Library and original files untouched.

== Description ==

WebPeasy is a production-ready WordPress solution that automatically converts all frontend images to WebP format while leaving your Media Library and original files completely untouched.

= Key Features =

* **Non-Destructive**: Original files remain unchanged in your Media Library
* **Automatic Conversion**: All generated image sizes (thumbnails, medium, large, custom) are created as WebP
* **Built-in Regeneration**: Bulk regenerate all existing images with real-time progress tracking
* **Smart URL Rewriting**: PHP-based output buffering automatically serves WebP versions
* **Universal Compatibility**: Works on any server (Apache, Nginx, LiteSpeed) - no .htaccess needed
* **Browser Detection**: Automatically serves WebP only to browsers that support it
* **Global Quality Control**: Single slider to configure WebP compression quality (0-100)
* **Smart Detection**: Automatically detects WebP support in your PHP environment
* **Graceful Fallback**: Works seamlessly on servers without WebP support

= How It Works =

The plugin uses WordPress's built-in `image_editor_output_format` filter to convert newly uploaded images to WebP format. For existing images, it uses PHP output buffering to automatically replace image URLs with WebP versions when:

1. The browser supports WebP (detected via HTTP Accept header)
2. A WebP version of the image exists on the server
3. The request is for a frontend page (not admin/AJAX/cron)

Original JPG/PNG files are preserved alongside WebP versions, providing automatic fallback for older browsers.

= Built-in Thumbnail Regeneration =

Navigate to Settings → WebPeasy and use the built-in regeneration tool to convert all existing images:

* Real-time progress tracking
* Batch processing (5 images at a time)
* No timeout issues
* Cancel anytime
* Preserves original files

= Requirements =

* WordPress 5.0 or higher
* PHP 7.0 or higher
* ImageMagick or GD with WebP support

== Installation ==

1. Upload the `webpeasy` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → WebPeasy to configure
4. (Optional) Click "Regenerate Thumbnails" to convert existing images

== Frequently Asked Questions ==

= Will this slow down my site? =

No. Conversion happens during upload, not on page load. The PHP URL rewriting adds minimal overhead (~10-20ms) but the smaller WebP files load much faster, resulting in net performance gains.

= Does it work on Nginx/LiteSpeed? =

Yes! Unlike .htaccess-based solutions, this plugin works on any server because it uses PHP output buffering.

= Do I need to delete my old images? =

No. Original JPG/PNG files are preserved alongside WebP versions. This provides automatic fallback for older browsers.

= What if my host doesn't support WebP? =

The plugin will detect this and gracefully do nothing. No errors, no broken images.

= Will this work with caching plugins? =

Yes. The rewriting happens before page caching, so cached pages will already have WebP URLs. Make sure to clear your cache after regenerating images.

= Can I revert to JPEG/PNG? =

Yes. Simply deactivate the plugin. Original files were never changed, so your site will work immediately with the original images.

== Screenshots ==

1. Settings page with WebP quality control and support status
2. Built-in thumbnail regeneration with progress tracking
3. WebP support detection and system information

== Changelog ==

= 1.0.0 =
* Initial release
* Automatic WebP conversion for new uploads
* Built-in thumbnail regeneration
* PHP-based URL rewriting
* Browser detection and fallback
* Quality control settings

== Upgrade Notice ==

= 1.0.0 =
Initial release of WebPeasy.
