# WebPeasy – WebP Delivery Plugin

A production-ready WordPress plugin that automatically converts all frontend images to WebP format while leaving your Media Library and original files completely untouched.

## Features

- **Non-Destructive**: Original files remain unchanged in your Media Library
- **Automatic Conversion**: All generated image sizes (thumbnails, medium, large, custom) are created as WebP
- **Global Quality Control**: Single slider to configure WebP compression quality (0-100)
- **Smart Detection**: Automatically detects WebP support in your PHP environment
- **Graceful Fallback**: Works seamlessly on servers without WebP support (no conversion occurs)
- **Clean Implementation**: Uses WordPress core APIs, no HTML rewriting or URL manipulation
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

Because derivative sizes are generated as WebP, WordPress automatically serves them through:
- `wp_get_attachment_image()`
- `the_post_thumbnail()`
- Image blocks in the block editor
- Responsive image srcsets

**No frontend HTML rewriting is needed** – WordPress handles everything natively.

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

### Option 1: WP-CLI (Recommended)

```bash
# Regenerate all images
wp media regenerate

# Regenerate specific image
wp media regenerate <attachment-id>

# Regenerate only missing sizes
wp media regenerate --only-missing
```

### Option 2: Plugin

Install a thumbnail regeneration plugin from the WordPress repository, such as:
- **Regenerate Thumbnails** by Alex Mills
- **Force Regenerate Thumbnails** by Pedro Elsner

### Option 3: Per-Image

In the Media Library:
1. Click on an image
2. Look for a "Regenerate Thumbnails" button (if you have a regeneration plugin)
3. Or re-upload the image (not recommended for large libraries)

## Technical Details

### File Structure

```
webpeasy/
├── webpeasy.php          # Main plugin file
├── includes/
│   ├── class-plugin.php          # Main plugin orchestrator
│   ├── class-settings.php        # Settings management
│   ├── class-admin-ui.php        # Admin interface
│   └── class-support-detector.php # WebP capability detection
└── README.md                      # This file
```

### Class Architecture

**Plugin** (`class-plugin.php`)
- Main orchestrator
- Wires components together
- Implements image conversion hooks
- Manages quality filters

**Settings** (`class-settings.php`)
- Manages plugin options using WordPress Settings API
- Provides get/update/sanitize methods
- Stores settings in `webpeasy_settings` option

**Admin_UI** (`class-admin-ui.php`)
- Renders settings page
- Shows WebP support status
- Displays admin notices
- Provides quality control interface

**Support_Detector** (`class-support-detector.php`)
- Detects ImageMagick WebP support
- Detects GD WebP support
- Caches detection results
- Provides support information for display

### Hooks & Filters

**Core Hooks Used:**

```php
// Convert image formats to WebP
add_filter( 'image_editor_output_format', callback, 10, 3 );

// Set WebP compression quality
add_filter( 'wp_editor_set_quality', callback, 10, 2 );

// Admin interface
add_action( 'admin_menu', callback );
add_action( 'admin_init', callback );
add_action( 'admin_notices', callback );
```

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
A: No. Conversion happens during upload, not on page load. Frontend delivery is faster due to smaller file sizes.

**Q: Do I need to delete my old images?**
A: No. Original files remain unchanged and can serve as backups.

**Q: What if my host doesn't support WebP?**
A: The plugin will detect this and gracefully do nothing. No errors, no broken images.

**Q: Can I use this with a CDN?**
A: Yes. The plugin doesn't interfere with CDN delivery.

**Q: Will this work with lazy loading plugins?**
A: Yes. The plugin only changes the format, not how images are loaded.

**Q: Can I revert to JPEG/PNG?**
A: Yes. Deactivate the plugin and regenerate thumbnails. Original files were never changed.

## Support

For issues, questions, or contributions:
- Website: https://beringer.io
- Plugin page: https://beringer.io/webpeasy

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Credits

Developed by [Beringer](https://beringer.io) for the WordPress community.

---

**Note**: This plugin does NOT:
- Delete or modify original uploads
- Perform HTML rewriting
- Require external services
- Create complex database structures
- Run heavy background processes
