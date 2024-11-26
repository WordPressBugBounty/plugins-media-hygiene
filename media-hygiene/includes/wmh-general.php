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

		/* Dashboard menu page */
		$dashboard_menu_page = add_submenu_page(
			'wmh-media-hygiene',
			esc_html(__('Dashboard', MEDIA_HYGIENE)),
			esc_html(__('Dashboard', MEDIA_HYGIENE)),
			'manage_options',
			'wmh-media-hygiene',
			array($this, 'fn_wmh_media_hygiene_handler')
		);

		/* Settings submenu page */
		$settings_menu_page = add_submenu_page(
			'wmh-media-hygiene',
			esc_html(__('Settings', MEDIA_HYGIENE)),
			esc_html(__('Settings', MEDIA_HYGIENE)),
			'manage_options',
			'wmh-settings',
			array($this, 'fn_wmh_settings_handler'),
			2
		);

		/* Get Help submenu page */
		$get_help_menu_page = add_submenu_page(
			'wmh-media-hygiene',
			esc_html(__('Get Help', MEDIA_HYGIENE)),
			esc_html(__('Get Help', MEDIA_HYGIENE)),
			'manage_options',
			'wmh-get-help',
			array($this, 'fn_wmh_get_help_handler'),
			4
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


		/* get scan option data. */
		/*$error_log_menu_page = '';
		$wmh_scan_option_data = get_option('wmh_scan_option_data', true);
		if (isset($wmh_scan_option_data) && isset($wmh_scan_option_data['error_log']) && $wmh_scan_option_data['error_log'] == 'on') {
			/* Error Log submenu page */
		/*$error_log_menu_page = add_submenu_page(
				'wmh-media-hygiene',
				esc_html(__('Error log', MEDIA_HYGIENE)),
				esc_html(__('Error log', MEDIA_HYGIENE)),
				'manage_options',
				'wmh-error-log',
				array($this, 'fn_wmh_error_log_handler'),
				4
			);
		} else {
			/* remove Error Log submenu page */
		/*remove_submenu_page('wmh-media-hygiene', 'wmh-error-log');
		}*/

		/* Go Pro */
		add_submenu_page(
			'wmh-media-hygiene',
			esc_html(__('Go Pro', MEDIA_HYGIENE)),
			'<span class="dashicons dashicons-lock" style="color: #ff8c00"></span><span id="go-pro-link" style="color: #ff8c00;font-weight: 500;display: inline-block;margin-left: 5px;margin-top: 2px;">' . __('Go Pro', MEDIA_HYGIENE) . '</span>',
			'manage_options',
			'https://mediahygiene.com/pricing/'
		);


		/* css. */
		add_action('admin_print_styles-' . $media_hygiene_menu_page, array($this, 'fn_wmh_media_hygiene_css'));
		add_action('admin_print_styles-' . $dashboard_menu_page, array($this, 'fn_wmh_media_hygiene_css'));
		add_action('admin_print_styles-' . $settings_menu_page, array($this, 'fn_wmh_media_hygiene_css'));
		add_action('admin_print_styles-' . $get_help_menu_page, array($this, 'fn_wmh_media_hygiene_css'));
		/*add_action('admin_print_styles-' . $error_log_menu_page, array($this, 'fn_wmh_media_hygiene_css'));*/
		/*add_action('admin_print_styles-' . $deleted_media_menu_page, array($this, 'fn_wmh_media_hygiene_css'));*/

		/* js. */
		add_action('admin_print_scripts-' . $media_hygiene_menu_page, array($this, 'fn_wmh_media_hygiene_js'));
		add_action('admin_print_scripts-' . $dashboard_menu_page, array($this, 'fn_wmh_media_hygiene_js'));
		add_action('admin_print_scripts-' . $settings_menu_page, array($this, 'fn_wmh_media_hygiene_js'));
		add_action('admin_print_scripts-' . $get_help_menu_page, array($this, 'fn_wmh_media_hygiene_js'));
		/*add_action('admin_print_scripts-' . $error_log_menu_page, array($this, 'fn_wmh_media_hygiene_js'));*/
		/*add_action('admin_print_scripts-' . $deleted_media_menu_page, array($this, 'fn_wmh_media_hygiene_js'));*/
	}

	public function fn_wmh_media_hygiene_css()
	{
		/* register. */
		wp_register_style('wmh-admin-css', plugins_url('/assets/css/wmh-admin.css', dirname(__FILE__)), false, MH_FILE_VERSION, 'all');
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
		wp_register_script('wmh-admin-js', plugins_url('/assets/js/wmh-admin.js', dirname(__FILE__)), array('jquery'), MH_FILE_VERSION, true);
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
			'trash_page_confirm_1' => esc_html(__('Are you sure  you want to trash ALL the unused media on this page?', MEDIA_HYGIENE)),
			'trash_page_confirm_2' => esc_html(__('I agree that I want to trash ALL the unused media files on this page and that I have made a backup of the website.', MEDIA_HYGIENE)),
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
		$gro_pro_link_html = '<a href="' .  esc_url('https://mediahygiene.com/pricing/')  . '" aria-label="' . esc_attr(__('Go Pro', MEDIA_HYGIENE)) . '" style="color: #ff8c00;font-weight:700" target="_blank">' . esc_html__('Go Pro', MEDIA_HYGIENE) . '</a>';
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

		/* make array for store scan option data to WORDPRESS option. */
		$wmh_scan_option_data = array(
			'delete_data_on_uninstall_plugin' =>  'on',
			'error_log' => 'off',
			'media_per_page_input' => '10',
			'ex_file_ex' => 'ttf, otf, woff, woff2, cff, cff2, eot, css',
			'wmh_timeframes' => 'quarterly',
			'number_of_image_scan' => '30',
			'menu_position_input' => ''
		);

		/* store scan button option data in wp_option. */
		update_option('wmh_scan_option_data',  $wmh_scan_option_data, 'no');

		/* anonymous analytics permission by default on */
		update_option('wmh_send_data_to_server_permission', 'on');

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

		if (is_array($args) && isset($args)) :
			extract($args);
		endif;

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
