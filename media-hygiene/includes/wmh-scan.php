<?php


defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class wmh_scan
{
	public $conn;
	public $wp_posts;
	public $wp_postmeta;
	public $wmh_unused_media_post_id;
	public $wmh_whitelist_media_post_id;
	public $wp_upload_dir;
	public $basedir;
	public $wmh_temp;
	public $wmh_deleted_media;
	public $wmh_used_media_post_id;
	public $wmh_save_scan_content;

	public function __construct()
	{

		global $wpdb;
		$this->conn = $wpdb;
		$this->wp_upload_dir = wp_upload_dir();
		$this->basedir = $this->wp_upload_dir['basedir'];
		$this->wp_posts = $this->conn->prefix . 'posts';
		$this->wp_postmeta = $this->conn->prefix . 'postmeta';
		$this->wmh_unused_media_post_id = $this->conn->prefix . MH_PREFIX . 'unused_media_post_id';
		$this->wmh_whitelist_media_post_id = $this->conn->prefix . MH_PREFIX . 'whitelist_media_post_id';
		$this->wmh_temp = $this->conn->prefix . MH_PREFIX . 'temp';
		$this->wmh_deleted_media = $this->conn->prefix . MH_PREFIX . 'deleted_media';
		$this->wmh_used_media_post_id = $this->conn->prefix . MH_PREFIX . 'used_media_post_id';
		$this->wmh_save_scan_content = $this->conn->prefix . MH_PREFIX . 'save_scan_content';

		/* unused images scan ajax call */
		add_action('wp_ajax_scan_unused_images', array($this, 'fn_wmh_scan_unused_images'));
		/* fetch data from database */
		add_action('wp_ajax_fetch_data_from_database', array($this, 'fn_wmh_fetch_data_from_database'));
		/* scanning data */
		add_action('wp_ajax_scanning_data', array($this, 'fn_wmh_scanning_data'));

		/* trash  */
		add_action('wp_ajax_row_action_trash', array($this, 'fn_wmh_row_action_trash'));


		/* delete attachment. */
		//add_action('delete_attachment', array($this, 'fn_wmh_delete_attachment_media'));
		/* whitelist media. */
		add_action('wp_ajax_whitelist_single_image_call', array($this, 'fn_wmh_whitelist_single_image_call'));
		/* blacklist media. */
		add_action('wp_ajax_blacklist_single_image_call', array($this, 'fn_wmh_blacklist_single_image_call'));
		/* filter data ajax call */
		add_action('wp_ajax_filter_data_ajax_call', array($this, 'fn_wmh_filter_data_ajax_call'));
		/* bulk action trash */
		add_action('wp_ajax_bulk_action_trash', array($this, 'fn_wmh_bulk_action_trash'));
		/* bulk action blacklist to whitelist */
		add_action('wp_ajax_bulk_action_to_whitelist', array($this, 'fn_wmh_bulk_action_to_whitelist'));
		/* bulk action whitelist to blacklist */
		add_action('wp_ajax_bulk_action_to_blacklist', array($this, 'fn_wmh_bulk_action_to_blacklist'));

		/* delete page media */
		//add_action('wp_ajax_delete_page_media', array($this, 'fn_wmh_delete_page_media'));

		/* trash page media */
		add_action('wp_ajax_trash_page_media', array($this, 'fn_wmh_trash_page_media'));

		/* bulk action restore for trash */
		add_action('wp_ajax_bulk_action_trash_to_restore', array($this, 'fn_wmh_bulk_action_trash_to_restore'));
		/* row action single image restore */
		add_action('wp_ajax_restore_single_image_call', array($this, 'fn_wmh_restore_single_image_call'));
		/* bulk restore media from trash */
		add_action('wp_ajax_wmh_bulk_restore', array($this, 'fn_wmh_bulk_restore'));
		/* delete permanently single image call */
		add_action('wp_ajax_delete_permanently_single_image_call', array($this, 'fn_wmh_delete_permanently_single_image_call'));
		/* bulk action delete */
		add_action('wp_ajax_bulk_action_delete', array($this, 'fn_wmh_bulk_action_delete'));
		/* delete permanently */
		add_action('wp_ajax_wmh_delete_permanently', array($this, 'fn_wmh_delete_permanently'));
		/* special process - fetch data from elementor */
		add_action('wp_ajax_fetch_data_from_elementor', array($this, 'fn_wmh_fetch_data_from_elementor'));
	}

	public function fn_wmh_scan_unused_images()
	{
		if (!current_user_can('manage_options')) {
			return false;
		}

		/* check nonce here. */
		$wp_nonce = sanitize_text_field($_POST['nonce']);
		if (!wp_verify_nonce($wp_nonce, 'scan_unused_images_nonce')) {
			die(esc_html(__('Security check. Hacking not allowed', MEDIA_HYGIENE)));
		}

		$ajax_call = (int) sanitize_text_field($_POST['ajax_call']);
		$progress_bar = (float) sanitize_text_field($_POST['progress_bar']);

		/* default response */
		$flg = 0;
		$message = __('Something is wrong', MEDIA_HYGIENE);
		$output = array(
			'flg' => $flg,
			'message' => $message
		);

		/* check ajax call number */
		if ($ajax_call == 1) {

			$new_table_status = get_option('wmh_create_new_table_save_scan_content');
			if ($new_table_status == '' && $new_table_status != 'yes') {
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				/* wmh_save_scan_content */
				$wmh_save_scan_content_sql = "CREATE TABLE IF NOT EXISTS " . $this->wmh_save_scan_content . "(
                `id` int NOT NULL AUTO_INCREMENT,
                `wmh_key` varchar(265) NOT NULL,
                `wmh_value` longtext NULL,
                `date_created` datetime NOT NULL,
                `date_updated` datetime NOT NULL,
                PRIMARY KEY (`id`));";
				dbDelta($wmh_save_scan_content_sql);
				update_option('wmh_create_new_table_save_scan_content', 'yes');
				/* save content option delete */
				delete_option('wmh_post_content_data');
				delete_option('wmh_page_content_data');
				delete_option('wmh_page_post_feature_image_ids_data');
				delete_option('wmh_elementor_data');
				delete_option('wmh_divi_post_content_data');
				delete_option('wmh_bricks_post_content_data');
				delete_option('wmh_bricks_temp_header_data');
				delete_option('wmh_bricks_temp_footer_data');
				delete_option('wmh_bricks_temp_page_data');
				delete_option('wmh_vc_post_content_data');
				delete_option('wmh_vc_tmp_data_data');
				delete_option('wmh_enfold_layerslider_data');
				delete_option('wmh_theme_mode_data');
				delete_option('wmh_ocean_logo_data');
				delete_option('wmh_whitelist_media_post_ids');
			}

			$attachment_counts = (array) wp_count_attachments();
			if (isset($attachment_counts['trash'])) {
				unset($attachment_counts['trash']);
			}
			$total_counts = array_sum($attachment_counts);

			if ($total_counts == '' || $total_counts == 0) {
				update_option('wmh_media_count', '0', 'no');
				update_option('wmh_total_media_size', '0', 'no');
				update_option('wmh_total_unused_media_count', '0', 'no');
				update_option('wmh_unused_media_size', '0', 'no');
				update_option('wmh_use_media_count', '0', 'no');
				update_option('wmh_use_media_size', '0', 'no');
				update_option('wmh_media_type_info', '', 'no');
				update_option('wmh_media_breakdown', '', 'no');
				/* set status for last scan, it is intruppted or not */
				update_option('wmh_scan_complete', 'completed');
				$flg = 0;
				$message = __('There is no media to scan', MEDIA_HYGIENE);
				$output = array(
					'flg' => $flg,
					'message' => $message
				);
				echo json_encode($output);
				wp_die();
			}

			/* update attachments ids */
			update_option('wmh_all_attachment_ids', $total_counts, 'no');

			$start_time = date('Y-m-d h:i:s');
			update_option('wmh_start_time', $start_time, 'no');
			update_option('wmh_scan_status', '1', 'no');
			update_option('wmh_scan_complete', 'interrupted', 'no');
			delete_option('wmh_page_url_content');

			$flg = 1;
			$output = array(
				'flg' => $flg,
				'ajax_call' => $ajax_call,
			);
			echo json_encode($output);
			wp_die();
		}

		if ($ajax_call == 2) {
			/* get URL list for scan */
			$urls = $this->fn_wmh_get_url_list_for_scan();

			update_option('wmh_url_list', $urls, 'no');
			update_option('wmh_url_list_count', count($urls), 'no');

			if (empty($urls)) {
				$flg = 2;
				$output = array(
					'flg' => $flg,
				);
				echo json_encode($output);
				wp_die();
			} else {
				$flg = 1;
				$output = array(
					'flg' => $flg,
					'ajax_call' => $ajax_call,
				);
				echo json_encode($output);
				wp_die();
			}
		}

		/* get URL list for scan */
		$urls = (array) get_option('wmh_url_list');
		$total_urls = (int) get_option('wmh_url_list_count');

		/* by pagination */
		$urls_to_scan = [];
		$per_url = 1;
		$offset = ($ajax_call - 1) * $per_url;
		$urls_to_scan = array_slice($urls, $offset, $per_url);

		/* count total ajax call */
		$total_ajax_call = (int) ceil(($total_urls / $per_url));

		/* get progress bar width and percentage */
		$percentage = (float) (100 / $total_ajax_call);
		$progress_bar_width = number_format(($progress_bar + $percentage), 2);

		/* get used images by url content */
		$results = $this->fn_get_content_from_url($urls_to_scan);

		/* check option data */
		$previous_data = get_option('wmh_page_url_content');
		if (is_array($previous_data) && !empty($previous_data)) {
			if (is_array($results) && !empty($results)) {
				$new_results = array_merge($previous_data, $results);
			} else {
				$new_results = $previous_data;
			}
		} else {
			$new_results = $results;
		}
		update_option('wmh_page_url_content', array_unique($new_results), 'no');

		if ($ajax_call == ($total_ajax_call + 2)) {

			delete_option('wmh_url_list');
			delete_option('wmh_url_list_count');

			$flg = 2;
			$message = __('Scan completed.', MEDIA_HYGIENE);
			$output = array(
				'flg' => $flg,
				'message' => $message
			);
		} else {
			$flg = 1;
			$output = array(
				'flg' => $flg,
				'total_ajax_call' => $total_ajax_call,
				'ajax_call' => $ajax_call,
				'progress_bar_width' => $progress_bar_width
			);
		}
		echo json_encode($output);
		wp_die();
	}

	public function fn_wmh_fetch_data_from_database()
	{

		/* default response */
		$flg = 0;

		/* ajax call */
		$ajax_call = sanitize_text_field($_POST['ajax_call']);

		/* progress bar */
		$progress_bar = sanitize_text_field($_POST['progress_bar']);

		/* get all plugin list */
		$all_plugins = get_plugins();

		/* get all theme list */
		$all_themes = wp_get_themes();

		if ($ajax_call == 1) {
			/* copy unused media table data in temp table */
			$this->conn->query(' TRUNCATE TABLE ' . $this->wmh_temp . ' ');
			$this->conn->query('INSERT INTO ' . $this->wmh_temp . ' SELECT * FROM ' . $this->wmh_unused_media_post_id . ' ');
			$this->conn->query(' TRUNCATE TABLE ' . $this->wmh_unused_media_post_id . ' ');
			$this->conn->query(' TRUNCATE TABLE ' . $this->wmh_used_media_post_id . ' ');
			$this->conn->query(' TRUNCATE TABLE ' . $this->wmh_save_scan_content . ' ');
			$flg = 1;
			$progress_bar = 0;
		}

		/* save post content data*/
		if ($ajax_call == 2) {
			$post_content = $this->fn_wmh_check_post_content();
			$mh_key = 'wmh_post_content_data';
			$this->fn_wmh_insert_save_content($post_content, $mh_key);
			$flg = 1;
			$progress_bar = 10;
		}

		/* save page content data */
		if ($ajax_call == 3) {
			$page_content = $this->fn_wmh_check_page_content();
			$mh_key = 'wmh_page_content_data';
			$this->fn_wmh_insert_save_content($page_content, $mh_key);
			$flg = 1;
			$progress_bar = 20;
		}

		/* save page and post feature images ids */
		if ($ajax_call == 4) {
			$page_post_feature_images_ids = $this->fn_wmh_get_page_post_feature_images_ids();
			$mh_key = 'wmh_page_post_feature_image_ids_data';
			$this->fn_wmh_insert_save_content($page_post_feature_images_ids, $mh_key);
			$flg = 1;
			$progress_bar = 30;
		}

		/* save elemntor data */
		if ($ajax_call == 5) {
			if ((array_key_exists('elementor/elementor.php', $all_plugins)) || (array_key_exists('elementor-pro/elementor-pro.php', $all_plugins))) {
				$flg = 3;
			} else {
				$flg = 1;
				$progress_bar = 40;
			}
		}

		/*  save Divi theme data */
		if ($ajax_call == 6) {
			$divi_post_content = array();
			if (array_key_exists('Divi', $all_themes)) {
				$wmh_divi = new wmh_divi();
				$divi_post_content = $wmh_divi->fn_wmh_divi_get_data();
			}
			$mh_key = 'wmh_divi_post_content_data';
			$this->fn_wmh_insert_save_content($divi_post_content, $mh_key);

			$flg = 1;
			$progress_bar = 50;
		}

		/*  save Bricks data  */
		if ($ajax_call == 7) {
			$bricks_post_content = array();
			$bricks_temp_data_header = array();
			$bricks_temp_data_footer = array();
			$bricks_temp_data_page = array();
			if (array_key_exists('bricks', $all_themes)) {
				$wmh_bricks = new wmh_bricks();
				/* get bricks data */
				$bricks_post_content = $wmh_bricks->fn_wmh_bricks_get_data();
				/* get bricks template data for header */
				$bricks_temp_data_header = $wmh_bricks->fn_wmh_get_bricks_template_header_data();
				/* get bricks template data for footer */
				$bricks_temp_data_footer = $wmh_bricks->fn_wmh_get_bricks_template_footer_data();
				/* get bricks template data for page */
				$bricks_temp_data_page = $wmh_bricks->fn_wmh_get_bricks_template_page_data();
			}

			$mh_key = 'wmh_bricks_post_content_data';
			$this->fn_wmh_insert_save_content($bricks_post_content, $mh_key);

			$mh_key = 'wmh_bricks_temp_header_data';
			$this->fn_wmh_insert_save_content($bricks_temp_data_header, $mh_key);

			$mh_key = 'wmh_bricks_temp_footer_data';
			$this->fn_wmh_insert_save_content($bricks_temp_data_footer, $mh_key);

			$mh_key = 'wmh_bricks_temp_page_data';
			$this->fn_wmh_insert_save_content($bricks_temp_data_page, $mh_key);

			$flg = 1;
			$progress_bar = 60;
		}

		/* save visual composer theme builder data */
		if ($ajax_call == 8) {
			$vc_post_content = array();
			$vc_tmp_data = array();
			if (array_key_exists('visualcomposer/plugin-wordpress.php', $all_plugins) || array_key_exists('visualcomposer-pro/plugin-wordpress.php', $all_plugins)) {
				$wmh_vc = new wmh_vc();
				/* get visual composer data */
				$vc_post_content = $wmh_vc->fn_get_visual_composer_data();
				/* get visual composer template data */
				$vc_tmp_data = $wmh_vc->fn_get_visual_composer_template_data();
			}

			$mh_key = 'wmh_vc_post_content_data';
			$this->fn_wmh_insert_save_content($vc_post_content, $mh_key);

			$mh_key = 'wmh_vc_tmp_data_data';
			$this->fn_wmh_insert_save_content($vc_tmp_data, $mh_key);

			$flg = 1;
			$progress_bar = 70;
		}

		/* save enfold theme layer slider data */
		if ($ajax_call == 9) {
			$enfold_layerslider_data = array();
			if (array_key_exists('enfold', $all_themes)) {
				$wmh_enfold = new wmh_enfold();
				$enfold_layerslider_data = $wmh_enfold->fn_wmh_enfold_get_layerslider_data();
			}

			$mh_key = 'wmh_enfold_layerslider_data';
			$this->fn_wmh_insert_save_content($enfold_layerslider_data, $mh_key);

			$flg = 1;
			$progress_bar = 80;
		}

		/* get oceanWP theme custom logo and set background image */
		if ($ajax_call == 10) {
			$theme_mode_data = array();
			$ocean_logo = array();
			if (array_key_exists('oceanwp', $all_themes)) {
				$wmh_ocean_wp = new wmh_ocean_wp();
				/* custom logo and set background image */
				$theme_mode_data = $wmh_ocean_wp->fn_wmh_theme_mods_oceanwp();
				/* ocean custom logo and ocean custom retina logo */
				$ocean_logo = $wmh_ocean_wp->fn_wmh_get_ocean_wp_library_data();
				/* oceanWP theme data scan by page and post */
			}

			$mh_key = 'wmh_theme_mode_data';
			$this->fn_wmh_insert_save_content($theme_mode_data, $mh_key);

			$mh_key = 'wmh_ocean_logo_data';
			$this->fn_wmh_insert_save_content($ocean_logo, $mh_key);

			$flg = 1;
			$progress_bar = 90;
		}

		if ($ajax_call == 11) {
			$whitelist_media_post_ids = array();
			/* get whitelist media post id to ignore in blaklist media */
			$wl_sql = 'SELECT post_id FROM ' . $this->wmh_whitelist_media_post_id . '';
			$wl_data = $this->conn->get_results($wl_sql, ARRAY_A);
			if (isset($wl_data) && !empty($wl_data)) {
				foreach ($wl_data as $val) {
					if ($val['post_id']) {
						array_push($whitelist_media_post_ids, $val['post_id']);
					}
				}
			}
			$mh_key = 'wmh_whitelist_media_post_ids';
			$this->fn_wmh_insert_save_content($whitelist_media_post_ids, $mh_key);

			$flg = 2;
			$progress_bar = 100;
		}

		$output = array(
			'flg' => $flg,
			'ajax_call' => $ajax_call,
			'progress_bar_width' => $progress_bar
		);
		echo json_encode($output);
		wp_die();
	}

	public function fn_wmh_insert_save_content($content = array(), $mh_key = '')
	{
		$insert_data_array = [
			'id' => '',
			'wmh_key' => $mh_key,
			'wmh_value' => json_encode($content),
			'date_created' => date('Y-m-d H:i:s'),
			'date_updated' => date('Y-m-d H:i:s')
		];
		$this->conn->insert($this->wmh_save_scan_content, $insert_data_array);
	}

	public function fn_wmh_get_save_content_data($mh_key = '')
	{
		$content = array();
		$sql = ' SELECT wmh_value FROM ' . $this->wmh_save_scan_content . ' WHERE wmh_key = "' . $mh_key . '" ';
		$data = $this->conn->get_row($sql, ARRAY_A);
		if (isset($data['wmh_value'])) {
			$content = json_decode($data['wmh_value'], true);
		}
		return $content;
	}

	public function fn_wmh_get_url_list_for_scan()
	{
		$url_array = [];

		/* default post type */
		$default_post_type = array(
			'page',
			'post'
		);

		$results = get_posts([
			'post_type' => $default_post_type,
			'fields' => 'ids',
			'posts_per_page'  => -1,
			'post_status' => array('publish', 'draft')
		]);

		if (!empty($results)) {
			foreach ($results as $id) {
				$url = get_permalink($id);
				if ($url) {
					array_push($url_array, $url);
				}
			}
		}

		return $url_array;
	}

	public function fn_get_content_from_url($urls_to_scan = array())
	{
		$results_array = [];

		if (!empty($urls_to_scan)) {
			foreach ($urls_to_scan as $url) {
				$response = wp_remote_get($url);
				if (is_wp_error($response)) {
					/* handle error */
				} else {
					$page_content = wp_remote_retrieve_body($response);
					/* src */
					/* $reg = '/<[^>]+\bsrc\s*=\s*[\'"]([^\'"]*\/wp-content\/uploads\/[^\'"]*)[\'"]/'; */
					$reg = '/src=(".*?"|\'.*?\'|.*?)[ >]/i';
					preg_match_all($reg, $page_content, $results);
					/* href */
					/* $reg1 = '/<[^>]+\bhref\s*=\s*[\'"]([^\'"]*\/wp-content\/uploads\/[^\'"]*)[\'"]/'; */
					$reg1 = '/href=(".*?"|\'.*?\'|.*?)[ >]/i';
					preg_match_all($reg1, $page_content, $results1);
				}
			}
		}

		/* for src */
		if (!empty($results)) {
			unset($results[0]);
			foreach ($results[1] as $val) {
				if (str_contains($val, 'wp-content/uploads')) {
					array_push($results_array, $val);
				}
			}
		}

		/* for href */
		if (!empty($results1)) {
			unset($results1[0]);
			foreach ($results1[1] as $val1) {
				if (str_contains($val1, 'wp-content/uploads')) {
					array_push($results_array, $val1);
				}
			}
		}

		return array_unique($results_array);
	}

	public function fn_wmh_scanning_data()
	{

		/* default response */
		$flg = 0;
		$message = __('Something is wrong', MEDIA_HYGIENE);
		$output = array(
			'flg' => $flg,
			'message' => $message
		);

		/* ajax call */
		$ajax_call = sanitize_text_field($_POST['ajax_call']);

		/* progress bar */
		$progress_bar = sanitize_text_field($_POST['progress_bar']);

		/* get scan option data */
		$wmh_scan_option_data =  get_option('wmh_scan_option_data', true);

		/* exclude extension string */
		$exclude_exs = $wmh_scan_option_data['ex_file_ex'];

		/* total attachments count */
		$total_attachments = (int) get_option('wmh_all_attachment_ids');

		/* get post_type attachment data by offset */
		$per_post = $wmh_scan_option_data['number_of_image_scan'];
		$offset = ($ajax_call - 1) * $per_post;
		$attachments = $this->fn_wmh_get_post_type_attachment_data($offset, $per_post);

		/* count total ajax call */
		$total_ajax_call = ceil(($total_attachments / $per_post));

		/* get progress bar width and percentage */
		$percentage = (100 / $total_ajax_call);
		$progress_bar_width = number_format(($progress_bar + $percentage), 2);

		/* get site logo */
		$site_logo = get_option('site_logo');

		/* get site icon */
		$site_icon =  get_option('site_icon');

		/* get page url scan content data */
		$wmh_page_url_content = get_option('wmh_page_url_content');

		/* get post content */
		$mh_key = 'wmh_post_content_data';
		$post_content = $this->fn_wmh_get_save_content_data($mh_key);

		/* get page content */
		$mh_key = 'wmh_page_content_data';
		$page_content = $this->fn_wmh_get_save_content_data($mh_key);

		/* get page and post feature images ids */
		$mh_key = 'wmh_page_post_feature_image_ids_data';
		$page_post_thumbnail_ids = $this->fn_wmh_get_save_content_data($mh_key);

		/* get elementor data, with post and page content build by elementor including prime slider addons */
		$mh_key = 'wmh_elementor_data';
		$elementor_result = array();
		$sql = ' SELECT wmh_value FROM ' . $this->wmh_save_scan_content . ' WHERE wmh_key = "' . $mh_key . '" ';
		$elementor_content = $this->conn->get_results($sql, ARRAY_A);
		if (!empty($elementor_content)) {
			foreach ($elementor_content as $ec) {
				$elementor_result_loop = json_decode($ec['wmh_value'], true);
				if (!empty($elementor_result_loop)) {
					foreach ($elementor_result_loop as $erl) {
						array_push($elementor_result, $erl);
					}
				}
			}
			$elementor_result = array_unique($elementor_result);
		}

		/*  get Divi theme data */
		$mh_key = 'wmh_divi_post_content_data';
		$divi_post_content = $this->fn_wmh_get_save_content_data($mh_key);

		/* get Bricks data */
		/* get bricks data content */
		$mh_key = 'wmh_bricks_post_content_data';
		$bricks_post_content =  $this->fn_wmh_get_save_content_data($mh_key);
		/* get bricks template data for header */
		$mh_key = 'wmh_bricks_temp_header_data';
		$bricks_temp_data_header =  $this->fn_wmh_get_save_content_data($mh_key);
		/* get bricks template data for footer */
		$mh_key = 'wmh_bricks_temp_footer_data';
		$bricks_temp_data_footer =  $this->fn_wmh_get_save_content_data($mh_key);
		/* get bricks template data for page */
		$mh_key = 'wmh_bricks_temp_page_data';
		$bricks_temp_data_page =  $this->fn_wmh_get_save_content_data($mh_key);

		/* get visual composer data */
		$mh_key = 'wmh_vc_post_content_data';
		$vc_post_content =  $this->fn_wmh_get_save_content_data($mh_key);
		/* get visual composer template data */
		$mh_key = 'wmh_vc_tmp_data_data';
		$vc_tmp_data =  $this->fn_wmh_get_save_content_data($mh_key);

		/* get enfold theme layer slider data */
		$mh_key = 'wmh_enfold_layerslider_data';
		$enfold_layerslider_data = $this->fn_wmh_get_save_content_data($mh_key);

		/* get oceanWP theme custom logo and set background image */
		/* custom logo and set background image */
		$mh_key = 'wmh_theme_mode_data';
		$theme_mode_data = $this->fn_wmh_get_save_content_data($mh_key);
		/* ocean custom logo and ocean custom retina logo */
		$mh_key = 'wmh_ocean_logo_data';
		$ocean_logo =  $this->fn_wmh_get_save_content_data($mh_key);

		/* oceanWP theme data scan by page and post */
		/* Astra theme data scan by post and page */
		/* Avada theme data autometic scan by page and post */
		/* WP bakery theme data autometic scan by page and post */
		/* Beaver builder theme data autometic scan by page and post */
		/* Enfold theme data scan by page and post, here some content not scan by page and post like Advanced layer slider */
		/* Flatsome theme data scan by page and post */

		/* get whitelist media id to avoid whitelist media */
		$mh_key = 'wmh_whitelist_media_post_ids';
		$wl_post_ids = $this->fn_wmh_get_save_content_data($mh_key);

		if (!empty($attachments)) {
			/* size array */
			$media_size_array = array();
			/* get all intermediate image sizes */
			$size_type = get_intermediate_image_sizes();
			array_push($size_type, 'full');
			foreach ($attachments as $id) {
				$all_urls = array();
				/* check whitelist media and ignore */
				if (!empty($wl_post_ids)) {
					if (in_array($id, $wl_post_ids)) {
						continue;
					}
				}
				/* default flg */
				$flg = 0;
				/* get post mime type */
				$post_mime_type = sanitize_mime_type(get_post_mime_type($id));
				if (str_contains($post_mime_type, 'image')) {
					$guid = wp_get_original_image_url($id);
					/* exclude dir file already make use */
					$ext = pathinfo($guid, PATHINFO_EXTENSION);
					if (str_contains($exclude_exs, $ext)) {
						$flg = 1;
						continue;
					}
					array_push($all_urls, $guid);
					foreach ($size_type as $size) {
						$guid = wp_get_attachment_image_url($id, $size);
						array_push($all_urls, $guid);
					}
					$all_urls = array_unique($all_urls);
				} else {
					$guid = wp_get_attachment_url($id);
					/* exclude dir file already make use */
					$ext = pathinfo($guid, PATHINFO_EXTENSION);
					if (str_contains($exclude_exs, $ext)) {
						$flg = 1;
						continue;
					}
					array_push($all_urls, $guid);
					$all_urls = array_unique($all_urls);
				}

				/* check site logo */
				if ($site_logo != '') {
					if ($site_logo == $id) {
						$flg = 1;
					}
				}

				/* check site icon. */
				if ($site_icon != '') {
					if ($site_icon == $id) {
						$flg = 1;
					}
				}

				/* check page and post thumbnail id*/
				if (isset($page_post_thumbnail_ids) && !empty($page_post_thumbnail_ids)) {
					if (in_array($id, $page_post_thumbnail_ids)) {
						$flg = 1;
					}
				}

				/* check oceanWP theme data */
				if (!empty($theme_mode_data)) {
					/* check oceanWP custom logo */
					if ($theme_mode_data['custom_logo'] != '') {
						if ($theme_mode_data['custom_logo'] ==  $id) {
							$flg = 1;
						}
					}
				}

				/* check logos */
				if (!empty($ocean_logo)) {
					/* check custom logo */
					if (!empty($ocean_logo['ocean_custom_logo'])) {
						if (in_array($id, $ocean_logo['ocean_custom_logo'])) {
							$flg = 1;
						}
					}
					/* check custom retina logo */
					if (!empty($ocean_logo['ocean_custom_retina_logo'])) {
						if (in_array($id, $ocean_logo['ocean_custom_retina_logo'])) {
							$flg = 1;
						}
					}
				}

				/* check divi gallery ids */
				if (isset($divi_post_content) && !empty($divi_post_content)) {
					if (!empty($divi_post_content['gallery_ids'])) {
						foreach ($divi_post_content['gallery_ids'] as $post_ids) {
							/* check Divi theme data for type gallery by id. */
							if (str_contains($post_ids, (string) $id)) {
								$flg = 1;
								break;
							}
						}
					}
				}

				if (!empty($all_urls)) {
					/* basename of url */
					foreach ($all_urls as $urls) {
						$url = wp_basename($urls);
						/* check url content */
						if (!empty($wmh_page_url_content)) {
							foreach ($wmh_page_url_content as $check_url) {
								if (str_contains($check_url, $url)) {
									$flg = 1;
									break;
								}
							}
						}

						/* check post content */
						if (!empty($post_content) && (is_array($post_content) || is_object($post_content))) {
							foreach ($post_content as $pc) {
								if ($pc != '') {
									if (str_contains($pc, $url)) {
										$flg = 1;
										break;
									}
								}
							}
						}

						/* check page content */
						if (!empty($page_content) && (is_array($page_content) || is_object($page_content))) {
							foreach ($page_content as $pc) {
								if ($pc != '') {
									if (str_contains($pc, $url)) {
										$flg = 1;
										break;
									}
								}
							}
						}

						/* check elementor data. */
						if (!empty($elementor_result)) {
							foreach ($elementor_result as $er) {
								if ($er != '') {
									if (str_contains($er, $url)) {
										$flg = 1;
										break;
									}
								}
							}
						}

						/* some of module of divi scan by guid*/
						if (isset($divi_post_content) && !empty($divi_post_content)) {
							if (!empty($divi_post_content['content'])) {
								foreach ($divi_post_content['content'] as $post_content) {
									if (str_contains($post_content, $url)) {
										$flg = 1;
										break;
									}
								}
							}
						}

						/* check Bricks data */
						if (isset($bricks_post_content) && !empty($bricks_post_content)) {
							foreach ($bricks_post_content as $br_url) {
								if ($br_url != '') {
									if (str_contains($br_url,  $url)) {
										$flg = 1;
										break;
									}
								}
							}
						}

						/* check bricks template data for header */
						if (isset($bricks_temp_data_header) && !empty($bricks_temp_data_header)) {
							foreach ($bricks_temp_data_header as $temp_data) {
								if ($temp_data != '') {
									if (str_contains($temp_data,  $url)) {
										$flg = 1;
										break;
									}
								}
							}
						}

						/* check bricks template data for footer */
						if (isset($bricks_temp_data_footer) && !empty($bricks_temp_data_footer)) {
							foreach ($bricks_temp_data_footer as $temp_data) {
								if ($temp_data != '') {
									if (str_contains($temp_data,  $url)) {
										$flg = 1;
										break;
									}
								}
							}
						}

						/* check bricks template data for page */
						if (isset($bricks_temp_data_page) && !empty($bricks_temp_data_page)) {
							foreach ($bricks_temp_data_page as $temp_data) {
								if ($temp_data != '') {
									if (str_contains($temp_data,  $url)) {
										$flg = 1;
										break;
									}
								}
							}
						}

						/* check visual composer data */
						if (isset($vc_post_content) && !empty($vc_post_content)) {
							foreach ($vc_post_content as $vc_content) {
								if ($vc_content != '') {
									if (str_contains($vc_content,  $url)) {
										$flg = 1;
										break;
									}
								}
							}
						}

						/* check visual composer data for template */
						if (isset($vc_tmp_data) && !empty($vc_tmp_data)) {
							foreach ($vc_tmp_data as $vc_data) {
								if ($vc_data != '') {
									$json_vc_data = json_encode($vc_data);
									if (str_contains($json_vc_data,  $url)) {
										$flg = 1;
										break;
									}
								}
							}
						}

						/* check Enfold theme layer slider data */
						if (isset($enfold_layerslider_data) && !empty($enfold_layerslider_data)) {
							foreach ($enfold_layerslider_data as $layer_data) {
								if ($layer_data) {
									if (str_contains($layer_data,  $url)) {
										$flg = 1;
										break;
									}
								}
							}
						}

						/* check oceanWP theme data */
						if (!empty($theme_mode_data)) {
							/* check oceanWP set background image */
							if ($theme_mode_data['background_image'] != '') {
								if (str_contains($theme_mode_data['background_image'],  $url)) {
									$flg = 1;
								}
							}
						}
					}
				}

				$post_cat = 'others';
				if (str_contains($post_mime_type, 'image')) {
					$post_cat = 'images';
				} else if (str_contains($post_mime_type, 'application')) {
					$post_cat = 'documents';
				} else if (str_contains($post_mime_type, 'video')) {
					$post_cat =  'video';
				} else if (str_contains($post_mime_type, 'audio')) {
					$post_cat = 'audio';
				} else {
					$post_cat = 'others';
				}

				/* get post date to insert. */
				$post_date = get_the_date('Y-m-d H:i:s', $id);

				/* calculate size */
				$post_size = array_sum($this->fn_wmh_calculate_size($id, $post_mime_type, $media_size_array));

				$media_ext = '';
				if (str_contains($post_mime_type, 'image')) {
					$guid_url_for_ext = wp_get_original_image_url($id);
				} else {
					$guid_url_for_ext = wp_get_attachment_url($id);
				}
				$media_type_ext = wp_check_filetype($guid_url_for_ext);
				if (isset($media_type_ext['ext'])) {
					if ($media_type_ext['ext'] != '') {
						$media_ext = $media_type_ext['ext'];
					}
				}

				$insert_array = array(
					'id' => '',
					'post_id' => $id,
					'attachment_cat' => $post_cat,
					'ext' => $media_ext,
					'post_date' => $post_date,
					'size' => $post_size,
					'date_created' => date('Y-m-d H:i:s'),
					'date_updated' => date('Y-m-d H:i:s')
				);

				if ($flg == 0) {
					$this->conn->insert($this->wmh_unused_media_post_id, $insert_array);
				} else if ($flg == 1) {
					$this->conn->insert($this->wmh_used_media_post_id, $insert_array);
				}
			}
		}

		if ($ajax_call == $total_ajax_call) {
			/* truncate temp table data is scan is complete */
			$this->conn->query(' TRUNCATE TABLE ' . $this->wmh_temp . ' ');
			$this->conn->query(' TRUNCATE TABLE ' . $this->wmh_save_scan_content . ' ');
			update_option('wmh_new_update_after_scan', '1.0.3', 'no');
			/* set status for last scan, it is intruppted or not */
			update_option('wmh_scan_complete', 'completed', 'no');
			update_option('wmh_scan_status_new', 1, 'no');
			$flg = 2;
			$message = __('Scan completed.', MEDIA_HYGIENE);
			$output = array(
				'flg' => $flg,
				'message' => $message
			);
		} else {
			$flg = 1;
			$output = array(
				'flg' => $flg,
				'total_ajax_call' => $total_ajax_call,
				'ajax_call' => $ajax_call,
				'progress_bar_width' => $progress_bar_width
			);
		}
		$end_time = date('Y-m-d h:i:s');
		update_option('wmh_end_time', $end_time, 'no');
		echo json_encode($output);
		wp_die();
	}

	/* get post type attachment data function */
	public function fn_wmh_get_post_type_attachment_data($offset = '', $per_post = '')
	{
		$attachments = [];
		$att_sql = " SELECT ID FROM $this->wp_posts WHERE post_type = 'attachment' AND post_status != 'trash' LIMIT $per_post OFFSET $offset ";
		$attr_result = $this->conn->get_results($att_sql, ARRAY_A);
		if (isset($attr_result) && !empty($attr_result)) {
			foreach ($attr_result as $id) {
				array_push($attachments, $id['ID']);
			}
		}
		return $attachments;
	}

	/* check post content */
	public function fn_wmh_check_post_content()
	{
		$check_post_content_sql =  'SELECT post_content FROM ' . $this->wp_posts . ' WHERE post_type = "post"';
		$post_content_data =  $this->conn->get_results($check_post_content_sql, ARRAY_A);
		$content = array();
		if (isset($post_content_data) && !empty($post_content_data)) {
			foreach ($post_content_data as $post_content) {
				if ($post_content['post_content'] != '') {
					$data = htmlentities($post_content['post_content']);
					array_push($content, $data);
				}
			}
		} else {
			$module = 'Scan';
			$error = 'post content data not set for scan';
			$wmh_general = new wmh_general();
			$wmh_general->fn_wmh_error_log($module, $error);
		}
		return $content;
	}

	/* check page content */
	public function fn_wmh_check_page_content()
	{

		$check_page_content_sql =  'SELECT ID, post_content FROM ' . $this->wp_posts . ' WHERE post_type = "page"';
		$page_content_data =  $this->conn->get_results($check_page_content_sql, ARRAY_A);
		$content = array();
		if (isset($page_content_data) && !empty($page_content_data)) {
			foreach ($page_content_data as $page_content) {
				if ($page_content['post_content'] != '') {
					$data = htmlentities($page_content['post_content']);
					array_push($content, $data);
				}
			}
		} else {
			$module = 'Scan';
			$error = 'page content data not set for scan';
			$wmh_general = new wmh_general();
			$wmh_general->fn_wmh_error_log($module, $error);
		}
		return $content;
	}

	public function fn_wmh_get_page_post_feature_images_ids()
	{
		$page_post_thumbnail_ids = [];
		$query = "SELECT pm.meta_value AS featured_image_id
          FROM {$this->wp_posts} AS p
          LEFT JOIN {$this->wp_postmeta} AS pm ON p.ID = pm.post_id
          WHERE p.post_type IN( 'page', 'post' )
          AND pm.meta_key = '_thumbnail_id'";
		$product_data = $this->conn->get_results($query, ARRAY_A);
		if (isset($product_data) && !empty($product_data)) {
			foreach ($product_data as $id) {
				array_push($page_post_thumbnail_ids, $id['featured_image_id']);
			}
		}

		return array_unique($page_post_thumbnail_ids);
	}

	public function fn_wmh_row_action_trash()
	{
		if (!current_user_can('manage_options')) {
			return false;
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "media_hygiene_nonce")) {
			die(__("Security check. Hacking not allowed", MEDIA_HYGIENE_PRO));
		}

		if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'row_action_trash') {
			$post_id = sanitize_text_field($_POST['post_id']);
			$media_size = sanitize_text_field($_POST['file_size']);
			$trashed_from_media_posts = wp_trash_post($post_id);
			if ($trashed_from_media_posts) {
				$this->conn->delete($this->wmh_unused_media_post_id, array('post_id' => $post_id));
				$update_statistics_array = array(
					'call' => 'single_trash',
					'count' => 1,
					'size' => $media_size
				);
				$this->fn_wmh_update_statistics_data_on_delete($update_statistics_array);
				$flg = 1;
				$message = esc_html(__('File trashed successfully.', MEDIA_HYGIENE));
				$output = array(
					'flg' => $flg,
					'message' => $message
				);
				echo json_encode($output);
			} else {
				$flg = 0;
				$message = esc_html(__('There is an error for trash media.', MEDIA_HYGIENE));
				$output = array(
					'flg' => $flg,
					'message' => $message
				);
				echo json_encode($output);
			}
		}
		wp_die();
	}

	public function fn_wmh_insert_into_deleted_media_list($post_id = '', $post_mime_type = '')
	{
		if (str_contains($post_mime_type, 'image')) {
			$guid = wp_get_original_image_url($post_id);
		} else {
			$guid = wp_get_attachment_url($post_id);
		}
		$insert_array = [
			'id' => '',
			'post_id' => $post_id,
			'url' => $guid,
			'date_created' => date('Y-m-d H:i:s'),
			'date_updated' => date('Y-m-d H:i:s')
		];
		$this->conn->insert($this->wmh_deleted_media, $insert_array);
	}

	public function fn_wmh_trash_page_media()
	{

		if (!current_user_can('manage_options')) {
			return false;
		}

		/* check nonce here. */
		$wp_nonce = sanitize_text_field($_POST['nonce']);
		if (!wp_verify_nonce($wp_nonce, 'trash_page_media_nonce')) {
			die(esc_html(__('Security check. Hacking not allowed', MEDIA_HYGIENE)));
		}

		/* default */
		$flg = 0;
		$message = esc_html(__('Something is wrong to trash page media', MEDIA_HYGIENE));

		$wmh_scan_option_data = get_option('wmh_scan_option_data', true);
		$media_per_page_input = 10;
		if (isset($wmh_scan_option_data['media_per_page_input']) && ($wmh_scan_option_data['media_per_page_input'] != '' || $wmh_scan_option_data['media_per_page_input'] != 0)) {
			$media_per_page_input = $wmh_scan_option_data['media_per_page_input'];
		}

		/* temp code */
		$per_post =  $media_per_page_input;
		$paged = sanitize_text_field($_POST['paged']);
		$offset = $per_post * ($paged - 1);
		$trashed_post_id_sql = ' SELECT post_id, size FROM ' . $this->wmh_unused_media_post_id . ' LIMIT ' . $per_post . ' OFFSET ' . $offset . '';
		$trashed_post_id_results = $this->conn->get_results($trashed_post_id_sql,  ARRAY_A);
		$trashed_display_size = array();
		if (isset($trashed_post_id_results) && !empty($trashed_post_id_results)) {
			foreach ($trashed_post_id_results as $id) {
				$post_id = $id['post_id'];
				array_push($trashed_display_size, $id['size']);
				$trashed_from_media_posts = wp_trash_post($post_id);
				if ($trashed_from_media_posts) {
					$this->conn->delete($this->wmh_unused_media_post_id, array('post_id' => $post_id));
				} else {
					$module = 'Scan trash page media';
					$error = 'Attachment not trashed for trash page media';
					$wmh_general = new wmh_general();
					$wmh_general->fn_wmh_error_log($module, $error);
				}
			}
			if (isset($trashed_display_size) && !empty($trashed_display_size)) {
				$trashed_display_size_count = count($trashed_post_id_results);
				/* update statistics data */
				$update_statistics_array = array(
					'call' => 'page_trash',
					'count' => (int) $trashed_display_size_count,
					'size' => array_sum($trashed_display_size)
				);
				$this->fn_wmh_update_statistics_data_on_delete($update_statistics_array);
				$trashed_display_size = size_format(array_sum($trashed_display_size));
				$flg = 1;
				$message = esc_html(__('Total images trashed: ' . $trashed_display_size_count . ', Free up space: ' . $trashed_display_size . '', MEDIA_HYGIENE));
			} else {
				$flg = 0;
				$message = esc_html(__('Something is wrong to calcualte size', MEDIA_HYGIENE));
			}
		} else {
			$flg = 0;
			$message = esc_html(__('There is no unused media to trash.', MEDIA_HYGIENE));
		}
		$output = array(
			'flg' => $flg,
			'message' => $message
		);
		echo json_encode($output);
		wp_die();
	}

	/* delet media from custom table called wmh_unused_media_post_id, when delete any media from media. */
	public function fn_wmh_delete_attachment_media($post_id)
	{
		/* delete from custom table that we created 'wmh_unused_media_post_id' */
		$deleted = $this->conn->delete($this->wmh_unused_media_post_id, array('post_id' => $post_id));

		/* delete whitelist table called wmh_whitelist_media_post_id. */
		$this->conn->delete($this->wmh_whitelist_media_post_id, array('post_id' => $post_id));

		$media_size_array = array();
		$count = 1;
		$post_mime_type = get_post_mime_type($post_id);
		$media_size_array = $this->fn_wmh_calculate_size($post_id, $post_mime_type, $media_size_array);
		$size = array_sum($media_size_array);

		if ($deleted) {
			$call = 'attachment_delete_unused';
		} else {
			$call = 'attachment_delete_used';
		}
		$update_statistics_array = array(
			'call' => $call,
			'count' => (int) $count,
			'size' => $size
		);
		$this->fn_wmh_update_statistics_data_on_delete($update_statistics_array);
	}

	/* whitelist media. */
	public function fn_wmh_whitelist_single_image_call()
	{

		if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'whitelist_single_image_call') {

			if (isset($_POST['post_id']) && $_POST['post_id'] != '') {

				$post_id = sanitize_text_field($_POST['post_id']);
				$whitelist_posts_result = array();
				$whitelist_posts_sql = 'SELECT * FROM ' . $this->wmh_unused_media_post_id . ' WHERE post_id = "' . $post_id . '"';
				$whitelist_posts_result = $this->conn->get_row($whitelist_posts_sql, ARRAY_A);
				$whitelist_posts_result['id'] = '';
				$whitelist_post_id_inserted = $this->conn->insert($this->wmh_whitelist_media_post_id, $whitelist_posts_result);
				if ($whitelist_post_id_inserted) {
					$this->conn->delete($this->wmh_unused_media_post_id, array('post_id' => $post_id));
					/* update statistics data */
					$media_count = 1;
					/* get size */
					$media_size = 0;
					if (isset($whitelist_posts_result['size'])) {
						$media_size = $whitelist_posts_result['size'];
					}
					/* update statistics data */
					$update_statistics_array = array(
						'call' => 'media_added_in_whitelist_single',
						'count' => $media_count,
						'size' => $media_size
					);
					$this->fn_wmh_update_statistics_data_on_delete($update_statistics_array);

					$flg = 1;
					$message = esc_html(__('Media added in whitelist.', MEDIA_HYGIENE));
					$output = array(
						'flg' => $flg,
						'message' => $message
					);
					echo json_encode($output);
				} else {

					$flg = 1;
					$message = esc_html(__('Media not added in whitelist.', MEDIA_HYGIENE));
					$output = array(
						'flg' => $flg,
						'message' => $message
					);
					echo json_encode($output);
				}
			} else {
				$module = 'Scan whitelist';
				$error = 'post_id does not set for whitelist';
				$wmh_general = new wmh_general();
				$wmh_general->fn_wmh_error_log($module, $error);
			}
		}
		wp_die();
	}

	/* blacklist media. */
	public function fn_wmh_blacklist_single_image_call()
	{

		if (isset($_POST['action']) && sanitize_text_field($_POST['action'] == 'blacklist_single_image_call')) {

			if (isset($_POST['post_id']) && $_POST['post_id'] != '') {

				$post_id = sanitize_text_field($_POST['post_id']);
				$whitelist_posts_result = array();
				$whitelist_posts_sql = 'SELECT * FROM ' . $this->wmh_whitelist_media_post_id . ' WHERE post_id = "' . $post_id . '"';
				$whitelist_posts_result = $this->conn->get_row($whitelist_posts_sql, ARRAY_A);
				$whitelist_posts_result['id'] = '';
				$whitelist_post_id_inserted = $this->conn->insert($this->wmh_unused_media_post_id, $whitelist_posts_result);
				if ($whitelist_post_id_inserted) {
					$whitelist_post_id_array = array(
						'post_id' => sanitize_text_field($_POST['post_id'])
					);
					$whitelist_post_id_deleted = $this->conn->delete($this->wmh_whitelist_media_post_id, $whitelist_post_id_array);
					if ($whitelist_post_id_deleted) {
						/* update statistics data */
						$media_count = 1;
						/* get size */
						$media_size = 0;
						if (isset($whitelist_posts_result['size'])) {
							$media_size = $whitelist_posts_result['size'];
						}
						/* update statistics data */
						$update_statistics_array = array(
							'call' => 'remove_media_from_whitelist_single',
							'count' => $media_count,
							'size' => $media_size
						);
						$this->fn_wmh_update_statistics_data_on_delete($update_statistics_array);
						$flg = 1;
						$message = esc_html(__('Media removed form whitelist.', MEDIA_HYGIENE));
						$output = array(
							'flg' => $flg,
							'message' => $message
						);
						echo json_encode($output);
					} else {

						$flg = 1;
						$message = esc_html(__('Media not removed form whitelist.', MEDIA_HYGIENE));
						$output = array(
							'flg' => $flg,
							'message' => $message
						);
						echo json_encode($output);
					}
				}
			} else {
				$module = 'Scan blacklist';
				$error = 'post_id does not set for blacklist';
				$wmh_general = new wmh_general();
				$wmh_general->fn_wmh_error_log($module, $error);
			}
		}
		wp_die();
	}

	public function fn_wmh_filter_data_ajax_call()
	{
		$list_element = '';

		if (sanitize_text_field($_POST['list_element']) == 'blacklist') {
			$list_element = '&type=blacklist';
		} else if (sanitize_text_field($_POST['list_element']) == 'whitelist') {
			$list_element = '&type=whitelist';
		} else if (sanitize_text_field($_POST['list_element']) == 'trash') {
			$list_element = '&type=trash';
		}


		if ($_POST['attachment_cat'] != '' && $_POST['date'] != '') {

			$flg = 1;
			$attachment_cat = sanitize_text_field($_POST['attachment_cat']);
			$attachment_date = sanitize_text_field($_POST['date']);
			$url = admin_url() . "admin.php?page=wmh-media-hygiene" . $list_element . "&attachment_cat=" . $attachment_cat . "&date=" . $attachment_date . "";
			$output = array(
				'flg' => $flg,
				'url' => $url
			);
		} else if ($_POST['attachment_cat'] != '' && $_POST['date'] == '') {

			$flg = 2;
			$attachment_cat = sanitize_text_field($_POST['attachment_cat']);
			$url = admin_url() . "admin.php?page=wmh-media-hygiene" . $list_element . "&attachment_cat=" . $attachment_cat . "";
			$output = array(
				'flg' => $flg,
				'url' => $url
			);
		} else if ($_POST['attachment_cat'] == '' && $_POST['date'] != '') {

			$flg = 3;
			$attachment_date = sanitize_text_field($_POST['date']);
			$url = admin_url() . "admin.php?page=wmh-media-hygiene" . $list_element . "&date=" . $attachment_date . "";
			$output = array(
				'flg' => $flg,
				'url' => $url
			);
		} else if ($_POST['attachment_cat'] == '' && $_POST['date'] == '') {

			$flg = 4;
			$url = admin_url() . "admin.php?page=wmh-media-hygiene" . $list_element . "";
			$output = array(
				'flg' => $flg,
				'url' => $url
			);
		}

		echo json_encode($output);
		wp_die();
	}

	public function fn_wmh_bulk_action_trash()
	{
		if (!current_user_can('manage_options')) {
			return false;
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "media_hygiene_nonce")) {
			die(__("Security check. Hacking not allowed", MEDIA_HYGIENE));
		}

		$trashed_display_size = array();

		/* deafult flg */
		$flg = 0;
		$message = __('Something is wrong to trash media', MEDIA_HYGIENE);

		if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'bulk_action_trash') {
			if (isset($_POST['bulk_action_val']) && sanitize_text_field($_POST['bulk_action_val']) == 'trash') {
				if (isset($_POST['chek_box_val']) && !empty($_POST['chek_box_val'])) {
					$chek_box_val = rest_sanitize_array($_POST['chek_box_val']);
					$size_array = [];
					$size_array = rest_sanitize_array($_POST['size']);
					foreach ($chek_box_val as $d_id) {
						$trashed_from_media_posts = wp_trash_post($d_id);
						if ($trashed_from_media_posts) {
							$this->conn->delete($this->wmh_unused_media_post_id, array('post_id' => $d_id));
						} else {
							$module = 'Scan trash bulk action';
							$error = 'Attachment not trashed for bulk action trash';
							$wmh_general = new wmh_general();
							$wmh_general->fn_wmh_error_log($module, $error);
						}
					}
					$trashed_display_size_count = count($chek_box_val);
					$update_statistics_array = array(
						'call' => 'bulk_trash',
						'count' => (int) $trashed_display_size_count,
						'size' => array_sum($size_array)
					);
					$this->fn_wmh_update_statistics_data_on_delete($update_statistics_array);
					$trashed_display_size = size_format(array_sum($size_array));
					$flg = 1;
					$message = esc_html(__('Total images trashed: ' . $trashed_display_size_count . ', Free up space: ' . $trashed_display_size . '', MEDIA_HYGIENE));
				}
			}
		}
		$bulk_trash_message_array = array(
			'flg' => $flg,
			'message' => $message
		);
		echo json_encode($bulk_trash_message_array);
		wp_die();
	}

	public function fn_wmh_update_statistics_data_on_delete($update_statistics_array = array())
	{
		$call = $update_statistics_array['call'];
		$count = (int) $update_statistics_array['count'];
		$size = $update_statistics_array['size'];

		/* make dashboard class */
		$wmh_dashboard = new wmh_dashboard();

		//$call == 'attachment_delete_unused';

		if ($call == 'single_trash' || $call == 'bulk_trash' || $call == 'page_trash') {

			/* get all option for statistics data */
			$wmh_media_count = (int) get_option('wmh_media_count');
			$wmh_total_media_size = (int) get_option('wmh_total_media_size');
			$wmh_total_unused_media_count = (int) get_option('wmh_total_unused_media_count');
			$wmh_unused_media_size = (int) get_option('wmh_unused_media_size');
			$update_wmh_media_count = $wmh_media_count - $count;
			$update_wmh_total_media_size = $wmh_total_media_size - $size;
			$update_wmh_total_unused_media_count = $wmh_total_unused_media_count - $count;
			$update_wmh_unused_media_size = $wmh_unused_media_size - $size;
			update_option('wmh_media_count', $update_wmh_media_count, 'no');
			update_option('wmh_total_media_size', $update_wmh_total_media_size, 'no');
			update_option('wmh_total_unused_media_count', $update_wmh_total_unused_media_count, 'no');
			update_option('wmh_unused_media_size', $update_wmh_unused_media_size, 'no');
			$media_type_info = $wmh_dashboard->fn_wmh_media_type_info();
			$media_breakdown = $wmh_dashboard->fn_wmh_get_media_breakdown();
			/* media type info */
			update_option('wmh_media_type_info', $media_type_info, 'no');
			/* media breakdown */
			update_option('wmh_media_breakdown', $media_breakdown, 'no');
		}

		if ($call == 'attachment_delete_used') {
			$wmh_use_media_count = (int) get_option('wmh_use_media_count');
			$wmh_use_media_size = (int) get_option('wmh_use_media_size');
			$update_wmh_use_media_count = $wmh_use_media_count - $count;
			$update_wmh_use_media_size = $wmh_use_media_size - $size;
			update_option('wmh_use_media_count', $update_wmh_use_media_count, 'no');
			update_option('wmh_use_media_size', $update_wmh_use_media_size, 'no');
		}

		if ($call == 'media_added_in_whitelist_single' || $call == 'media_added_in_whitelist_bulk') {
			/* minus for unused */
			$wmh_total_unused_media_count = (int) get_option('wmh_total_unused_media_count');
			$wmh_unused_media_size = (int) get_option('wmh_unused_media_size');
			$update_wmh_total_unused_media_count = $wmh_total_unused_media_count - $count;
			$update_wmh_unused_media_size = $wmh_unused_media_size - $size;
			update_option('wmh_total_unused_media_count', $update_wmh_total_unused_media_count, 'no');
			update_option('wmh_unused_media_size', $update_wmh_unused_media_size, 'no');
			/* added for used */
			$wmh_use_media_count = (int) get_option('wmh_use_media_count');
			$wmh_use_media_size = (int) get_option('wmh_use_media_size');
			$update_wmh_use_media_count = $wmh_use_media_count + $count;
			$update_wmh_use_media_size = $wmh_use_media_size + $size;
			update_option('wmh_use_media_count', $update_wmh_use_media_count, 'no');
			update_option('wmh_use_media_size', $update_wmh_use_media_size, 'no');
			$media_type_info = $wmh_dashboard->fn_wmh_media_type_info();
			$media_breakdown = $wmh_dashboard->fn_wmh_get_media_breakdown();
			/* media type info */
			update_option('wmh_media_type_info', $media_type_info, 'no');
			/* media breakdown */
			update_option('wmh_media_breakdown', $media_breakdown, 'no');
		}

		if ($call == 'remove_media_from_whitelist_single' || $call == 'remove_media_from_whitelist_bulk') {
			/* minus for unused */
			$wmh_total_unused_media_count = (int) get_option('wmh_total_unused_media_count');
			$wmh_unused_media_size = (int) get_option('wmh_unused_media_size');
			$update_wmh_total_unused_media_count = $wmh_total_unused_media_count + $count;
			$update_wmh_unused_media_size = $wmh_unused_media_size + $size;
			update_option('wmh_total_unused_media_count', $update_wmh_total_unused_media_count, 'no');
			update_option('wmh_unused_media_size', $update_wmh_unused_media_size, 'no');
			/* added for used */
			$wmh_use_media_count = (int) get_option('wmh_use_media_count');
			$wmh_use_media_size = (int) get_option('wmh_use_media_size');
			$update_wmh_use_media_count = $wmh_use_media_count - $count;
			$update_wmh_use_media_size = $wmh_use_media_size - $size;
			update_option('wmh_use_media_count', $update_wmh_use_media_count, 'no');
			update_option('wmh_use_media_size', $update_wmh_use_media_size, 'no');
			$media_type_info = $wmh_dashboard->fn_wmh_media_type_info();
			$media_breakdown = $wmh_dashboard->fn_wmh_get_media_breakdown();
			/* media type info */
			update_option('wmh_media_type_info', $media_type_info, 'no');
			/* media breakdown */
			update_option('wmh_media_breakdown', $media_breakdown, 'no');
		}
	}

	public function fn_wmh_update_statistics_data_on_restore($update_statistics_array = array())
	{


		$call = $update_statistics_array['call'];
		$count = (int) $update_statistics_array['count'];
		$size = $update_statistics_array['size'];

		/* make dashboard class */
		$wmh_dashboard = new wmh_dashboard();

		if ($call == 'bulk_restore' || $call == 'single_restore') {
			/* get all option for statistics data */
			$wmh_media_count = (int) get_option('wmh_media_count');
			$wmh_total_media_size = (int) get_option('wmh_total_media_size');
			$wmh_total_unused_media_count = (int) get_option('wmh_total_unused_media_count');
			$wmh_unused_media_size = (int) get_option('wmh_unused_media_size');
			$update_wmh_media_count = $wmh_media_count + $count;
			$update_wmh_total_media_size = $wmh_total_media_size + $size;
			$update_wmh_total_unused_media_count = $wmh_total_unused_media_count + $count;
			$update_wmh_unused_media_size = $wmh_unused_media_size + $size;
			update_option('wmh_media_count', $update_wmh_media_count, 'no');
			update_option('wmh_total_media_size', $update_wmh_total_media_size, 'no');
			update_option('wmh_total_unused_media_count', $update_wmh_total_unused_media_count, 'no');
			update_option('wmh_unused_media_size', $update_wmh_unused_media_size, 'no');
			$media_type_info = $wmh_dashboard->fn_wmh_media_type_info();
			$media_breakdown = $wmh_dashboard->fn_wmh_get_media_breakdown();
			/* media type info */
			update_option('wmh_media_type_info', $media_type_info, 'no');
			/* media breakdown */
			update_option('wmh_media_breakdown', $media_breakdown, 'no');
		}
	}

	public function fn_wmh_bulk_action_to_whitelist()
	{

		if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'bulk_action_to_whitelist') {
			if (isset($_POST['bulk_action_val']) && sanitize_text_field($_POST['bulk_action_val']) == 'whitelist') {
				$chek_box_val = rest_sanitize_array($_POST['chek_box_val']);
				$size = array();
				$media_size_array = array();
				foreach ($chek_box_val as $d_id) {
					$sql = 'SELECT * FROM ' . $this->wmh_unused_media_post_id . ' WHERE post_id="' . $d_id . '"';
					$data = $this->conn->get_row($sql, ARRAY_A);
					$data['id'] = '';
					if (isset($data) && !empty($data)) {
						$whitelist_post_id_inserted = $this->conn->insert($this->wmh_whitelist_media_post_id, $data);
						if ($whitelist_post_id_inserted) {
							$deleted = $this->conn->delete($this->wmh_unused_media_post_id, array('post_id' => $d_id));
							if (isset($data['size'])) {
								$size = $data['size'];
							}
							array_push($media_size_array, $size);
						}
					}
				}
				if ($deleted) {
					$media_count = count($chek_box_val);
					/* update statistics data */
					$update_statistics_array = array(
						'call' => 'media_added_in_whitelist_bulk',
						'count' => $media_count,
						'size' => array_sum($media_size_array)
					);
					$this->fn_wmh_update_statistics_data_on_delete($update_statistics_array);
					$flg = 1;
					$message = esc_html(__('Media added in whitelist.', MEDIA_HYGIENE));
					$output = array(
						'flg' => $flg,
						'message' => $message
					);
					echo json_encode($output);
					wp_die();
				}
			}
		}
	}

	public function fn_wmh_bulk_action_to_blacklist()
	{

		if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'bulk_action_to_blacklist') {
			if (isset($_POST['bulk_action_val']) && sanitize_text_field($_POST['bulk_action_val']) == 'blacklist') {
				$chek_box_val = rest_sanitize_array($_POST['chek_box_val']);
				$size = array();
				$media_size_array = array();
				foreach ($chek_box_val as $d_id) {
					$sql = 'SELECT * FROM ' . $this->wmh_whitelist_media_post_id . ' WHERE post_id="' . $d_id . '"';
					$data = $this->conn->get_row($sql, ARRAY_A);
					$data['id'] = '';
					if (isset($data) && !empty($data)) {
						$whitelist_post_id_inserted = $this->conn->insert($this->wmh_unused_media_post_id, $data);
						if ($whitelist_post_id_inserted) {
							$deleted = $this->conn->delete($this->wmh_whitelist_media_post_id, array('post_id' => $d_id));
							if (isset($data['size'])) {
								$size = $data['size'];
							}
							array_push($media_size_array, $size);
						}
					}
				}
				if ($deleted) {

					$media_count = count($chek_box_val);
					/* update statistics data */
					$update_statistics_array = array(
						'call' => 'remove_media_from_whitelist_bulk',
						'count' => $media_count,
						'size' => array_sum($media_size_array)
					);
					$this->fn_wmh_update_statistics_data_on_delete($update_statistics_array);

					$flg = 1;
					$message = esc_html(__('Media added in blacklist.', MEDIA_HYGIENE));
					$output = array(
						'flg' => $flg,
						'message' => $message
					);
					echo json_encode($output);
					wp_die();
				}
			}
		}
	}

	public function fn_wmh_calculate_size($attachment_id = '', $post_mime_type = '', $media_size_array = array())
	{
		/* declare array */
		$register_size_by_post = array();

		/* get original image path */
		$original_image_path = wp_get_original_image_path($attachment_id);
		$original_image_path = substr($original_image_path, 0, strrpos($original_image_path, "/"));

		if (str_contains($post_mime_type, 'image')) {
			/* get attachment size data by id */
			$attachment_size = wp_get_attachment_metadata($attachment_id);
			if (isset($attachment_size['sizes']) && !empty($attachment_size['sizes'])) {
				$register_size_by_post = array_keys($attachment_size['sizes']);
			}
			/* main file */
			if (isset($attachment_size['file']) && $attachment_size['file'] != '') {
				$main_file_path = $this->basedir . '/' . $attachment_size['file'];
				$main_file_path_size = wp_filesize($main_file_path);
				array_push($media_size_array, $main_file_path_size);
			}
			if (isset($register_size_by_post) && !empty($register_size_by_post)) {
				foreach ($register_size_by_post as $rsp) {
					/* check multiple size */
					if (isset($attachment_size['sizes'][$rsp]['file'])) {
						if ($attachment_size['sizes'][$rsp]['file'] != '') {
							$multi_file_name = $attachment_size['sizes'][$rsp]['file'];
							$make_new_file_name = $original_image_path . '/' . $multi_file_name;
							$make_new_file_name_size = wp_filesize($make_new_file_name);
							array_push($media_size_array, $make_new_file_name_size);
						}
					}
				}
			}
			/* check original image */
			if (isset($attachment_size['original_image']) && $attachment_size['original_image'] != '') {
				$original_image_path = wp_get_original_image_path($attachment_id);
				$original_image_size = wp_filesize($original_image_path);
				array_push($media_size_array, $original_image_size);
			}
			/* check file size for svg */
			if (str_contains($post_mime_type, 'svg')) {
				if (isset($attachment_size['filesize']) && $attachment_size['filesize'] != '') {
					$svg_file_size = $attachment_size['filesize'];
					array_push($media_size_array, $svg_file_size);
				}
			}
		} else {
			/* get media size. */
			$attachment_filesize = wp_filesize(get_attached_file($attachment_id));
			array_push($media_size_array, $attachment_filesize);
		}
		return $media_size_array;
	}

	public function fn_wmh_bulk_action_trash_to_restore()
	{
		if (!current_user_can('manage_options')) {
			return false;
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "media_hygiene_nonce")) {
			die(__("Security check. Hacking not allowed", MEDIA_HYGIENE));
		}

		$flg = 0;
		$message = __('Something is wrong to restore media', MEDIA_HYGIENE);

		$chek_box_val = [];
		if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'bulk_action_trash_to_restore') {
			if (isset($_POST['bulk_action_val']) && sanitize_text_field($_POST['bulk_action_val']) == 'restore') {
				$chek_box_val = rest_sanitize_array($_POST['chek_box_val']);
			}
		}

		if (!empty($chek_box_val)) {
			$size_array = [];
			$size_array = rest_sanitize_array($_POST['size']);
			foreach ($chek_box_val as $a_id) {
				$media_size_array = [];
				/* post mime type */
				$post_mime_type = sanitize_mime_type(get_post_mime_type($a_id));
				/* post cat */
				$post_cat = 'others';
				if (str_contains($post_mime_type, 'image')) {
					$post_cat = 'images';
				} else if (str_contains($post_mime_type, 'application')) {
					$post_cat = 'documents';
				} else if (str_contains($post_mime_type, 'video')) {
					$post_cat =  'video';
				} else if (str_contains($post_mime_type, 'audio')) {
					$post_cat = 'audio';
				} else {
					$post_cat = 'others';
				}
				/* post date */
				$post_date = get_the_date('Y-m-d H:i:s', $a_id);
				/* calculate size */
				$post_size = array_sum($this->fn_wmh_calculate_size($a_id, $post_mime_type, $media_size_array));
				/* media ext */
				$media_ext = '';
				if (str_contains($post_mime_type, 'image')) {
					$guid_url_for_ext = wp_get_original_image_url($a_id);
				} else {
					$guid_url_for_ext = wp_get_attachment_url($a_id);
				}
				$media_type_ext = wp_check_filetype($guid_url_for_ext);
				if (isset($media_type_ext['ext'])) {
					if ($media_type_ext['ext'] != '') {
						$media_ext = $media_type_ext['ext'];
					}
				}
				if (get_post_status($a_id) === 'trash') {
					if (wp_untrash_post($a_id)) {
						$insert_array = array(
							'id' => '',
							'post_id' => $a_id,
							'attachment_cat' => $post_cat,
							'ext' => $media_ext,
							'post_date' => $post_date,
							'size' => $post_size,
							'date_created' => date('Y-m-d H:i:s'),
							'date_updated' => date('Y-m-d H:i:s')
						);
						$restored = $this->conn->insert($this->wmh_unused_media_post_id, $insert_array);
					}
				}
			}
			if ($restored) {

				/* update statistics data */
				$update_statistics_array = array(
					'call' => 'bulk_restore',
					'count' => (int) count($chek_box_val),
					'size' => array_sum($size_array)
				);
				$this->fn_wmh_update_statistics_data_on_restore($update_statistics_array);

				$flg = 1;
				$message = __('Media Restored.', MEDIA_HYGIENE);
			} else {
				$flg = 0;
				$message = __('Something is wrong to restore media', MEDIA_HYGIENE);
			}
		} else {
			$flg = 0;
			$message = __('Please, select media by checkbox to restore');
		}
		$output = [
			'flg' => $flg,
			'message' => $message
		];
		echo json_encode($output);
		wp_die();
	}

	public function fn_wmh_restore_single_image_call()
	{

		if (!current_user_can('manage_options')) {
			return false;
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "media_hygiene_nonce")) {
			die(__("Security check. Hacking not allowed", MEDIA_HYGIENE));
		}

		$flg = 0;
		$message = __('Something is wrong to restore media', MEDIA_HYGIENE);

		$post_id = isset($_POST['post_id']) ? sanitize_text_field($_POST['post_id']) : 0;
		$file_size = isset($_POST['file_size']) ? sanitize_text_field($_POST['file_size']) : 0;

		$media_size_array = [];
		/* post mime type */
		$post_mime_type = sanitize_mime_type(get_post_mime_type($post_id));
		/* post cat */
		$post_cat = 'others';
		if (str_contains($post_mime_type, 'image')) {
			$post_cat = 'images';
		} else if (str_contains($post_mime_type, 'application')) {
			$post_cat = 'documents';
		} else if (str_contains($post_mime_type, 'video')) {
			$post_cat =  'video';
		} else if (str_contains($post_mime_type, 'audio')) {
			$post_cat = 'audio';
		} else {
			$post_cat = 'others';
		}
		/* post date */
		$post_date = get_the_date('Y-m-d H:i:s', $post_id);
		/* calculate size */
		$post_size = array_sum($this->fn_wmh_calculate_size($post_id, $post_mime_type, $media_size_array));
		/* media ext */
		$media_ext = '';
		if (str_contains($post_mime_type, 'image')) {
			$guid_url_for_ext = wp_get_original_image_url($post_id);
		} else {
			$guid_url_for_ext = wp_get_attachment_url($post_id);
		}
		$media_type_ext = wp_check_filetype($guid_url_for_ext);
		if (isset($media_type_ext['ext'])) {
			if ($media_type_ext['ext'] != '') {
				$media_ext = $media_type_ext['ext'];
			}
		}
		if (get_post_status($post_id) === 'trash') {
			if (wp_untrash_post($post_id)) {
				$insert_array = array(
					'id' => '',
					'post_id' => $post_id,
					'attachment_cat' => $post_cat,
					'ext' => $media_ext,
					'post_date' => $post_date,
					'size' => $post_size,
					'date_created' => date('Y-m-d H:i:s'),
					'date_updated' => date('Y-m-d H:i:s')
				);
				$restored = $this->conn->insert($this->wmh_unused_media_post_id, $insert_array);
				if ($restored) {
					/* update statistics data */
					$update_statistics_array = array(
						'call' => 'single_restore',
						'count' => 1,
						'size' => $file_size
					);
					$this->fn_wmh_update_statistics_data_on_restore($update_statistics_array);
					$flg = 1;
					$message = __('Media Restored.', MEDIA_HYGIENE);
				} else {
					$flg = 0;
					$message = __('Something is wrong to restore media', MEDIA_HYGIENE);
				}
			}
		}
		$output = [
			'flg' => $flg,
			'message' => $message
		];
		echo json_encode($output);
		wp_die();
	}

	public function fn_wmh_bulk_restore()
	{
		if (!current_user_can('manage_options')) {
			return false;
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "wmh_bulk_restore")) {
			die(__("Security check. Hacking not allowed", MEDIA_HYGIENE));
		}

		$ajax_call = (int) sanitize_text_field($_POST['ajax_call']);

		if ($ajax_call == 0) {
			$trash_media_count = wp_count_attachments()->trash;
			if ($trash_media_count) {
				update_option('wmh_bulk_restore_total_trash_media', $trash_media_count);
				$ajax_call++;
				echo json_encode(['flg' => 1, 'ajax_call' => $ajax_call, 'message' => __('Pending ...', MEDIA_HYGIENE), 'progress_bar' => 0]);
				wp_die();
			} else {
				echo json_encode([
					'flg' => 0,
					'message' => __('There are currently no media items in the trash.', MEDIA_HYGIENE),
					'progress_bar' => 0
				]);
				wp_die();
			}
		}

		$per_post = 25;
		$posts_count = (int) get_option('wmh_bulk_restore_total_trash_media');
		$total_ajax_call = (int) ceil($posts_count / $per_post);
		$progress_bar = min((($ajax_call / $total_ajax_call) * 100), 100);
		$progress_bar = number_format($progress_bar, 2);

		$post_ids = get_posts([
			'post_type'      => 'attachment',
			'post_status' => 'trash',
			'posts_per_page' => $per_post,
			'fields'         => 'ids'
		]);

		if (!empty($post_ids)) {
			foreach ($post_ids as $a_id) {
				$media_size_array = [];
				/* post mime type */
				$post_mime_type = sanitize_mime_type(get_post_mime_type($a_id));
				/* post cat */
				$post_cat = 'others';
				if (str_contains($post_mime_type, 'image')) {
					$post_cat = 'images';
				} else if (str_contains($post_mime_type, 'application')) {
					$post_cat = 'documents';
				} else if (str_contains($post_mime_type, 'video')) {
					$post_cat =  'video';
				} else if (str_contains($post_mime_type, 'audio')) {
					$post_cat = 'audio';
				} else {
					$post_cat = 'others';
				}
				/* post date */
				$post_date = get_the_date('Y-m-d H:i:s', $a_id);
				/* calculate size */
				$post_size = array_sum($this->fn_wmh_calculate_size($a_id, $post_mime_type, $media_size_array));
				/* media ext */
				$media_ext = '';
				if (str_contains($post_mime_type, 'image')) {
					$guid_url_for_ext = wp_get_original_image_url($a_id);
				} else {
					$guid_url_for_ext = wp_get_attachment_url($a_id);
				}
				$media_type_ext = wp_check_filetype($guid_url_for_ext);
				if (isset($media_type_ext['ext'])) {
					if ($media_type_ext['ext'] != '') {
						$media_ext = $media_type_ext['ext'];
					}
				}
				if (wp_untrash_post($a_id)) {
					$insert_array = array(
						'id' => '',
						'post_id' => $a_id,
						'attachment_cat' => $post_cat,
						'ext' => $media_ext,
						'post_date' => $post_date,
						'size' => $post_size,
						'date_created' => date('Y-m-d H:i:s'),
						'date_updated' => date('Y-m-d H:i:s')
					);
					$this->conn->insert($this->wmh_unused_media_post_id, $insert_array);
				}
			}
		}

		if ($ajax_call == $total_ajax_call) {
			delete_option('wmh_bulk_restore_total_trash_media');
			$flg = 2;
			$message = __('Media restored successfully. Please note that it may take some time to update the dashboard statistics after you click "OK".', MEDIA_HYGIENE);
		} else {
			$ajax_call++;
			$flg = 1;
			$message = __('Pending ...', MEDIA_HYGIENE);
		}

		$output = [
			'flg' => $flg,
			'ajax_call' => $ajax_call,
			'message' => $message,
			'total_ajax_call' => $total_ajax_call,
			'progress_bar' => $progress_bar
		];
		echo json_encode($output);
		wp_die();
	}

	public function fn_wmh_delete_permanently_single_image_call()
	{
		if (!current_user_can('manage_options')) {
			return false;
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "media_hygiene_nonce")) {
			die(__("Security check. Hacking not allowed", MEDIA_HYGIENE));
		}

		$flg = 0;
		$message = __('Something is wrong to delete media', MEDIA_HYGIENE);

		/* post id */
		$post_id = isset($_POST['post_id']) ? sanitize_text_field($_POST['post_id']) : 0;
		/* media size */
		//$file_size = isset($_POST['file_size']) ? sanitize_text_field($_POST['file_size']) : 0;
		/* post mime type */
		$post_mime_type = get_post_mime_type($post_id);
		/* insert data to trash media */
		$this->fn_wmh_insert_into_deleted_media_list($post_id, $post_mime_type);
		/* delet from media */
		$deleted_from_media_posts = wp_delete_attachment($post_id, true);
		if ($deleted_from_media_posts) {
			$this->conn->delete($this->wmh_unused_media_post_id, array('post_id' => $post_id));
			/* update statistics data */
			//$delete_image_count = 1;
			/* update statistics data */
			// $update_statistics_array = array(
			// 	'call' => 'single_delete',
			// 	'count' => $delete_image_count,
			// 	'size' => $file_size
			// );
			//$this->fn_wmh_update_statistics_data_on_delete($update_statistics_array);
			$flg = 1;
			$message = esc_html(__('File deleted successfully.', MEDIA_HYGIENE));
			$output = array(
				'flg' => $flg,
				'message' => $message
			);
			echo json_encode($output);
		} else {

			$flg = 0;
			$message = esc_html(__('There is an error for delete media.', MEDIA_HYGIENE));
			$output = array(
				'flg' => $flg,
				'message' => $message
			);
			echo json_encode($output);
		}
		wp_die();
	}

	public function fn_wmh_bulk_action_delete()
	{
		if (!current_user_can('manage_options')) {
			return false;
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "media_hygiene_nonce")) {
			die(__("Security check. Hacking not allowed", MEDIA_HYGIENE));
		}

		$deleted_display_size = array();

		/* deafult flg */
		$flg = 0;
		$message = __('Something is wrong to trash media', MEDIA_HYGIENE);

		if (isset($_POST['bulk_action_val']) && sanitize_text_field($_POST['bulk_action_val']) == 'delete') {
			if (isset($_POST['chek_box_val']) && !empty($_POST['chek_box_val'])) {
				/* check box value */
				$chek_box_val = rest_sanitize_array($_POST['chek_box_val']);
				/* size */
				$size_array = [];
				$size_array = rest_sanitize_array($_POST['size']);
				foreach ($chek_box_val as $d_id) {
					/* attachment id */
					$attachment_id = $d_id;
					/* post mime type */
					$post_mime_type = sanitize_mime_type(get_post_mime_type($attachment_id));
					/* insert data to deleted media */
					$this->fn_wmh_insert_into_deleted_media_list($attachment_id, $post_mime_type);
					/* delete from media. */
					$deleted_from_media_posts = wp_delete_attachment($d_id, true);
					if ($deleted_from_media_posts) {
						/* delete from custom table that we created 'wmh_unused_media_post_id'. */
						$this->conn->delete($this->wmh_unused_media_post_id, array('post_id' => $d_id));
					} else {
						$module = 'Scan delete bulk action';
						$error = 'Attachment not deleted for bulk action delete';
						$wmh_general = new wmh_general();
						$wmh_general->fn_wmh_error_log($module, $error);
					}
				}
				/* count media */
				$deleted_display_size_count = count($chek_box_val);
				/* update statistics data */
				// $update_statistics_array = array(
				// 	'call' => 'bulk_delete',
				// 	'count' => (int) $deleted_display_size_count,
				// 	'size' => array_sum($size_array)
				// );
				// $this->fn_wmh_update_statistics_data_on_delete($update_statistics_array);
				$deleted_display_size = size_format(array_sum($size_array));
				$flg = 1;
				$message = esc_html(__('Total images trashed: ' . $deleted_display_size_count . ', Free up space: ' . $deleted_display_size . '', MEDIA_HYGIENE));
			}
		}

		$bulk_delete_message_array = array(
			'flg' => $flg,
			'message' => $message
		);
		echo json_encode($bulk_delete_message_array);
		wp_die();
	}

	public function fn_wmh_delete_permanently()
	{

		if (!current_user_can('manage_options')) {
			return false;
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "wmh_delete_permanently")) {
			die(__("Security check. Hacking not allowed", MEDIA_HYGIENE));
		}

		$ajax_call = (int) sanitize_text_field($_POST['ajax_call']);

		if ($ajax_call == 0) {
			$trash_media_count = wp_count_attachments()->trash;
			if ($trash_media_count) {
				update_option('wmh_bulk_delete_permanently_total_trash_media', $trash_media_count);
				$ajax_call++;
				echo json_encode(['flg' => 1, 'ajax_call' => $ajax_call, 'message' => __('Pending ...', MEDIA_HYGIENE), 'progress_bar' => 0]);
				wp_die();
			} else {
				echo json_encode([
					'flg' => 0,
					'message' => __('There are currently no media items in the trash.', MEDIA_HYGIENE),
					'progress_bar' => 0
				]);
				wp_die();
			}
		}

		$per_post = 25;
		$posts_count = (int) get_option('wmh_bulk_delete_permanently_total_trash_media');
		$total_ajax_call = (int) ceil($posts_count / $per_post);
		$progress_bar = min((($ajax_call / $total_ajax_call) * 100), 100);
		$progress_bar = number_format($progress_bar, 2);

		$post_ids = get_posts([
			'post_type'      => 'attachment',
			'post_status' => 'trash',
			'posts_per_page' => $per_post,
			'fields'         => 'ids'
		]);

		if (!empty($post_ids)) {
			foreach ($post_ids as $a_id) {
				$deleted_from_media_posts = wp_delete_attachment($a_id, true);
				if ($deleted_from_media_posts) {
					$this->conn->delete($this->wmh_unused_media_post_id, array('post_id' => $a_id));
				}
			}
		}

		if ($ajax_call == $total_ajax_call) {
			delete_option('wmh_bulk_delete_permanently_total_trash_media');
			$flg = 2;
			$message = __('Media deleted successfully. Please note that it may take some time to update the dashboard statistics after you click "OK".', MEDIA_HYGIENE);
		} else {
			$ajax_call++;
			$flg = 1;
			$message = __('Pending ...', MEDIA_HYGIENE);
		}

		$output = [
			'flg' => $flg,
			'ajax_call' => $ajax_call,
			'message' => $message,
			'total_ajax_call' => $total_ajax_call,
			'progress_bar' => $progress_bar
		];
		echo json_encode($output);
		wp_die();
	}

	public function fn_wmh_fetch_data_from_elementor()
	{
		$all_plugins = get_plugins();
		$ajax_call = (int) sanitize_text_field($_POST['ajax_call']);
		$progress_bar = sanitize_text_field($_POST['progress_bar']);
		if ($ajax_call == 0) {
			$elementor_count = (int) $this->conn->get_var(
				$this->conn->prepare(
					"SELECT COUNT(DISTINCT pm.post_id) 
					FROM {$this->conn->postmeta} pm 
					JOIN {$this->conn->posts} p ON p.ID = pm.post_id 
					WHERE pm.meta_key = %s 
					AND p.post_type != %s",
					'_elementor_data',
					'revision'
				)
			);
			if ($elementor_count > 0) {
				update_option('wmh_total_elementor_call', $elementor_count);
				$ajax_call++;
				$flg = 1;
			} else {
				$flg = 0;
			}
			echo json_encode(['flg' => $flg, 'ajax_call' => $ajax_call, 'progress_bar_width' => $progress_bar]);
			wp_die();
		}
		$per_post = 50;
		$offset = ($ajax_call - 1) * $per_post;
		$total_elementor_count = (int) get_option('wmh_total_elementor_call');
		$total_ajax_call = ceil(($total_elementor_count / $per_post));
		$percentage = (3.57 / $total_ajax_call);
		$progress_bar_width = number_format(($progress_bar + $percentage), 2);
		$elementor_result = array();
		if ((array_key_exists('elementor/elementor.php', $all_plugins)) || (array_key_exists('elementor-pro/elementor-pro.php', $all_plugins))) {
			$wmh_elementor = new wmh_elementor();
			$elementor_result = $wmh_elementor->fn_wmh_get_elementor_data($per_post, $offset);
		}
		if (!empty($elementor_result)) {
			$mh_key = 'wmh_elementor_data';
			$this->fn_wmh_insert_save_content($elementor_result, $mh_key);
		}
		if ($ajax_call == $total_ajax_call) {
			delete_option('wmh_total_elementor_call');
			$flg = 0;
		} else {
			$flg = 1;
		}
		$output = array(
			'flg' => $flg,
			'ajax_call' => $ajax_call + 1,
			'progress_bar_width' => $progress_bar_width
		);
		echo json_encode($output);
		wp_die();
	}
}

$wmh_scan = new wmh_scan();
