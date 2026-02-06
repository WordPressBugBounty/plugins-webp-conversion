=== WebP Conversion ===
Contributors: SheepFish, denzamb
Donate link: https://www.privat24.ua/send/3hawm
Tags: webp, conversion, image, svg, ico
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 2.2
License: GPL2

Convert your .png and .jpeg images to WebP format for FREE – with absolutely NO limits or hidden restrictions.

== Description ==
WebP Conversion allows you to automatically convert images to WebP format while uploading them to the WordPress media library. You can also convert individual images, perform bulk conversions directly from the media library, restore original images after conversion, and delete originals if you want to save storage space.

Unlike many other plugins, **WebP Conversion is 100% free and completely unlimited** – no premium version, no hidden restrictions, and no image limits. Convert as many images as you want, whenever you want!

Key features include:
- Unlimited image conversions with no restrictions.
- Bulk Conversion and Bulk Restore options for converting or restoring multiple images at once with real-time feedback.
- Image conversion quality settings for images below 200KB, between 200KB and 1MB, between 1MB and 2.5MB and more than 2.5MB.
- Option to automatically convert images when they are uploaded to the media library.
- Single image conversion button for .png and .jpeg files in the WordPress media library.
- "Select All" button for WordPress media library.
- Full compatibility with ACF and WooCommerce.
- Enabling uploads of .svg and .ico images.
- Automatic replacement of converted images in postmeta, termmeta, usermeta, post content, WooCommerce gallery, WooCommerce product thumbnails and ACF fields.
- Restore original images after conversion if needed.
- Option to delete original images after conversion to save storage space.
- "Remove all originals" button on the plugin page to quickly clear all original images and free up space.

### Key Features:
- Automatic WebP conversion on image upload.
- Restore original images after conversion.
- Delete original images to save disk space.
- Conversion quality settings based on image size categories.
- "Convert to WebP", "Restore" and "Remove" buttons for individual images in the media library.
- Bulk image conversion with real-time progress display.
- ACF and WooCommerce compatibility.
- Upload .svg and .ico into WordPress Media.
- Replaces old images with new WebP versions in existing locations.
- "Remove all originals" button for quick storage cleanup.

== Frequently Asked Questions ==
= What image formats does the plugin support for conversion? =
The plugin supports converting .png and .jpeg files to WebP format.

= Is there any limit of amount of images that can be converted? =
No, you can convert as many images as many you want.

= Which libraries are supported for image conversion? =
The plugin supports both GD and Imagick libraries.

= Can I automatically convert images during upload? =
Yes, you can enable the "Automatically convert images while uploading" option in the settings to convert images upon upload to the media library.

= How do I convert multiple images at once? =
In the WordPress media library, use the "Bulk Select" option, choose the images you want to convert, and click the "Convert Selected" button.

= Is the plugin compatible with WooCommerce and ACF? =
Yes, the plugin works with WooCommerce and ACF. After conversion, it replaces old images in WooCommerce product galleries and ACF image fields with the new WebP versions.

= Does the plugin enables uploading of .svg and .ico images? =
Yes, starting from version 1.1 you can enable uploading of .svg and .ico in plugin settings.

= Can I restore an image to its original version after conversion? =
Yes, you can do this in the Media Library page and on the single image page by clicking the "Restore" button.

== Screenshots ==
1. **Media Library** - Convert selected images in media library with real time feedback.
2. **Media Library Options** – Bulk actions menu in the Media Library list view that allows you to choose between multiple image processing options.
3. **Settings Page** - Manage conversion options.
4. **Convert Single Image** – Convert individual images directly from the attachment page.
5. **Restore or Remove Original file** – Restore or remove originals for a single image from the attachment page.

== Note ==
* There is no way of converting individual images over 10MB.

== Changelog ==
= 2.1 =
* Fixed Fatal Error when removing an image while plugin is active.
= 2.1 =
* Added confirm message when clicking the button "Remove all originals".
= 2.0 =
* Added GD Library. From now on, you can choose between Imagick and GD libraries for .webp conversion.
* Added restore functionality:
  - "Restore" button for individual images on the attachment page.
  - "Restore Selected" option in the Media Library.
* Added remove originals functionality (for converted to .webp images):
  - "Remove" button for individual images on the attachment page.
  - "Remove Originals for Selected" option in the Media Library.
  - "Remove all originals" button on the plugin settings page.
* Improved workflow: converted images now update the existing attachment instead of creating a new one, ensuring the attachment ID remains the same.
* Updated translations for English and Ukrainian.
= 1.1.2 =
* Added translations for different languages.
= 1.1 =
* Added ability to upload .svg and .ico images to WordPress Media.
* Conversion of multiple images now working by batches of 5 images (10 before).
* For efficiency reason, from this version, there is no longer ability to convert individual images over 10MB.
* Updated screenshots and plugin icon.
= 1.0.1 =
* Updated list of plugin tags.
= 1.0 =
* Initial release of the WebP Conversion plugin.
* Added automatic conversion on upload.
* Added single and bulk image conversion.
* Introduced settings for managing conversion quality for different image sizes.
* Full compatibility with WooCommerce and ACF for image replacement.