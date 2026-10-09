<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class wmh_general
{

	public $conn;
	public $wmh_unused_media_post_id;
	public $wmh_whitelist_media_post_id;
	public $wmh_error_log;
	public $wmh_temp;
	public $wmh_deleted_media;
	public $wmh_used_media_post_id;
	public $wmh_save_scan_content;
	public $wmh_scan_known_used;

	public function __construct()
	{
		global $wpdb;
		$this->conn = $wpdb;
		$this->wmh_unused_media_post_id = $this->conn->prefix . MH_PREFIX . 'unused_media_post_id';
		$this->wmh_whitelist_media_post_id = $this->conn->prefix . MH_PREFIX . 'whitelist_media_post_id';
		$this->wmh_error_log = $this->conn->prefix . MH_PREFIX . 'error_log';
		$this->wmh_temp = $this->conn->prefix . MH_PREFIX . 'temp';
		$this->wmh_deleted_media = $this->conn->prefix . MH_PREFIX . 'deleted_media';
		$this->wmh_used_media_post_id = $this->conn->prefix . MH_PREFIX . 'used_media_post_id';
		$this->wmh_save_scan_content = $this->conn->prefix . MH_PREFIX . 'save_scan_content';
		$this->wmh_scan_known_used = $this->conn->prefix . MH_PREFIX . 'scan_known_used';
		/* add admin menu hook */
		add_action('admin_menu', array($this, 'fn_add_menu_to_admin'));
		/* add Go Pro link in plugin list row action */
		add_filter('plugin_action_links_' . MH_BASE_NAME, array($this, 'fn_wmh_add_row_action_go_pro'));
	}

	public function fn_add_menu_to_admin()
	{
		/* get scan option data. */
		$default_poisition = '10';
		$wmh_scan_option_data = get_option('wmh_scan_option_data', true);
		if (!empty($wmh_scan_option_data)) {
			if (isset($wmh_scan_option_data['menu_position_input']) && ($wmh_scan_option_data['menu_position_input'] != "" && $wmh_scan_option_data['menu_position_input'] != "0")) {
				$default_poisition = (int) $wmh_scan_option_data['menu_position_input'];
			}
		}

		/* Media Hygiene menu page */
		$media_hygiene_menu_page = add_menu_page(
			esc_html(__('Media Hygiene', MEDIA_HYGIENE)),
			esc_html(__('Media Hygiene', MEDIA_HYGIENE)),
			'manage_options',
			'wmh-media-hygiene',
			array($this, 'fn_wmh_media_hygiene_handler'),
			'dashicons-trash',
			$default_poisition
		);

		/* Settings and Get Help are pages, not menu rows.
		 *
		 * Every one of our screens already renders a tab bar with Dashboard,
		 * Settings, Get Help and Folder Scan, and that bar shows which tab you
		 * are on. Listing the same four destinations in the sidebar said the
		 * same thing twice in two places that cannot agree about the current
		 * page, and it spent room in WordPress's global menu, which every other
		 * plugin shares, to do it. A Dashboard child was the worst of them: it
		 * pointed at the parent's own slug, so the menu showed "Media Hygiene >
		 * Dashboard" as two rows for one page.
		 *
		 * Registered against options.php so each stays a real admin page with a
		 * capability check and a working URL. Anyone with admin.php?page=wmh-settings
		 * bookmarked, or arriving from a link in our own UI, still lands on it. */
		$settings_menu_page = add_submenu_page(
			'options.php',
			esc_html(__('Settings', MEDIA_HYGIENE)),
			esc_html(__('Settings', MEDIA_HYGIENE)),
			'manage_options',
			'wmh-settings',
			array($this, 'fn_wmh_settings_handler')
		);

		$get_help_menu_page = add_submenu_page(
			'options.php',
			esc_html(__('Get Help', MEDIA_HYGIENE)),
			esc_html(__('Get Help', MEDIA_HYGIENE)),
			'manage_options',
			'wmh-get-help',
			array($this, 'fn_wmh_get_help_handler')
		);

		/* Deleted media page */
		/*$deleted_media_menu_page = add_submenu_page(
			'wmh-media-hygiene',
			esc_html(__('Deleted media', MEDIA_HYGIENE)),
			esc_html(__('Deleted media', MEDIA_HYGIENE)),
			'manage_options',
			'wmh-deleted-media',
			array($this, 'fn_wmh_deleted_media_handler'),
			4
		);*/


		/* Error Log page.
		 *
		 * Registered with a null parent, so it is a real admin page with a
		 * capability check but adds no row to the menu: the log is a support
		 * tool reached from Settings, not something to sit beside Dashboard
		 * all the time. Settings linked to page=wmh-media-hygiene&tab=error_log
		 * which nothing has ever read, so "View Error Log" silently returned
		 * the user to the dashboard.
		 *
		 * Registered whether or not logging is switched on. The page explains
		 * that it is off and shows any rows already collected; refusing to
		 * register it meant the link 404'd instead. */
		/* 'options.php' rather than null: passing null here is a deprecated
		 * string parameter in PHP 8.1 and emits a notice on every admin page. */
		$error_log_menu_page = add_submenu_page(
			'options.php',
			esc_html(__('Scan Log', MEDIA_HYGIENE)),
			esc_html(__('Scan Log', MEDIA_HYGIENE)),
			'manage_options',
			'wmh-error-log',
			array($this, 'fn_wmh_error_log_handler')
		);

		/* No Go Pro row, and so no submenu at all.
		 *
		 * WordPress builds the first child of a top-level menu itself, pointing
		 * back at the parent. Any child therefore produced a "Media Hygiene"
		 * row underneath "Media Hygiene": one page, two rows. Removing the last
		 * child removes that duplicate with it, and the sidebar is one entry.
		 *
		 * Pro is still offered where someone is actually using the plugin: the
		 * Upgrade button and the Folder Scan tab in our own header, the tooltips
		 * on the locked buttons, the notices, and the Go Pro link on the Plugins
		 * page. A permanent menu row added reach in the one place it cost the
		 * whole plugin a tidy menu. */


		/* css. */
		add_action('admin_print_styles-' . $media_hygiene_menu_page, array($this, 'fn_wmh_media_hygiene_css'));
		add_action('admin_print_styles-' . $settings_menu_page, array($this, 'fn_wmh_media_hygiene_css'));
		add_action('admin_print_styles-' . $get_help_menu_page, array($this, 'fn_wmh_media_hygiene_css'));
		add_action('admin_print_styles-' . $error_log_menu_page, array($this, 'fn_wmh_media_hygiene_css'));
		/*add_action('admin_print_styles-' . $deleted_media_menu_page, array($this, 'fn_wmh_media_hygiene_css'));*/

		/* js. */
		add_action('admin_print_scripts-' . $media_hygiene_menu_page, array($this, 'fn_wmh_media_hygiene_js'));
		add_action('admin_print_scripts-' . $settings_menu_page, array($this, 'fn_wmh_media_hygiene_js'));
		add_action('admin_print_scripts-' . $get_help_menu_page, array($this, 'fn_wmh_media_hygiene_js'));
		add_action('admin_print_scripts-' . $error_log_menu_page, array($this, 'fn_wmh_media_hygiene_js'));
		/*add_action('admin_print_scripts-' . $deleted_media_menu_page, array($this, 'fn_wmh_media_hygiene_js'));*/
	}

	public function fn_wmh_media_hygiene_css()
	{
		/* register. */
		/* our own files use filemtime so an edit is picked up immediately;
		   the vendor bundles below only change on release, so the version
		   constant is the right cache key for them */
		wp_register_style('wmh-admin-css', plugins_url('/assets/css/wmh-admin.css', dirname(__FILE__)), false, wmh_asset_version('assets/css/wmh-admin.css'), 'all');
		wp_register_style('bootstrap-css', plugins_url('/assets/css/bootstrap.min.css', dirname(__FILE__)), false, MH_FILE_VERSION, 'all');
		wp_register_style('bootstrap-datepicker-css', plugins_url('/assets/css/bootstrap-datepicker.min.css', dirname(__FILE__)), false, MH_FILE_VERSION, 'all');
		wp_register_style('fontawesome-css', plugins_url('/assets/fontawesome/css/fontawesome.min.css', dirname(__FILE__)), false, MH_FILE_VERSION, 'all');
		wp_register_style('fontawesome-all-css', plugins_url('/assets/fontawesome/css/all.min.css', dirname(__FILE__)), false, MH_FILE_VERSION, 'all');
		wp_register_style('datatable-css', plugins_url('/assets/css/datatable.min.css', dirname(__FILE__)), false, MH_FILE_VERSION, 'all');

		/* enqueue. */
		wp_enqueue_style('wmh-admin-css');
		wp_enqueue_style('bootstrap-css');
		wp_enqueue_style('bootstrap-datepicker-css');
		wp_enqueue_style('fontawesome-css');
		wp_enqueue_style('fontawesome-all-css');
		wp_enqueue_style('datatable-css');
	}

	public function fn_wmh_media_hygiene_js()
	{
		/* register. */
		wp_register_script('wmh-admin-js', plugins_url('/assets/js/wmh-admin.js', dirname(__FILE__)), array('jquery'), wmh_asset_version('assets/js/wmh-admin.js'), true);
		wp_register_script('bootstrap-js', plugins_url('/assets/js/bootstrap.min.js', dirname(__FILE__)), array('jquery'), MH_FILE_VERSION, true);
		wp_register_script('bootstrap-datepicker-js', plugins_url('/assets/js/bootstrap-datepicker.min.js', dirname(__FILE__)), array('jquery'), MH_FILE_VERSION, true);
		wp_register_script('fontawesome-js', plugins_url('/assets/fontawesome/js/fontawesome.min.js', dirname(__FILE__)), array('jquery'), MH_FILE_VERSION, true);
		wp_register_script('fontawesome-all-js', plugins_url('/assets/fontawesome/js/all.min.js', dirname(__FILE__)), array('jquery'), MH_FILE_VERSION, true);
		wp_register_script('datatable-js', plugins_url('/assets/js/datatable.min.js', dirname(__FILE__)), array('jquery'), MH_FILE_VERSION, true);

		/* enqueue. */
		wp_enqueue_script('wmh-admin-js');
		wp_enqueue_script('bootstrap-js');
		wp_enqueue_script('bootstrap-datepicker-js');
		wp_enqueue_script('fontawesome-js');
		wp_enqueue_script('fontawesome-all-js');
		wp_enqueue_script('datatable-js');

		/* check current url for filter or search data */
		if (isset($_GET['page'])) {
			/* get media type */
			if (isset($_GET['images'])) {
				$filter_keyword = sanitize_text_field($_GET['images']);
			} else if (isset($_GET['video'])) {
				$filter_keyword = sanitize_text_field($_GET['video']);
			} else if (isset($_GET['audio'])) {
				$filter_keyword = sanitize_text_field($_GET['audio']);
			} else if (isset($_GET['documents'])) {
				$filter_keyword = sanitize_text_field($_GET['documents']);
			} else if (isset($_GET['others'])) {
				$filter_keyword = sanitize_text_field($_GET['others']);
			} else {
				$filter_keyword = 'all';
			}
		}

		/* required messages or string for JS file */
		$msg_array = array(
			'warning_1' => esc_html(__('Are you sure you want to trash all unused media in one shot?', MEDIA_HYGIENE)),
			'warning_2' => esc_html(__('Did you make a backup of your website?', MEDIA_HYGIENE)),
			'warning_3' => esc_html(__('I agree that I want to trash all unused media files and that I have made a backup of the website.', MEDIA_HYGIENE)),
			'msg_1' => esc_html(__('Copied', MEDIA_HYGIENE)),
			'trash_page_confirm_1' => esc_html(__('Move ALL unused media on this page to Trash? Nothing will be permanently deleted yet. Files go to the WordPress Media Trash first, so you can review and restore anything before permanent removal.', MEDIA_HYGIENE)),
			'trash_page_confirm_2' => esc_html(__('Please confirm: I have a backup of my website and I understand that these files will be moved to Trash (NOT permanently deleted). I can restore any file from the Media Library if needed.', MEDIA_HYGIENE)),
			/* Body of the banner shown after a page of media is trashed.
			 * Says the Trash tab, not the Media Library: that is where the
			 * link goes and where the files can actually be restored from. */
			'trash_page_notice_body' => esc_html(__('These files are now in the Trash. Nothing has been permanently deleted yet. Check your site looks right, and if a file was trashed by mistake you can restore it from the Trash tab. Once you are happy, empty the Trash to free up the space.', MEDIA_HYGIENE)),

			/* Permanent delete had NO confirmation at all, while moving files to
			 * Trash asked twice. Trash is recoverable; this is not. The first
			 * message carries the count so the user sees the scale of what they
			 * are about to do, and the second asks about the backup, which is
			 * the only thing that can get these files back. */
			'delete_confirm_1' => esc_html(__('Permanently delete %d file(s)?', MEDIA_HYGIENE))
				. "

" . esc_html(__('This removes the files from the server along with every thumbnail. It does not go to Trash and it cannot be undone.', MEDIA_HYGIENE)),
			'delete_confirm_2' => esc_html(__('Last check before deleting.', MEDIA_HYGIENE))
				. "

" . esc_html(__('Do you have a backup you could restore these files from? If you are not sure, press Cancel. The files will stay in Trash and nothing is lost.', MEDIA_HYGIENE)),
			/* Trash is reversible, so these ask ONCE, not twice. A single prompt
			 * is enough to catch a misclick on a big selection, and reserving
			 * the double prompt for permanent deletion keeps that one meaning
			 * something. */
			'trash_confirm_bulk' => esc_html(__('Move %d file(s) to Trash?', MEDIA_HYGIENE))
				. "

" . esc_html(__('Nothing is deleted yet. You can restore them from the Trash tab.', MEDIA_HYGIENE)),
			'trash_confirm_single' => esc_html(__('Move this file to Trash?', MEDIA_HYGIENE))
				. "

" . esc_html(__('Nothing is deleted yet. You can restore it from the Trash tab.', MEDIA_HYGIENE)),

			/* The whole-Trash button. This is the single most destructive action
			 * in the plugin: it empties Trash entirely, not a page at a time, and
			 * it had no confirmation at all. */
			'delete_confirm_all' => esc_html(__('Empty the Trash completely?', MEDIA_HYGIENE))
				. "

" . esc_html(__('This deletes every file in the Trash, not only the ones you have ticked. Each one goes from the server with its thumbnails, and it cannot be undone.', MEDIA_HYGIENE)),
			'delete_confirm_all_2' => esc_html(__('Last check.', MEDIA_HYGIENE))
				. "

" . esc_html(__('Do you have a backup you could restore from? If you are not sure, press Cancel. Everything stays in Trash and nothing is lost.', MEDIA_HYGIENE)),
			'delete_confirm_single' => esc_html(__('Permanently delete this file?', MEDIA_HYGIENE))
				. "

" . esc_html(__('It will be removed from the server with every thumbnail. This cannot be undone.', MEDIA_HYGIENE)),
			'restore_default_file_exe_msg_1' => esc_html(__('Are you sure want to restore default file extensions.', MEDIA_HYGIENE)),
		);

		/* localize script for wmh-admin-js. */
		wp_localize_script(
			'wmh-admin-js',
			'wmhObj',
			array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				"nonce" => wp_create_nonce('media_hygiene_nonce'),
				'url' => admin_url(),
				'filter_keyword' => $filter_keyword,
				'msg_array' => $msg_array
			)
		);
	}

	/* call back function for Media Hygiene menu page. */
	public function fn_wmh_media_hygiene_handler()
	{

		/* Media Hygiene view  */
		$this->fn_wmh_get_template('wmh-media-hygiene-view.php');
	}

	/* Call back function for Settings menu page. */
	public function fn_wmh_settings_handler()
	{
		/* Settings view  */
		$this->fn_wmh_get_template('wmh-settings-view.php');
	}

	/* Call back function for Get help menu page */
	public function fn_wmh_get_help_handler()
	{
		/* Get Help view  */
		$this->fn_wmh_get_template('wmh-get-help-view.php');
	}

	/* Call back function for Error log */
	public function fn_wmh_error_log_handler()
	{
		/* Error Log view  */
		$this->fn_wmh_get_template('wmh-error-log-view.php');
	}

	/* call back function for Deleted media */
	public function fn_wmh_deleted_media_handler()
	{

		$this->fn_wmh_get_template('wmh-deleted-media-view.php');
	}

	public function fn_wmh_add_row_action_go_pro($links)
	{

		/* Go Pro link */
		$gro_pro_link_html = '<a href="' .  wmh_site_link('pricing', 'plugins-row-action')  . '" aria-label="' . esc_attr(__('Go Pro', MEDIA_HYGIENE)) . '" style="color: #ff8c00;font-weight:700" target="_blank">' . esc_html__('Go Pro', MEDIA_HYGIENE) . '</a>';
		$links['go_pro'] = $gro_pro_link_html;

		return $links;
	}

	public function fn_wmh_create_table()
	{

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		/* get active plugin list */
		$all_plugins = get_option('active_plugins');
		if (!empty($all_plugins)) {
			/* media hygiene pro version plugin key */
			$media_hygine_key = 'media-hygiene-pro/media-hygiene-pro.php';
			if ((in_array($media_hygine_key, $all_plugins))) {
				deactivate_plugins($media_hygine_key);
			}
		}

		$wmh_unused_media_post_id_sql = "CREATE TABLE IF NOT EXISTS " . $this->wmh_unused_media_post_id . "(
			`id` int NOT NULL AUTO_INCREMENT,
			`post_id` int NOT NULL,
			`attachment_cat` varchar(255) NOT NULL,
			`ext` varchar(255) NOT NULL,
			`post_date` datetime NOT NULL,
			`size` varchar(255) NOT NULL,
			`date_created` datetime NOT NULL,
			`date_updated` datetime NOT NULL,
			PRIMARY KEY (`id`));";
		dbDelta($wmh_unused_media_post_id_sql);

		$wmh_used_media_post_id_sql = "CREATE TABLE IF NOT EXISTS " . $this->wmh_used_media_post_id . "(
		`id` int NOT NULL AUTO_INCREMENT,
		`post_id` int NOT NULL,
		`attachment_cat` varchar(255) NOT NULL,
		`ext` varchar(255) NOT NULL,
		`post_date` datetime NOT NULL,
		`size` varchar(255) NOT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		PRIMARY KEY (`id`));";
		dbDelta($wmh_used_media_post_id_sql);

		$wmh_temp_sql = "CREATE TABLE IF NOT EXISTS " . $this->wmh_temp . "(
			`id` int NOT NULL AUTO_INCREMENT,
			`post_id` int NOT NULL,
			`attachment_cat` varchar(255) NOT NULL,
			`ext` varchar(255) NOT NULL,
			`post_date` datetime NOT NULL,
			`size` varchar(255) NOT NULL,
			`date_created` datetime NOT NULL,
			`date_updated` datetime NOT NULL,
			PRIMARY KEY (`id`));";
		dbDelta($wmh_temp_sql);

		$wmh_whitelist_media_post_id_sql = "CREATE TABLE IF NOT EXISTS " . $this->wmh_whitelist_media_post_id . "(
		`id` int NOT NULL AUTO_INCREMENT,
		`post_id` int NOT NULL,
		`attachment_cat` varchar(255) NOT NULL,
		`ext` varchar(255) NOT NULL,
		`post_date` datetime NOT NULL,
		`size` varchar(255) NOT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		PRIMARY KEY (`id`));";
		dbDelta($wmh_whitelist_media_post_id_sql);

		$wmh_error_log_sql = "CREATE TABLE IF NOT EXISTS " . $this->wmh_error_log . "(
		`id` int NOT NULL AUTO_INCREMENT,
		`module` varchar(255) NOT NULL,
		`error` varchar(1000) NOT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		PRIMARY KEY (`id`));";
		dbDelta($wmh_error_log_sql);

		$wmh_deleted_media_sql = "CREATE TABLE IF NOT EXISTS " . $this->wmh_deleted_media . "(
		`id` int NOT NULL AUTO_INCREMENT,
		`post_id` varchar(1000) NOT NULL,
		`url` varchar(1000) NOT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		PRIMARY KEY (`id`));";
		dbDelta($wmh_deleted_media_sql);

		$wmh_save_scan_content_sql = "CREATE TABLE IF NOT EXISTS " . $this->wmh_save_scan_content . "(
			`id` int NOT NULL AUTO_INCREMENT,
			`wmh_key` varchar(265) NOT NULL,
			`wmh_value` longtext NULL,
			`date_created` datetime NOT NULL,
			`date_updated` datetime NOT NULL,
			PRIMARY KEY (`id`));";
		dbDelta($wmh_save_scan_content_sql);

		/* Known-used index for the dashboard scan.
		 * Built in Step 2 call 12 from all collected plugin/content data.
		 * Step 3 queries this table per attachment batch instead of loading all PHP arrays. */
		$wmh_scan_known_used_sql = "CREATE TABLE IF NOT EXISTS " . $this->wmh_scan_known_used . "(
			`id` int NOT NULL AUTO_INCREMENT,
			`post_id` int NULL,
			`basename` varchar(255) NULL,
			PRIMARY KEY (`id`),
			KEY `idx_post_id` (`post_id`),
			KEY `idx_basename` (`basename`(191)));";
		dbDelta($wmh_scan_known_used_sql);

		/* make array for store scan option data to WORDPRESS option. */
		$wmh_scan_option_data = array(
			'delete_data_on_uninstall_plugin' =>  'on',
			'error_log' => 'off',
			'media_per_page_input' => '10',
			'ex_file_ex' => 'ttf, otf, woff, woff2, cff, cff2, eot, css',
			'wmh_timeframes' => 'monthly',
			'number_of_image_scan' => '30',
			'menu_position_input' => ''
		);

		/* Preserve what the user already configured.
		 *
		 * This function runs on EVERY activation, not just the first, and it
		 * used to write the defaults array over the top unconditionally. So
		 * deactivating and reactivating the plugin silently reset every
		 * setting: a media-per-page of 45 came back as 10, a custom exclusion
		 * list reverted to the stock one, and the email recipient was lost.
		 * Nothing told the user, and nothing in the UI hinted that toggling
		 * the plugin would do it.
		 *
		 * Merging rather than skipping outright matters. Several callers read
		 * subkeys without isset(), for example wmh-my-cron-job.php reading
		 * wmh_timeframes, so an array saved by an older version that lacks a
		 * newer key must still come out complete. Saved values win; defaults
		 * only fill the gaps. A first activation has nothing saved, so this
		 * still produces exactly the defaults array. */
		$wmh_saved_option_data = get_option('wmh_scan_option_data');
		if (is_array($wmh_saved_option_data) && !empty($wmh_saved_option_data)) {
			$wmh_scan_option_data = array_merge($wmh_scan_option_data, $wmh_saved_option_data);
		}

		/* store scan button option data in wp_option. */
		update_option('wmh_scan_option_data',  $wmh_scan_option_data, 'no');

		/* Anonymous analytics permission: pending opt-in, shown as a notice on
		 * first use. Only set it when there is no answer on record. Writing
		 * 'pending' unconditionally would re-ask someone who already decided,
		 * and worse, it would discard an explicit 'off', which is the one
		 * answer we must never lose. */
		if (get_option('wmh_send_data_to_server_permission') === false) {
			update_option('wmh_send_data_to_server_permission', 'pending', false);
		}

		/* save scan status */
		$count = $this->conn->get_row('SELECT COUNT(id) as unused_media FROM ' . $this->wmh_unused_media_post_id . '', ARRAY_A);
		if (isset($count['unused_media']) && $count['unused_media'] > 0) {
			update_option('wmh_scan_status',  '1', 'no');
		} else {
			update_option('wmh_scan_status',  '0', 'no');
		}

		update_option('wmh_media_type_info', '', 'no');
		update_option('wmh_media_breakdown', '', 'no');
		update_option('wmh_plugin_db_version', '2.0.0', 'no');
		update_option('wmh_plugin_db_version_upgrade', '1', 'no');
	}

	/**
	 * Name the plugin that declared a class, from its own plugin header.
	 *
	 * Some classes are provided by more than one plugin. ACF is declared by
	 * Advanced Custom Fields and by Secure Custom Fields, its WordPress.org
	 * fork, and nothing stops another fork doing the same. Printing a fixed
	 * name in that situation credits a plugin the user may not have installed.
	 *
	 * Reflection answers it without a list to maintain: the file that declares
	 * the class sits inside exactly one plugin folder, and that plugin's header
	 * carries its real name. A bundled or renamed copy that is not a plugin in
	 * its own right falls back to the name passed in.
	 *
	 * @param string $class_name Class to trace.
	 * @param string $fallback   Name to use when the plugin cannot be identified.
	 * @return string
	 */
	public function fn_wmh_plugin_name_for_class($class_name, $fallback)
	{
		if (!class_exists($class_name)) {
			return $fallback;
		}

		try {
			$reflector = new ReflectionClass($class_name);
			$file = $reflector->getFileName();
		} catch (Exception $e) {
			return $fallback;
		}
		if (!$file) {
			return $fallback;
		}

		/* the folder directly under the plugins directory */
		$plugin_root = wp_normalize_path(WP_PLUGIN_DIR);
		$file = wp_normalize_path($file);
		if (strpos($file, $plugin_root . '/') !== 0) {
			return $fallback;
		}
		$relative = substr($file, strlen($plugin_root) + 1);
		$slug = strtok($relative, '/');
		if (!$slug) {
			return $fallback;
		}

		if (!function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		/* Match on the folder, not on a guessed file name: the file holding the
		 * header is not always named after the plugin. */
		foreach ((array) get_plugins() as $plugin_file => $plugin_data) {
			if (strtok($plugin_file, '/') !== $slug) {
				continue;
			}
			if (!empty($plugin_data['Name'])) {
				return $plugin_data['Name'];
			}
		}

		return $fallback;
	}

	/**
	 * Count media references the free version cannot read, per plugin.
	 *
	 * The old upsell notice fired on get_plugins(), so a site with ACF installed
	 * but no images in it saw the same warning as a site with 1,300 of them.
	 * This counts actual rows in each plugin's own meta storage that contain an
	 * uploads path, so the notice can report a real number or stay silent.
	 *
	 * Counting only. It does not resolve those references to attachments or mark
	 * anything as used, which is what Pro does.
	 *
	 * Two kinds of count live here and they are NOT interchangeable:
	 *   'values' = saved rows we can point at, one per stored reference.
	 *   'fields' = configured places media can be stored, not how many are used.
	 * ACF reports fields. Reconstructing its meta keys to count values needs the
	 * full ancestor path (a group prefixes its children, a repeater adds a row
	 * index, and both nest arbitrarily deep). Getting that wrong understates the
	 * gap, which is the dangerous direction: it tells someone the blind spot is
	 * small right before they delete a file that is in use. Counting the field
	 * definitions needs no path at all, so it cannot be wrong that way.
	 *
	 * @return array {
	 *     @type array $sources Plugin label => array('count' => int, 'unit' => 'values'|'fields').
	 *     @type int   $total   Total across sources that count values.
	 * }
	 */
	public function fn_wmh_count_unreadable_references()
	{
		/* What the key must track is the COUNTING LOGIC, not the release.
		 *
		 * This used to key on MH_FILE_VERSION alone. Upgrading from 6.0.0 used
		 * to leave the old cached answer in place for up to an hour, so new
		 * code served an old result: a site with 26 unreadable ACF fields was
		 * told it had none and that its results were "most likely complete",
		 * directly above the delete list. Putting the version in the key fixed
		 * that, but only across releases.
		 *
		 * It was not enough. Changing the counter WITHIN a version left the
		 * old answer cached under the same key: a store that had cached 436
		 * went on being told 436 after the counter was corrected to report
		 * about 2,900, because the version string had not moved. Testing a
		 * fix on a site that had already scanned showed no change at all.
		 *
		 * So hash THIS FILE into the key. Every query behind the count lives
		 * here, so any edit to how the count is produced changes the hash and
		 * retires the cached answer, with no version bump to remember and
		 * nothing to maintain by hand.
		 *
		 * Hashing the whole file rather than the one method is deliberate: an
		 * unrelated edit here also clears the cache, which costs one extra
		 * run of these queries and never serves a wrong number. Tracking the
		 * method alone would mean parsing, and getting that wrong fails
		 * silently in the direction that keeps stale counts.
		 *
		 * md5_file on a file PHP has just loaded is a stat and a read from
		 * the OS cache, against a query set this exists to run hourly.
		 *
		 * Falls back to the version if the file cannot be read, which is the
		 * old behaviour rather than no cache at all. */
		$wmh_logic_fingerprint = MH_FILE_VERSION;
		$wmh_self = __FILE__;
		if (is_readable($wmh_self)) {
			$wmh_logic_fingerprint = substr(md5_file($wmh_self), 0, 12);
		}
		$cache_key = 'wmh_unreadable_refs_' . $wmh_logic_fingerprint;

		$cached = get_transient($cache_key);
		if (is_array($cached)) {
			return $cached;
		}

		$found = array();
		$total = 0;

		/* ---- Advanced Custom Fields ----
		 * ACF stores field DEFINITIONS in the 'acf-field' post type: the field
		 * name is in post_excerpt, the type inside serialized post_content.
		 * Media fields then hold ATTACHMENT IDS (not URLs) under a key built
		 * from the whole ancestor chain, which is exactly why the free version's
		 * URL-based scan cannot see them.
		 *
		 * Count the field DEFINITIONS, not the saved values. A definition is a
		 * single row we read directly; a saved value would mean rebuilding ACF's
		 * meta key, and a group, a repeater and a flexible content block each
		 * prefix their children differently and nest without limit. Reporting
		 * configured fields is a smaller claim, and it is one we can make
		 * correctly on any site.
		 *
		 * Local field groups (acf-json in the theme) are not in the database at
		 * all, so a site using those reports nothing here and stays silent.
		 *
		 * Several plugins provide this class and this post type: ACF itself and
		 * Secure Custom Fields, the WordPress.org fork, which keeps both for
		 * compatibility. Everything below reads either one correctly, but
		 * printing a fixed "Advanced Custom Fields" names a plugin that may not
		 * be installed, which makes a correct count look like a wrong detection.
		 *
		 * So ask the class where it came from rather than testing for known
		 * plugin paths: whichever file declares it is the plugin in use, and its
		 * own header says what it is called. That names a fork we have never
		 * heard of just as well, and needs no list to maintain. */
		if (class_exists('ACF')) {
			$acf_label = $this->fn_wmh_plugin_name_for_class('ACF', __('Advanced Custom Fields', MEDIA_HYGIENE));

			/* wysiwyg is deliberately absent: those values hold uploads URLs in
			 * plain text, which the normal scan already reads. Counting them here
			 * would overstate the gap. */
			$acf_media_types = array('image', 'gallery', 'file', 'url', 'photo_gallery');

			$acf_definitions = $this->conn->get_col(
				"SELECT post_content FROM {$this->conn->posts}
				 WHERE post_type = 'acf-field' AND post_status = 'publish'"
			);

			$acf_field_count = 0;
			foreach ((array) $acf_definitions as $acf_definition) {
				$acf_parsed = maybe_unserialize($acf_definition);
				if (!is_array($acf_parsed) || !isset($acf_parsed['type'])) {
					continue;
				}
				if (in_array($acf_parsed['type'], $acf_media_types, true)) {
					$acf_field_count++;
				}
			}

			if ($acf_field_count > 0) {
				$found[$acf_label] = array(
					'count' => $acf_field_count,
					'unit'  => 'fields',
				);
			}
		}

		/* ---- WooCommerce ----
		 * A store keeps product images in three separate places, and the free
		 * scan reads NONE of them. It walks post_type post and page only, so
		 * every product sits outside the scan entirely, not merely partly read.
		 *
		 * This used to count galleries alone. On a real 2,968 item store that
		 * reported 436 while roughly 2,900 files were actually unreadable, so
		 * the notice understated the gap by a factor of six directly above a
		 * delete list. Understating is the dangerous direction: it tells
		 * someone the blind spot is small right before they delete a file that
		 * is in use on a product page.
		 *
		 * All three are counted as VALUES, and each number is a count of
		 * references, never a count of products. Mixing a per product figure
		 * into a per reference total would report 1 where the truth is 8.
		 *
		 *   _thumbnail_id          one featured image per product or variation
		 *   _product_image_gallery a comma separated list of attachment ids
		 *   post_content           images placed in the product description
		 *
		 * Variations are included because a variation carries its own featured
		 * image, and a store with per colour images keeps much of its media
		 * there. */
		if (class_exists('WooCommerce')) {
			$wc_count = 0;

			/* featured images on products and variations */
			$wc_count += (int) $this->conn->get_var(
				"SELECT COUNT(*) FROM {$this->conn->postmeta} AS pm
				 INNER JOIN {$this->conn->posts} AS p ON p.ID = pm.post_id
				 WHERE pm.meta_key = '_thumbnail_id'
				   AND pm.meta_value != ''
				   AND p.post_type IN ('product', 'product_variation')"
			);

			/* Gallery ids. One row holds a comma separated list, so count the
			 * ids rather than the rows: a product with eight gallery images is
			 * eight unreadable references, not one. */
			$wc_galleries = $this->conn->get_col(
				"SELECT meta_value FROM {$this->conn->postmeta}
				 WHERE meta_key = '_product_image_gallery' AND meta_value != ''"
			);
			foreach ((array) $wc_galleries as $wc_gallery) {
				$wc_ids = array_filter(array_map('trim', explode(',', (string) $wc_gallery)));
				$wc_count += count($wc_ids);
			}

			/* Images written into the product description.
			 *
			 * COUNT THE IMAGES, NOT THE PRODUCTS. A description holding eight
			 * images is eight unreadable references. Matching the same uploads
			 * pattern the scan uses on posts and pages keeps the number
			 * meaning the same thing it means there.
			 *
			 * Deduplicated per product, because one file repeated twice in a
			 * single description is still one file at risk. Read in chunks so
			 * a large catalogue does not load every description at once. */
			$wc_offset = 0;
			do {
				$wc_rows = $this->conn->get_col(
					$this->conn->prepare(
						"SELECT post_content FROM {$this->conn->posts}
						 WHERE post_type = 'product'
						   AND post_status NOT IN ('trash', 'auto-draft')
						   AND post_content LIKE '%/wp-content/uploads/%'
						 ORDER BY ID ASC
						 LIMIT %d OFFSET %d",
						200,
						$wc_offset
					)
				);
				foreach ((array) $wc_rows as $wc_content) {
					preg_match_all('/\/wp-content\/uploads\/[^"\')\s,>]+/', (string) $wc_content, $wc_m);
					if (empty($wc_m[0])) {
						continue;
					}
					$wc_found = array();
					foreach ($wc_m[0] as $wc_url) {
						$wc_base = wp_basename(rtrim($wc_url, '\\/.,;'));
						if ($wc_base !== '' && strpos($wc_base, '.') !== false) {
							$wc_found[$wc_base] = true;
						}
					}
					$wc_count += count($wc_found);
				}
				$wc_offset += 200;
			} while (count((array) $wc_rows) === 200);

			if ($wc_count > 0) {
				/* same reasoning as above: name whichever plugin declared the
				 * class, so a fork or a rename is reported as what it is */
				$wc_label = $this->fn_wmh_plugin_name_for_class('WooCommerce', 'WooCommerce');
				$found[$wc_label] = array(
					'count' => $wc_count,
					'unit'  => 'values',
				);
				$total += $wc_count;
			}
		}

		$result = array('sources' => $found, 'total' => $total);

		/* these queries are unindexed, so run them at most once an hour */
		set_transient($cache_key, $result, HOUR_IN_SECONDS);

		return $result;
	}

	public function fn_wmh_error_log($error_log_module = '', $error = '')
	{
		/* get scan option data. */
		$wmh_scan_option_data = get_option('wmh_scan_option_data', true);
		if (isset($wmh_scan_option_data['error_log'])) {
			if ($wmh_scan_option_data['error_log'] == 'on') {
				if ($error_log_module != '' && $error != '') {
					error_log($error, 0);
					$error_log_insert_array = array(
						'id' => '',
						'module' => $error_log_module,
						'error' => $error,
						'date_created' => date('Y-m-d H:i:s'),
						'date_updated' => date('Y-m-d H:i:s')
					);
					$this->conn->insert($this->wmh_error_log, $error_log_insert_array);
				}
			}
		}
	}

	public function fn_wmh_get_template($template_name, $args = array(), $tempate_path = '', $default_path = '')
	{

		/* $args used to be fed through extract(), which wp.org flags because it
		 * creates variables whose names come from the data. No caller has ever
		 * passed anything, so it ran on an empty array on every template load.
		 * The parameter stays for compatibility, and a template that needs it
		 * can read $args directly. */

		$template_file = $this->fn_wmh_locate_template($template_name, $tempate_path, $default_path);

		if (!file_exists($template_file)) :
			_doing_it_wrong(__FUNCTION__, sprintf('<code>%s</code> does not exist.', $template_file), '1.0.0');
			return;
		endif;

		include $template_file;
	}

	public function fn_wmh_locate_template($template_name, $template_path = '', $default_path = '')
	{

		// Set variable to search in wp-plugin-templates folder of theme.
		if (!$template_path) :
			$template_path = 'media-hygiene/';
		endif;

		// Set default plugin templates path.
		if (!$default_path) :
			$default_path = MH_FILE_PATH . 'templates/admin/'; // Path to the template folder
		endif;

		// Search template file in theme folder.
		$template = locate_template(array(
			$template_path . $template_name,
			$template_name
		));


		// Get plugins template file.
		if (!$template) :
			$template = $default_path . $template_name;
		endif;

		return apply_filters('wcpt_locate_template', $template, $template_name, $template_path, $default_path);
	}
}

$wmh_general = new wmh_general();
