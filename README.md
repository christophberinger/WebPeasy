# WebPeasy – WebP Delivery Plugin

A production-ready WordPress plugin that automatically converts all frontend images to WebP format while leaving your Media Library and original files completely untouched.

## Features

- **Non-Destructive**: Original files remain unchanged in your Media Library
- **Automatic Conversion**: All generated image sizes (thumbnails, medium, large, custom) are created as WebP
- **Built-in Regeneration**: Bulk regenerate all existing images with progress tracking
- **Smart URL Rewriting**: PHP-based output buffering automatically serves WebP versions
- **Global Quality Control**: Single slider to configure WebP compression quality (0-100)
- **Smart Detection**: Automatically detects WebP support in your PHP environment
- **Graceful Fallback**: Works seamlessly on servers without WebP support (no conversion occurs)
- **Universal Compatibility**: Works on any server (Apache, Nginx, etc.) - no .htaccess needed
- **Browser Detection**: Automatically serves WebP only to browsers that support it
- **Admin Notices**: Clear feedback about WebP support status
- **Settings Page**: Simple configuration under Settings → WebPeasy

## How It Works

### Core Mechanism

The plugin uses WordPress's built-in `image_editor_output_format` filter to map source image formats to WebP:

```php
add_filter( 'image_editor_output_format', callback, 10, 3 );
```

This approach ensures:
- **Original uploads stay intact**: Your JPEG, PNG, and GIF files remain unchanged
- **Derivative sizes are WebP**: All generated thumbnails and responsive images are WebP
- **No database changes**: No modifications to attachment metadata structure
- **Core compatibility**: Relies on WordPress's native image handling

### What Gets Converted

- **JPEG → WebP**: All JPEG images and their sizes
- **PNG → WebP**: All PNG images and their sizes
- **GIF → WebP**: All GIF images and their sizes
- **SVG**: Not converted (remains SVG)
- **Existing WebP**: Not re-converted

### Frontend Delivery

The plugin uses a two-pronged approach for serving WebP images:

**1. Native WordPress Functions (New Uploads)**
- `wp_get_attachment_image()`
- `the_post_thumbnail()`
- Image blocks in the block editor
- Responsive image srcsets

**2. Smart URL Rewriting (Existing Images)**

For existing images and hardcoded URLs, the plugin uses PHP output buffering to automatically replace image URLs:

```php
// When browser supports WebP and file exists
image.jpg → image.webp (automatically)
```

This approach:
- Works on **any server** (Apache, Nginx, LiteSpeed, etc.)
- Requires **no .htaccess** configuration
- Detects **browser support** via HTTP Accept header
- Preserves **original files** as fallback
- Handles `src`, `srcset`, `data-src`, and lazy-loaded images

## Installation

1. Upload the `webpeasy` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → WebPeasy to configure

## Configuration

### WebP Quality Setting

Navigate to **Settings → WebPeasy** in your WordPress admin.

- **WebP Quality Slider**: Set compression quality from 0-100
  - Lower values = smaller files, lower quality
  - Higher values = larger files, better quality
  - **Recommended**: 80-85 for optimal balance
  - Default: 82

### WebP Support Status

The settings page displays:
- **Support Status**: Enabled or Disabled
- **Image Library**: Which PHP library is being used (ImageMagick or GD)
- **Active Conversions**: Which formats are being converted to WebP

If WebP is not supported, you'll see a warning notice with instructions.

## Requirements

### Minimum Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

### WebP Support

**One of the following is required:**

**ImageMagick** (preferred):
```bash
# Check if available
php -m | grep imagick

# Check WebP support
php -r "print_r(Imagick::queryFormats('WEBP'));"
```

**OR GD Library**:
```bash
# Check if available
php -m | grep gd

# Check WebP support
php -r "var_dump(function_exists('imagewebp'));"
```

### Enabling WebP Support

If WebP is not supported, contact your hosting provider to:
- Install or enable the ImageMagick extension for PHP
- Ensure ImageMagick is compiled with WebP support
- OR ensure GD is compiled with WebP support

Most modern hosting environments support WebP by default.

## Regenerating Existing Images

**Important**: The plugin only converts images as they are processed. Existing images in your Media Library will only get WebP versions if their sizes are regenerated.

### Option 1: Built-in Regeneration Tool (Recommended)

Navigate to **Settings → WebPeasy** and use the built-in regeneration feature:

1. Click the **"Regenerate Thumbnails"** button
2. Confirm the operation
3. Watch the progress bar as images are processed
4. WebP versions are generated alongside originals (originals preserved)

**Features:**
- Real-time progress tracking
- Batch processing (5 images at a time)
- No timeout issues
- Cancel anytime
- Processes in background

**How it works:**
- Generates WebP versions for all existing JPG/PNG/GIF images
- Keeps original thumbnails intact
- Processes images in small batches to avoid server timeouts
- Shows detailed progress: "45 / 120 images processed (38%)"

### Option 2: WP-CLI

```bash
# Regenerate all images
wp media regenerate

# Regenerate specific image
wp media regenerate <attachment-id>

# Regenerate only missing sizes
wp media regenerate --only-missing
```

### Option 3: Third-party Plugin

Install a thumbnail regeneration plugin from the WordPress repository, such as:
- **Regenerate Thumbnails** by Alex Mills
- **Force Regenerate Thumbnails** by Pedro Elsner

## Technical Details

### File Structure

```
webpeasy/
├── webpeasy.php                      # Main plugin file
├── includes/
│   ├── class-plugin.php              # Main plugin orchestrator
│   ├── class-settings.php            # Settings management
│   ├── class-admin-ui.php            # Admin interface
│   ├── class-support-detector.php    # WebP capability detection
│   └── class-thumbnail-regenerator.php # Bulk thumbnail regeneration
└── README.md                         # This file
```

### Class Architecture

**Plugin** (`class-plugin.php`)
- Main orchestrator
- Wires components together
- Implements image conversion hooks
- Manages quality filters
- Handles PHP-based URL rewriting via output buffering
- Detects browser WebP support

**Settings** (`class-settings.php`)
- Manages plugin options using WordPress Settings API
- Provides get/update/sanitize methods
- Stores settings in `webpeasy_settings` option

**Admin_UI** (`class-admin-ui.php`)
- Renders settings page
- Shows WebP support status
- Displays admin notices
- Provides quality control interface
- Handles AJAX endpoints for thumbnail regeneration
- Provides JavaScript for progress tracking

**Support_Detector** (`class-support-detector.php`)
- Detects ImageMagick WebP support
- Detects GD WebP support
- Caches detection results
- Provides support information for display

**Thumbnail_Regenerator** (`class-thumbnail-regenerator.php`)
- Handles bulk thumbnail regeneration
- Processes images in batches
- Generates WebP versions alongside originals
- Provides progress tracking data

### Hooks & Filters

**Core Hooks Used:**

```php
// Convert image formats to WebP
add_filter( 'image_editor_output_format', callback, 10, 3 );

// Set WebP compression quality
add_filter( 'wp_editor_set_quality', callback, 10, 2 );

// PHP URL rewriting via output buffering
add_action( 'template_redirect', callback, 0 );

// Admin interface
add_action( 'admin_menu', callback );
add_action( 'admin_init', callback );
add_action( 'admin_notices', callback );

// AJAX endpoints for thumbnail regeneration
add_action( 'wp_ajax_webpeasy_get_image_count', callback );
add_action( 'wp_ajax_webpeasy_regenerate_batch', callback );
```

### PHP URL Rewriting Mechanism

The plugin uses output buffering to automatically serve WebP images to compatible browsers:

**1. Browser Detection**
```php
// Checks HTTP_ACCEPT header
if ( strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false ) {
    // Browser supports WebP
}
```

**2. Output Buffering**
```php
// Start output buffering on frontend
add_action( 'template_redirect', 'start_output_buffer', 0 );
ob_start( 'replace_images_with_webp' );
```

**3. URL Replacement**
```php
// Replace image URLs in HTML before sending to browser
preg_replace_callback( $pattern, function( $matches ) {
    // Check if WebP version exists
    if ( file_exists( $webp_path ) ) {
        return $webp_url; // Use WebP
    }
    return $original_url; // Fallback to original
});
```

**What gets replaced:**
- `src="image.jpg"` → `src="image.webp"`
- `srcset="image.jpg 1x"` → `srcset="image.webp 1x"`
- `data-src="image.jpg"` → `data-src="image.webp"` (lazy loading)
- `data-srcset="image.jpg"` → `data-srcset="image.webp"`

**Performance considerations:**
- Only runs on frontend (not admin/AJAX/cron)
- Only processes if browser supports WebP
- Only replaces URLs if WebP file exists
- Minimal overhead (~10-20ms per page)

### Database

**No custom tables are created.**

Settings are stored in a single option:
- Option name: `webpeasy_settings`
- Option type: Serialized array
- Default values:
  ```php
  array(
      'webp_quality' => 82
  )
  ```

## Compatibility

### WordPress

- Core image functions: Full compatibility
- Block editor: Full compatibility
- Classic editor: Full compatibility
- Custom image sizes: Full compatibility
- Responsive images (srcset): Full compatibility

### Plugins

Compatible with most WordPress plugins including:
- SEO plugins (Yoast, Rank Math, etc.)
- Page builders (Elementor, Beaver Builder, etc.)
- Gallery plugins
- E-commerce plugins (WooCommerce, Easy Digital Downloads, etc.)

### Themes

Works with any WordPress theme that uses standard WordPress image functions.

## Troubleshooting

### Images are not converting to WebP

1. **Check WebP Support**: Go to Settings → WebPeasy and verify WebP is "Enabled"
2. **Regenerate Thumbnails**: Existing images need to be regenerated
3. **Check File Permissions**: Ensure WordPress can write to the uploads directory
4. **Clear Caches**: Clear any caching plugins, CDN caches, or browser cache

### WebP support shows as Disabled

1. **Contact Host**: Ask your hosting provider to enable WebP support
2. **Check PHP Extensions**: Verify ImageMagick or GD is installed
3. **Check WebP in Extension**: Ensure the extension is compiled with WebP support

### Quality settings not applying

1. **Regenerate Images**: Quality only applies to newly generated images
2. **Check Filters**: Ensure no other plugin is overriding quality settings
3. **Verify Settings Saved**: Confirm settings were saved successfully

### Original images are being changed

**This should never happen.** If original files are being modified:
1. Deactivate the plugin immediately
2. Report the issue with your environment details
3. Check for conflicts with other image optimization plugins

## Performance Impact

### Server Resources

- **Minimal CPU overhead**: Only during image upload/regeneration
- **Memory usage**: Same as WordPress core image processing
- **Disk space**: Approximately 30-50% less than JPEG/PNG for same quality

### File Size Reduction

Typical WebP savings:
- **JPEG**: 25-35% smaller
- **PNG**: 50-75% smaller (especially for photos)
- **GIF**: 50-70% smaller

### Page Load Speed

Expected improvements:
- **Faster page loads**: Due to smaller image sizes
- **Better Core Web Vitals**: Improved LCP scores
- **Reduced bandwidth**: Lower hosting costs

## Uninstallation

### Automatic Cleanup

When you uninstall the plugin:
1. Plugin settings are removed from the database
2. Original files remain unchanged
3. WebP files remain in the uploads directory

### Manual Cleanup

To remove WebP files:

```bash
# Remove all WebP files (use with caution)
find /path/to/wp-content/uploads -name "*.webp" -delete
```

Or use a plugin like "Media Cleaner" to identify and remove unused files.

## Developer Information

### Filters

```php
// Modify WebP quality programmatically
add_filter( 'wp_editor_set_quality', function( $quality, $mime_type ) {
    if ( $mime_type === 'image/webp' ) {
        return 90; // Custom quality
    }
    return $quality;
}, 20, 2 );
```

### Constants

```php
WEBPEASY_VERSION  // Plugin version
WEBPEASY_FILE     // Main plugin file path
WEBPEASY_PATH     // Plugin directory path
WEBPEASY_URL      // Plugin URL
```

## Frequently Asked Questions

**Q: Will this slow down my site?**
A: No. Conversion happens during upload, not on page load. The PHP URL rewriting adds minimal overhead (~10-20ms) but the smaller WebP files load much faster, resulting in net performance gains.

**Q: Does the URL rewriting work with caching plugins?**
A: Yes. The rewriting happens before page caching, so cached pages will already have WebP URLs. Make sure to clear your cache after regenerating images.

**Q: Do I need to delete my old images?**
A: No. Original JPG/PNG files are preserved alongside WebP versions. This provides automatic fallback for older browsers.

**Q: What if my host doesn't support WebP?**
A: The plugin will detect this and gracefully do nothing. No errors, no broken images.

**Q: Can I use this with a CDN?**
A: Yes. If your CDN caches HTML, clear the cache after activating the plugin. If your CDN only caches images, both formats (original and WebP) will be served.

**Q: Will this work with lazy loading plugins?**
A: Yes. The URL rewriting handles `data-src` and `data-srcset` attributes used by lazy loading plugins.

**Q: Does it work on Nginx/LiteSpeed/other servers?**
A: Yes! Unlike .htaccess-based solutions, this plugin works on any server because it uses PHP output buffering.

**Q: Can I revert to JPEG/PNG?**
A: Yes. Simply deactivate the plugin. Original files were never changed, so your site will work immediately with the original images.

## Support

For issues, questions, or contributions:
- Website: https://beringer.io
- Plugin page: https://beringer.io/webpeasy

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Credits

Developed by [Beringer](https://beringer.io) for the WordPress community.

---

**Note**: This plugin:
- ✅ **Preserves** original uploads (never deleted or modified)
- ✅ **Performs** safe PHP-based URL rewriting (output buffering)
- ✅ **Works** entirely within WordPress (no external services)
- ✅ **Uses** WordPress's native database options (no custom tables)
- ✅ **Processes** images in smart batches (no heavy background processes)
- ✅ **Supports** all modern servers (Apache, Nginx, LiteSpeed, etc.)
