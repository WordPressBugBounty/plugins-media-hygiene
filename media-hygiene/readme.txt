=== Media Hygiene: Remove or Delete Unused Images and More! ===
Contributors: slui
Tags: unused media, delete unused images, media library, media cleanup, unused images
Requires at least: 5.3
Tested up to: 7.1.3
Requires PHP: 8.0
Stable tag: 6.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Find and delete unused images in your WordPress media library, free up hosting space, and keep your backups small.

== Description ==

Old logos. Images from last year's redesign. Uploads you tried once and forgot about. A WordPress media library keeps growing long after you have stopped using some of what is in it.

Media Hygiene finds the files worth a second look. Scan your library, review what appears unused, and decide what stays. Clearing out what you no longer need reclaims storage and makes every future backup smaller.

It suits blogs, business sites and client work. Start small, or filter a large library down into groups you can actually work through. No coding needed.

### How it works

1. Scan your library. The plugin looks for references to your images, documents, audio and video across the content sources it supports.

2. Review the results. You get names, URLs and sizes, so you can open a file or copy its address and check for yourself.

3. Keep what matters. Add anything you want to protect to the whitelist and it stays out of every future scan.

4. Finish when you are ready. Move files to Trash, restore them if you change your mind, or delete them permanently.

You stay in control throughout. Nothing is deleted unless you select it and confirm. An unused result means no reference was found in the places the plugin reads, which is why the review step matters. Keep a backup before you delete anything.

### Some use cases for website owners

- Unable to keep track of unused media files in the media library.

- Uploading the same images in different sizes to figure out optimal size and forgetting which one was actually used.

- Switching out an image in a post with another and can't find it media library file.

- Updating the logo with a new version and don't want the old one to be indexed by Google.

- Images included with purchased theme but never deleted after site was launched.

- After a website was redesigned or rebranded, there may a number of old images or media files no longer in use.

- Before migrating to a new hosting provider, cleaning up the media library may make it a smoother and faster migration process.

- Getting a warning from your hosting provider that you are hitting the storage limit, make your backups smaller.

- You have guest posts or contributors who may have left behind a number of images.

- Changing your product listings or discontinuing products in e-commerce sites may mean images are left unused.

- After trying out a theme, deactivation of theme may leave behind media files.

### How is it Different?

 - [Media Hygiene Pro](https://mediahygiene.com) scans the entire website using a number of different techniques. One way is to "visualize images/media in page source code" to confirm the existence of a media file even when builders/plugins/themes use specialized custom posts to display their widgets on-screen.

### File Types:

* Media Hygiene will recognize most if not all major file types stored in the WordPress media library. This includes but not limited to PDF, jpg, png, gif, mp4, mp3, csv, zip, rar, and txt files. We also cover alternative extension spelling such as jpeg.

### Standard Features:

- Dashboard - shows list of all unused media by occupied space and by type.

- Notifications - a summary email when new files have been uploaded since your last scan. Monthly by default, and you can change it to daily, weekly, bi-weekly or quarterly, or switch it off, in Settings.

- Site builder compatibility - detects media placed with Gutenberg, Elementor, Divi, Bricks, Visual Composer and OceanWP on pages and posts, plus sliders built with LayerSlider (see list below).

- Whitelist - keep files safe from deletion. Perfect for files linked to from off-site sources.

- Filtering - files by name, by year and month, and by category: images, video, audio, documents or others.

- Page size - show between 1 and 200 results at a time. The default is 10.

- Exclude file types - leave chosen extensions out of the scan in Settings. Font files and CSS are excluded already.

- Backup - download unused media file individually or by page.

- Delete - remove files individually or by page.

- Scan - scanning process for all media files not linked to any page/post or other theme settings.

- Support - only through WordPress repository.

### Changelog ###
Full version history is at the bottom of this page, and also at **[mediahygiene.com/changelog](https://mediahygiene.com/changelog/)**.


### **[Compatibility](https://mediahygiene.com/compatibilities/)**

Please check our website to see a complete list of compatible plugins/themes.

Detected by the free version, on pages and posts:

- Gutenberg

- Elementor (builder)

- Bricks (builder)

- Divi (builder)

- Visual Composer (builder)

- OceanWP (theme)

- LayerSlider (slider plugin, bundled with the Enfold theme)

Pro adds the same builders in templates, theme options, headers, footers and custom post types, plus WP Bakery, Beaver Builder, Avada and Flatsome, and media stored by WooCommerce, ACF, Pods, Custom Field Suite, the SEO plugins and the slider plugins.

* More to come...


### What it reads, and what it does not

We would rather tell you this up front than have you find out after deleting something.

The free version finds media by reading your posts and pages, including the page builders listed above, your Site Editor templates, your widgets and your site logo.

Some plugins store images as an ID number rather than a link. Advanced Custom Fields, WooCommerce product galleries, Pods, Custom Field Suite, the SEO plugins and the slider plugins all work that way. The free version cannot follow those, so an image used only in one of those places can appear in your unused list while your site is still using it.

It tells you when that applies to your site. If one of those plugins is holding media, the dashboard says so, with a count and the plugin name, before you delete anything. A file can also be referenced by a shortcode or a dynamic tag that builds the link when the page loads, and those cannot be counted in advance.

So treat the results as a list to review rather than a verdict, and keep a backup before deleting. Pro reads all of the above.

### **[Pro](https://mediahygiene.com)**

Pro reads the places the free version cannot.

**Media stored by plugins**

- Advanced Custom Fields, including image, gallery, file and repeater fields
- WooCommerce product galleries, category images, downloadable files and custom tabs
- Pods
- Custom Field Suite
- Jet Engine
- Yoast SEO, All in One SEO and SEO Press
- Slider Revolution, Meta Slider and Smart Slider

**Wider coverage of the builders**

The free version reads page builders on your pages and posts. Pro also reads saved templates, headers, footers, theme options and custom post types, so a template reused across the site is not mistaken for unused content.

**Stronger matching**

Pro matches resized copies, scaled, rotated and edited versions back to the original file, follows references inside JSON and serialized data, and works on sites where the uploads folder has been moved.

**Tools**

- Folder Scan, which checks the uploads folder for files with no database record at all
- Quarantine, which moves files aside first so you can restore them if something was in use
- One shot download and one shot delete for the whole list rather than a page at a time
- Filter by year and by file type
- WP-CLI commands for large libraries, so a scan is not limited by browser timeouts
- Direct support from the plugin

### Coming to Pro

- Meta Box
- CPT UI
- Remote server backup and restore
- Scheduled scans, so a scan can run on a timer rather than being started by hand
- WPML, Weglot and TranslatePress


== Installation ==
**From Your WordPress Dashboard**
 
1. Go to Plugins >**Add New**
2. Search for **Media hygiene**
3. Click on **Install Now** Button
4. Click on **Activate Now** After successfully installed in your site.

**From WordPress.org**
 
1. Download **[Media hygiene](https://wordpress.org/plugins/media-hygiene/)**
2. Upload the **media-hygiene** folder to the /wp-content/plugins/ directory
3. Activate **Media hygiene** plugin from your plugins page.

== Frequently Asked Questions ==

= How do I know which media files are unused? =

The plugin scans the content sources it supports: posts and pages including drafts, Site Editor templates and template parts, synced patterns, the Customizer and site identity settings, widgets, and the builders listed in the compatibility table. A file is reported as unused when no reference to it was found in any of those places.

That is a statement about where we looked, not a guarantee about your whole site. A file can still be in use somewhere the free version does not read, for example a custom field defined in your theme's code, or a reference built at page render time by a shortcode or a dynamic tag. The dashboard tells you when it detects a coverage gap like that, with the source and a count.

So treat the list as a shortlist to review, not a verdict. Keep a current backup, check anything you are unsure about, and whitelist the files you want to keep.

= Could a file I am still using show up as unused? =

It can, and it is worth knowing when. The plugin never deletes anything on its own: nothing is removed unless you select it and confirm. The question is whether the list you are choosing from is right.

If a file is referenced in a scanned source, a post, a page, a template, a widget, a site logo or a supported builder, it is detected and kept out of the unused list. What the free version cannot follow is media stored outside those sources: custom fields registered in theme or plugin code, and content assembled when the page is rendered rather than saved in the database.

Two things protect you. The dashboard reports a detected coverage gap with its source and count, so you know to look closer before deleting. And the whitelist locks any file you want kept, so it stays out of every future scan.

= Does the plugin send any data off my site? =

Two things, and you control both.

Usage statistics are opt-in. Nothing is sent unless you click "Yes, allow" on the notice inside the plugin. If you do, we send your site URL along with totals: how many media files you have, how much space they use, and how much of that is unused. We do not send filenames, file contents, post content, user details, or email addresses. You can turn it off at any time in Settings.

The summary email is sent from your own site to your own admin email address, using WordPress. It is not sent to us and we never see it. It is on by default, once a month, and only when files have actually been uploaded since your last scan. Set Frequency to "Off (no emails)" in Settings to stop it.

= Which page builders does the free version support? =

The free version detects media placed with Gutenberg, Elementor, Divi, Bricks, Visual Composer and OceanWP on your pages and posts. It also reads sliders built with LayerSlider, the slider plugin that ships with the Enfold theme.

Pro reads those same builders everywhere else as well: saved templates, theme options, headers and footers, and custom post types. Pro also reads media stored by plugins in their own custom fields and database tables, such as Advanced Custom Fields, WooCommerce, Pods, Custom Field Suite, the SEO plugins and the slider plugins. The free version cannot see media stored that way, which is why it warns you when one of those plugins is active.

= How does the plugin identify and remove the unused media files? =

The Media Hygiene uses a few scanning modes. The first mode compares the database entries of specific modules with the files. The second mode scans each folder in the wp-content/upload directory. It identifies files that are not listed in the database and considers them to be unused.

= Will this plugin remove any media files from my backups? =

The Media Hygiene plugin does not have the ability to look inside backups and/or remove any files from your backups.

= Will removing unused media files affect the performance of my website? =

While removing unused media files does not directly impact page loading times, removing unused media files can actually improve the performance of your server and indirectly improve loading times. Unused media files take up space on your server and can cause backups to take longer. 

= Is there any risk of data loss or corruption when using this plugin? =

Like any software, there is a small risk of data loss or corruption when using the Media Hygiene plugin. However, the developer has taken steps to minimize this risk by thoroughly testing the plugin and including safety features on various hosting platforms (e.g. shared, VPS, and dedicated). Please note: websites hosted on shared platforms have a greater probability of failure due to limited allowable resources dictated by the hosting provider.

Additionally, it is always recommended to keep a backup of your website before using any plugin that modifies your directories, just in case something goes wrong.

= How often should I run this plugin to keep my media library clean? =

The frequency at which you run the Media Hygiene plugin depends on how frequently you add media files to your website. There is an email notification feature that allows informs you of the number of files uploaded but not scanned.

If your website receives a high volume of traffic and media files are frequently added, it's recommended to set notifications more frequently, such as every week.

= Does this plugin have any limitations or potential conflicts with other plugins or themes? =

The Media Hygiene plugin is designed to work seamlessly with all the most popular WordPress themes and plugins. However, WordPress is a dynamic ecosystem where there are many changes occurring. The developers have tried to accommodate every possibility.

To our knowledge, the plugin does not have any known conflicts with other plugins or themes. 

We strongly recommend backing up the website and/or running the plugin on a staging or development environment.

If you do encounter any issues or conflicts, you can contact the developer for support and guidance. The developer will work to resolve the issue as soon as possible and provide a solution.

= Can I preview the media files that will be deleted before they are removed? =

Yes, the Media Hygiene plugin allows you to preview the media files that will be deleted before they are removed. The plugin's media scanner will provide a list of all the unused media files, including details such as the file name, image, location (URL), and size.

You can review the files and whitelist the file to prevent deletion.

= Is there any customer support available if I have any issues with the plugin? =

Yes, there is customer support available for the Media Hygiene plugin. The developer provides [basic support](https://wordpress.org/support/plugin/media-hygiene/) through the WordPress repository, where you can find answers to frequently asked questions and troubleshoot common issues. 

Pro users will have access to direct support inside the plugin. The support team will respond to inquiries as soon as possible, but response times may vary depending on the nature of the inquiry and the volume of support requests.

= Can I exclude certain media files or folders from being deleted? =

Yes, the Media Hygiene plugin allows you to exclude certain media files type from scan that do not display in unused files by adding files to a whitelist in the scan results list or It is for Folder Scan separate from Regular Media Scan. 

= Can I restore any media files that were accidentally deleted? =

The Media Hygiene plugin does not have a built-in feature to restore deleted media files. You can restore accidentally deleted media files by using a backup of your website. 

= Is there a way to schedule the plugin to run automatically at regular intervals? =

Scans are started by you, not on a timer, in both the free version and Pro. What you can schedule is the summary email. Under Settings you can have the plugin email you about new media daily, weekly, every two weeks, monthly or quarterly, or turn it off. The email tells you what has been added since your last scan, so you know when another scan is worth running.

= Does this plugin work with multisite installation of WordPress? =

This feature is not available yet but coming.

= Can I see a log of all the media files that have been deleted by the plugin? =

This feature is not available yet but coming.

= Will this plugin remove any meta data associated with the media files? =

No, Media Hygiene will remove the file in its entirety from the WordPress media library. It does not remove metadata only.  

= Is the plugin compatible with the latest version of WordPress? =

The Media Hygiene plugin is designed to be compatible with the latest version of WordPress. The developer regularly updates the plugin to ensure that it continues to work seamlessly with the latest version of WordPress.

== Screenshots ==

1. Scan and See
2. Identify Unused Media Files
3. Remove Media Files 
4. Settings
5. Bulk Delete and Bulk Restore

= Minimum Requirements =

* PHP 8.0 or greater is required
* MySQL 5.6 or greater is recommended

== Upgrade Notice ==

= 6.1.1 =
Fixes a server error that could appear on the update screen when updating from 6.0.

= 6.1.0 =
Version 6.1.0 improves scan accuracy, safety, reporting, and overall usability across Media Hygiene.

- More accurate unused-media detection for site logos, Customizer images, and block-theme templates.

- Clearer warnings when Media Hygiene finds content it cannot fully check.

- Safer Trash, restore, and permanent deletion with better confirmations and completion reporting.

- Improved ACF, Secure Custom Fields and similar custom field plugins, compatibility information, and summary email reporting.

- Additional reliability, performance, security, navigation, translation, and usability improvements throughout the plugin.

== Changelog ==

= 6.1.1 =
* Fixed: updating from 6.0 could show a server error on the update screen. The update itself finished correctly, and the site was not affected.
* Changed: the deactivation feedback form is now only added to the Plugins screen, instead of to every admin page.

= 6.1.0 =
This release is mostly about trust: files that were being listed as unused when they were still on your site, and warnings that were telling you the wrong thing.

Files that were in use, but listed as unused:

* Fixed: your site logo could show as unused on almost every theme. Site logos, site icons, and any image set in the Customizer are now recognised.
* Fixed: on a block theme, images placed in a header, footer or page template through the Site Editor were never checked, so they could show as unused. Templates, template parts and reusable blocks are now included in every scan.
* Fixed: images in your widgets and in Additional CSS were not checked, and could show as unused.
* Fixed: a file could be wrongly held back from your unused list because of a mix-up with unrelated theme settings. Across five real sites this cut the number of files being guessed at from 79 to 2.

Safer deleting:

* Added: deleting permanently now asks you to confirm, twice for a bulk delete and once for a single file. It asked nothing at all before, while moving files to Trash asked twice. Trash can be undone and permanent deletion cannot, so the warnings were the wrong way round.
* Added: moving files to Trash now asks you to confirm once, and tells you how many files.
* Fixed: Restore All on the Trash tab could say everything was restored while files were still sitting there. It now keeps going until the Trash is genuinely empty, and tells you if a file could not be restored.
* Fixed: whitelisting a file could quietly remove it from your unused list without actually adding it to the whitelist. This also affected restoring from the whitelist and the bulk versions of both.
* Fixed: the message confirming files had been moved to Trash did not always appear, and its link went to the Media Library rather than the Trash tab.

Clearer about what the free version can and cannot see:

* Changed: when the plugin cannot read media held by another plugin, it now tells you how many files and which plugin holds them, instead of giving a vague warning. When there is nothing to report it stays quiet.
* Added: for Advanced Custom Fields and Secure Custom Fields, the plugin tells you how many media fields are set up on your site, so you know where to check before deleting.
* Changed: the dashboard now says plainly that the free version reads your posts and pages, and that media kept in a theme option, a plugin field or a store product is outside that scan.

The summary email:

* Fixed: the email setting showed "Disabled" while the plugin was quietly sending it every three months. The setting and the behaviour now match, and the default is monthly.
* Fixed: pictures in the email were being sent at full size, so opening it could pull down tens of megabytes to show a row of thumbnails. It now uses the small version WordPress already made.

Finding your way around:

* Changed: Media Hygiene now takes a single row in the WordPress menu instead of repeating the same four links in two places.
* Fixed: the "View Error Log" link in Settings did nothing. It is now the Scan Log, and it records what each page builder reader found during a scan.
* Changed: the "Download Media" button is now "Download Current Page Media", because that is all it ever did.
* Fixed: the help popups on the Scan, Download and Trash buttons closed before you could click the link inside them.
* Fixed: after a scan finished, the page stayed scrolled below the results, so a scan that had worked did not look like it. It now returns to the top.

Also in this release:

* Faster: large media libraries are noticeably quicker to check for new files.
* Faster: the plugin no longer adds weight to every page load of your site.
* Fixed: a scan could end on a blank page instead of a message, usually on a brand new install or after an interrupted scan.
* Added: the plugin can now be translated. It had no translation file before, so translators had nothing to work from.
* Tested with WordPress 7.1.3.

= 6.0.0 =
*Released 25 May 2026*

* Rewritten scan engine: it reads your page content directly instead of making an HTTP request for every URL.
* Scanning is significantly faster on large media libraries, with up to 30 times fewer database queries per batch and lower memory use.
* A single problem item no longer stops the whole scan.
* Clear error messages when a scan step fails, instead of a scan that simply stops.
* Hardened the handling of background requests.
* Contact details in the deactivation form are only sent if you tick the box.
* Tested with WordPress 7.0.0.

= 5.0.0 =
*Released 25 April 2026*

* Hardened every database query against SQL injection.
* Fixed a cross site scripting issue in the copy media URL action.
* Server errors are no longer shown in the browser.
* Anonymous usage data is now opt in. Nothing is sent unless you agree.
* The deactivation feedback form now has an opt in checkbox.
* Fixed dashboard figures showing the wrong values.
* Fixed the count on the Unused tab not updating when a filter was applied.
* Download and Trash now respect the filter you have applied.
* Fixed the last scan date showing incorrectly.
* Lower memory use during a scan and while building a download.
* Removed PHP notices in some setups.
* Minimum PHP version raised to 8.0.

= 4.0.2 =
*Released 28 March 2026*

* Fixed missing permission checks on background requests, which now refuse unauthorised calls.
* Fixed SQL injection risks in the whitelist, bulk action, scan, Elementor and download handlers.
* Fixed unescaped output in the security token handlers.
* Fixed an email header injection risk in the scheduled notification.
* Fixed unescaped links in the scheduled email.
* Fixed the file version used for cache busting, so updated files reach browsers.

= 4.0.1 =
*Released 1 May 2025*

* Security release.

= 4.0.0 =
*Released 26 November 2024*

* Rebuilt how Elementor content is scanned.
* Added Trash, so files can be removed and restored rather than deleted outright.
* The position of Media Hygiene in the WordPress menu can now be changed.

= 3.0.1 =
*Released 3 July 2024*

* Security release.

= 3.0.0 =
*Released 27 April 2024*

* Added a setting for how many results to show per page.
* Added the email notification telling you about media uploaded since your last scan.
* Improved detection of background images.

= 2.0.0 =
*Released 20 November 2023*

* Reworked the interface for readability.
* General performance improvements.
* Confirmed PHP 8.1 compatibility.
* Updated error log handling.
* Updated data privacy handling.
* Added the option to exclude CSS files from a scan.
* Added a FAQ tab.
* Added LiteSpeed Cache compatibility.
* Fixed the settings page failing to load.
* Fixed a scan that could get stuck.

= 1.0.5 =
*Released 8 September 2023*

* Improved detection of images in page source code.
* Added support for WP Engine server setups.
* Added the deactivation feedback form.
* Improved the dashboard interface.

= 1.0.4 =
*Released 12 July 2023*

* Performance and compatibility improvements.

= 1.0.3 =
*Released 17 June 2023*

* Performance improvements.

= 1.0.2 =
*Released 25 April 2023*

* Added compatibility with the Astra and OceanWP themes.

= 1.0.1 =
*Released 10 April 2023*

* Improved detection for the Enfold theme.
* Better scan notices for themes and plugins.

= 1.0.0 =
*Released 6 April 2023*

* First release.
