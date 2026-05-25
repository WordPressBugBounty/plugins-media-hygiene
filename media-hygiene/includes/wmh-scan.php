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
	public $wmh_scan_known_used;

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
		$this->wmh_scan_known_used = $this->conn->prefix . MH_PREFIX . 'scan_known_used';

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

	/* Write to debug log when WP_DEBUG and WP_DEBUG_LOG are both true. */
	private function fn_wmh_log( $context, $message )
	{
		if ( defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
			error_log( '[Media Hygiene][' . $context . '] ' . $message );
		}
	}

	public function fn_wmh_scan_unused_images()
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(null, 403);
		}

		/* check nonce here. */
		$wp_nonce = sanitize_text_field($_POST['nonce']);
		if (!wp_verify_nonce($wp_nonce, 'scan_unused_images_nonce')) {
			wp_die(esc_html__('Security check. Hacking not allowed', MEDIA_HYGIENE), '', array('response' => 403));
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
			/* clear Step 1 rendered-content basenames from any previous scan */
			$this->conn->query(
				$this->conn->prepare(
					'DELETE FROM ' . $this->wmh_save_scan_content . ' WHERE wmh_key LIKE %s',
					$this->conn->esc_like('wmh_step1_basenames_') . '%'
				)
			);

			$flg = 1;
			$output = array(
				'flg' => $flg,
				'ajax_call' => $ajax_call,
			);
			echo json_encode($output);
			wp_die();
		}

		if ($ajax_call == 2) {
			/* build post ID list — no HTTP requests needed */
			$wmh_scan_option_data_step1 = get_option('wmh_scan_option_data', true);
			$excludes_post_types_step1 = isset($wmh_scan_option_data_step1['excludes_post_types']) ? (array) $wmh_scan_option_data_step1['excludes_post_types'] : [];

			$post_types_step1 = array_values(array_diff(
				array_merge(['page', 'post']),
				$excludes_post_types_step1
			));

			$post_ids_step1 = get_posts([
				'post_type'      => $post_types_step1,
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'post_status'    => ['publish', 'draft'],
			]);

			update_option('wmh_url_list', $post_ids_step1, 'no');
			update_option('wmh_url_list_count', count($post_ids_step1), 'no');

			if (empty($post_ids_step1)) {
				$flg = 2;
				echo json_encode(['flg' => $flg]);
				wp_die();
			}

			$flg = 1;
			echo json_encode(['flg' => $flg, 'ajax_call' => $ajax_call]);
			wp_die();
		}

		/* in-process content rendering — 50 posts per AJAX call, no HTTP requests */
		$post_ids_list   = (array) get_option('wmh_url_list');
		$total_ids_list  = (int) get_option('wmh_url_list_count');
		$per_batch       = 50;
		$batch_offset    = ($ajax_call - 3) * $per_batch;
		$batch_ids       = array_slice($post_ids_list, $batch_offset, $per_batch);
		$total_ajax_call = (int) ceil(max($total_ids_list, 1) / $per_batch);

		$percentage          = (float) (100 / $total_ajax_call);
		$progress_bar_width  = number_format(($progress_bar + $percentage), 2);

		$step1_basenames = [];
		foreach ($batch_ids as $batch_post_id) {
			$batch_post = get_post($batch_post_id);
			if (!$batch_post) continue;
			try {
				/* apply_filters renders shortcodes, Gutenberg blocks, page builder output */
				$rendered = apply_filters('the_content', $batch_post->post_content);
			} catch (\Throwable $e) {
				$this->fn_wmh_log('step1_render', 'Skipped post ' . $batch_post_id . ': ' . $e->getMessage());
				continue;
			}
			/* src/href upload references */
			preg_match_all('/(src|href)=["\']([^"\']*\/wp-content\/uploads\/[^"\']+)["\']/', $rendered, $sm);
			if (!empty($sm[2])) {
				foreach ($sm[2] as $found_url) {
					$b = wp_basename($found_url);
					if ($b) $step1_basenames[] = $b;
				}
			}
			/* inline background-image CSS */
			preg_match_all('/url\(["\']?([^"\'()]*\/wp-content\/uploads\/[^"\'()]+)["\']?\)/', $rendered, $bm);
			if (!empty($bm[1])) {
				foreach ($bm[1] as $found_url) {
					$b = wp_basename($found_url);
					if ($b) $step1_basenames[] = $b;
				}
			}
		}
		$step1_basenames = array_values(array_filter(array_unique($step1_basenames)));
		if (!empty($step1_basenames)) {
			$this->fn_wmh_insert_save_content($step1_basenames, 'wmh_step1_basenames_' . $ajax_call);
		}

		if ($ajax_call == ($total_ajax_call + 2)) {
			try {
			/* final batch — also scan theme mods and widgets */
			$theme_widget_basenames = [];

			$theme_mods_data = get_theme_mods();
			if (!empty($theme_mods_data)) {
				array_walk_recursive($theme_mods_data, function ($v) use (&$theme_widget_basenames) {
					if (is_string($v) && str_contains($v, '/wp-content/uploads/')) {
						$b = wp_basename($v);
						if ($b) $theme_widget_basenames[] = $b;
					}
				});
			}

			$widget_opt_rows = $this->conn->get_results(
				"SELECT option_value FROM {$this->conn->options} WHERE option_name LIKE 'widget_%'",
				ARRAY_A
			);
			foreach ($widget_opt_rows as $widget_row) {
				$widget_json = json_encode(maybe_unserialize($widget_row['option_value']));
				preg_match_all('/\/wp-content\/uploads\/[^"\')\s,>]+/', $widget_json, $wm);
				foreach ($wm[0] as $found_url) {
					$b = wp_basename(rtrim($found_url, '\\/.,;'));
					if ($b) $theme_widget_basenames[] = $b;
				}
			}

			$css_post_id_step1 = (int) get_option('custom_css_post_id');
			if ($css_post_id_step1 > 0) {
				$css_post_step1 = get_post($css_post_id_step1);
				if ($css_post_step1 && $css_post_step1->post_content) {
					preg_match_all('/url\(["\']?([^"\'()]*\/wp-content\/uploads\/[^"\'()]+)["\']?\)/', $css_post_step1->post_content, $cm);
					foreach ($cm[1] as $found_url) {
						$b = wp_basename($found_url);
						if ($b) $theme_widget_basenames[] = $b;
					}
				}
			}

			$theme_widget_basenames = array_values(array_filter(array_unique($theme_widget_basenames)));
			if (!empty($theme_widget_basenames)) {
				$this->fn_wmh_insert_save_content($theme_widget_basenames, 'wmh_step1_basenames_theme');
			}

			delete_option('wmh_url_list');
			delete_option('wmh_url_list_count');
			delete_option('wmh_page_url_content');

			$flg = 2;
			$output = ['flg' => $flg, 'message' => __('Scan completed.', MEDIA_HYGIENE)];
			} catch (\Throwable $e) {
				echo json_encode([
					'flg'     => -1,
					'message' => 'Content scan error (theme/widget pass): ' . $e->getMessage(),
				]);
				wp_die();
			}
		} else {
			$flg = 1;
			$output = [
				'flg'                => $flg,
				'total_ajax_call'    => $total_ajax_call,
				'ajax_call'          => $ajax_call,
				'progress_bar_width' => $progress_bar_width,
			];
		}
		echo json_encode($output);
		wp_die();
	}

	public function fn_wmh_fetch_data_from_database()
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(null, 403);
		}

		/* check nonce here. */
		$wp_nonce = sanitize_text_field($_POST['nonce']);
		if (!wp_verify_nonce($wp_nonce, 'media_hygiene_nonce')) {
			wp_die(esc_html__('Security check. Hacking not allowed', MEDIA_HYGIENE), '', array('response' => 403));
		}

		/* default response */
		$flg = 0;

		/* ajax call */
		$ajax_call = sanitize_text_field($_POST['ajax_call']);

		/* progress bar */
		$progress_bar = sanitize_text_field($_POST['progress_bar']);

		if ($ajax_call == 1) {
			/* copy unused media table data in temp table */
			$this->conn->query(' TRUNCATE TABLE ' . $this->wmh_temp . ' ');
			$this->conn->query('INSERT INTO ' . $this->wmh_temp . ' SELECT * FROM ' . $this->wmh_unused_media_post_id . ' ');
			$this->conn->query(' TRUNCATE TABLE ' . $this->wmh_unused_media_post_id . ' ');
			$this->conn->query(' TRUNCATE TABLE ' . $this->wmh_used_media_post_id . ' ');
			/* preserve Step 1 rendered-content basenames — needed by call 12 to build the known-used index */
			$this->conn->query(
				$this->conn->prepare(
					'DELETE FROM ' . $this->wmh_save_scan_content . ' WHERE wmh_key NOT LIKE %s',
					$this->conn->esc_like('wmh_step1_basenames_') . '%'
				)
			);
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
			$all_plugins = get_plugins();
			if ((array_key_exists('elementor/elementor.php', $all_plugins)) || (array_key_exists('elementor-pro/elementor-pro.php', $all_plugins))) {
				$flg = 3;
			} else {
				$flg = 1;
				$progress_bar = 40;
			}
		}

		/*  save Divi theme data */
		if ($ajax_call == 6) {
			$all_themes = wp_get_themes();
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
			$all_themes = wp_get_themes();
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
			$all_plugins = get_plugins();
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
			$all_themes = wp_get_themes();
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
			$all_themes = wp_get_themes();
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

			/* advance to call 12 which builds the known-used index */
			$flg = 1;
			$progress_bar = 95;
		}

		if ($ajax_call == 12) {
			try {
				@set_time_limit(300);

				/* ensure table exists for sites upgraded without re-activating plugin */
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				$wmh_scan_known_used_sql = "CREATE TABLE IF NOT EXISTS " . $this->wmh_scan_known_used . "(
					`id` int NOT NULL AUTO_INCREMENT,
					`post_id` int NULL,
					`basename` varchar(255) NULL,
					PRIMARY KEY (`id`),
					KEY `idx_post_id` (`post_id`),
					KEY `idx_basename` (`basename`(191)));";
				dbDelta($wmh_scan_known_used_sql);

				$this->conn->query('TRUNCATE TABLE ' . $this->wmh_scan_known_used);

				/* ── Helper: batch-insert post IDs ── */
				$insert_post_ids = function (array $ids) {
					$ids = array_values(array_filter(array_map('intval', $ids)));
					foreach (array_chunk($ids, 500) as $chunk) {
						if (empty($chunk)) continue;
						$placeholders = implode(',', array_fill(0, count($chunk), '(%d)'));
						$this->conn->query(
							$this->conn->prepare(
								"INSERT IGNORE INTO {$this->wmh_scan_known_used} (post_id) VALUES $placeholders",
								$chunk
							)
						);
					}
				};

				/* ── Helper: batch-insert basenames ── */
				$insert_basenames = function (array $items) {
					$bns = [];
					foreach ($items as $item) {
						if (!is_string($item) || $item === '') continue;
						$b = wp_basename($item);
						if ($b && str_contains($b, '.')) $bns[] = $b;
					}
					$bns = array_values(array_unique($bns));
					foreach (array_chunk($bns, 500) as $chunk) {
						if (empty($chunk)) continue;
						$placeholders = implode(',', array_fill(0, count($chunk), '(%s)'));
						$this->conn->query(
							$this->conn->prepare(
								"INSERT IGNORE INTO {$this->wmh_scan_known_used} (basename) VALUES $placeholders",
								$chunk
							)
						);
					}
				};

				/* ── Helper: extract basenames from HTML/JSON string via regex ── */
				$extract_from_content = function ($content) use ($insert_basenames) {
					if (!is_string($content) || $content === '') return;
					$found = [];
					preg_match_all('/\/wp-content\/uploads\/[^"\')\s,>]+/', $content, $m);
					foreach ($m[0] as $u) {
						$b = wp_basename(rtrim($u, '\\/.,;'));
						if ($b && str_contains($b, '.')) $found[] = $b;
					}
					if (!empty($found)) $insert_basenames($found);
				};

				/* ── ID-based sources ── */
				$id_keys_12 = [
					'wmh_page_post_feature_image_ids_data',
					'wmh_whitelist_media_post_ids',
				];
				foreach ($id_keys_12 as $id_key_12) {
					$id_data_12 = $this->fn_wmh_get_save_content_data($id_key_12);
					if (!empty($id_data_12) && is_array($id_data_12)) {
						$insert_post_ids($id_data_12);
					}
				}

				/* site_logo and site_icon */
				foreach (['site_logo', 'site_icon'] as $site_opt_12) {
					$site_opt_id_12 = (int) get_option($site_opt_12);
					if ($site_opt_id_12 > 0) {
						$this->conn->query(
							$this->conn->prepare(
								"INSERT IGNORE INTO {$this->wmh_scan_known_used} (post_id) VALUES (%d)",
								$site_opt_id_12
							)
						);
					}
				}

				/* OceanWP logo IDs */
				$ocean_logo_12 = $this->fn_wmh_get_save_content_data('wmh_ocean_logo_data');
				if (!empty($ocean_logo_12)) {
					foreach (['ocean_custom_logo', 'ocean_custom_retina_logo'] as $logo_key_12) {
						if (!empty($ocean_logo_12[$logo_key_12]) && is_array($ocean_logo_12[$logo_key_12])) {
							$insert_post_ids($ocean_logo_12[$logo_key_12]);
						}
					}
				}

				/* OceanWP custom_logo ID */
				$theme_mode_12 = $this->fn_wmh_get_save_content_data('wmh_theme_mode_data');
				if (!empty($theme_mode_12['custom_logo'])) {
					$insert_post_ids([(int) $theme_mode_12['custom_logo']]);
				}

				/* Divi gallery IDs */
				$divi_12 = $this->fn_wmh_get_save_content_data('wmh_divi_post_content_data');
				if (!empty($divi_12['gallery_ids']) && is_array($divi_12['gallery_ids'])) {
					foreach ($divi_12['gallery_ids'] as $gallery_str_12) {
						$gids = array_filter(array_map('intval', explode(',', (string) $gallery_str_12)));
						if (!empty($gids)) $insert_post_ids(array_values($gids));
					}
				}

				/* ── Step 1 rendered-content basenames ── */
				$step1_all_12 = $this->fn_wmh_get_save_content_data_all('wmh_step1_basenames_');
				if (!empty($step1_all_12)) $insert_basenames($step1_all_12);

				/* ── Raw post/page content basenames (already extracted as basenames) ── */
				foreach (['wmh_post_content_data', 'wmh_page_content_data'] as $content_key_12) {
					$raw_12 = $this->fn_wmh_get_save_content_data($content_key_12);
					if (!empty($raw_12) && is_array($raw_12)) $insert_basenames($raw_12);
				}

				/* ── Elementor (multiple rows) ── */
				$el_sql_12 = $this->conn->prepare(
					'SELECT wmh_value FROM ' . $this->wmh_save_scan_content . ' WHERE wmh_key = %s',
					'wmh_elementor_data'
				);
				$el_rows_12 = $this->conn->get_results($el_sql_12, ARRAY_A);
				if (!empty($el_rows_12)) {
					foreach ($el_rows_12 as $el_row_12) {
						$el_decoded_12 = json_decode($el_row_12['wmh_value'], true);
						if (is_array($el_decoded_12)) {
							foreach ($el_decoded_12 as $el_content_12) {
								$extract_from_content(is_string($el_content_12) ? $el_content_12 : json_encode($el_content_12));
							}
						}
					}
				}

				/* ── Other content sources (HTML/JSON — extract via regex) ── */
				$other_keys_12 = [
					'wmh_divi_post_content_data',
					'wmh_bricks_post_content_data',
					'wmh_bricks_temp_header_data',
					'wmh_bricks_temp_footer_data',
					'wmh_bricks_temp_page_data',
					'wmh_vc_post_content_data',
					'wmh_vc_tmp_data_data',
					'wmh_enfold_layerslider_data',
				];
				foreach ($other_keys_12 as $ok_12) {
					$ok_data_12 = $this->fn_wmh_get_save_content_data($ok_12);
					if (empty($ok_data_12)) continue;
					if (is_string($ok_data_12)) {
						$extract_from_content($ok_data_12);
					} elseif (is_array($ok_data_12)) {
						foreach ($ok_data_12 as $ok_item_12) {
							$extract_from_content(is_string($ok_item_12) ? $ok_item_12 : json_encode($ok_item_12));
						}
					}
				}

				/* Divi content strings */
				if (!empty($divi_12['content']) && is_array($divi_12['content'])) {
					foreach ($divi_12['content'] as $divi_content_12) {
						$extract_from_content($divi_content_12);
					}
				}

				/* OceanWP background image */
				if (!empty($theme_mode_12['background_image'])) {
					$extract_from_content($theme_mode_12['background_image']);
				}

				/* Legacy wmh_page_url_content option (backwards compat) */
				$legacy_12 = get_option('wmh_page_url_content', []);
				if (!empty($legacy_12) && is_array($legacy_12)) {
					foreach ($legacy_12 as $legacy_url_12) {
						$extract_from_content(is_string($legacy_url_12) ? $legacy_url_12 : '');
					}
					delete_option('wmh_page_url_content');
				}

				$flg = 2;
				$progress_bar = 100;
			} catch (\Throwable $e) {
				echo json_encode([
					'flg'                => -1,
					'ajax_call'          => $ajax_call,
					'progress_bar_width' => $progress_bar,
					'error'              => $e->getMessage(),
					'file'               => basename($e->getFile()),
					'line'               => $e->getLine(),
				]);
				wp_die();
			}
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
		$sql = $this->conn->prepare('SELECT wmh_value FROM ' . $this->wmh_save_scan_content . ' WHERE wmh_key = %s', $mh_key);
		$data = $this->conn->get_row($sql, ARRAY_A);
		if (isset($data['wmh_value'])) {
			$content = json_decode($data['wmh_value'], true);
		}
		return $content;
	}

	/* Fetch and merge all rows whose wmh_key starts with $key_prefix. */
	public function fn_wmh_get_save_content_data_all($key_prefix = '')
	{
		$results = [];
		$sql = $this->conn->prepare(
			'SELECT wmh_value FROM ' . $this->wmh_save_scan_content . ' WHERE wmh_key LIKE %s',
			$this->conn->esc_like($key_prefix) . '%'
		);
		$rows = $this->conn->get_results($sql, ARRAY_A);
		if (!empty($rows)) {
			foreach ($rows as $row) {
				$decoded = json_decode($row['wmh_value'], true);
				if (is_array($decoded)) {
					$results = array_merge($results, $decoded);
				} elseif ($decoded !== null) {
					$results[] = $decoded;
				}
			}
		}
		return $results;
	}

	public function fn_wmh_get_url_list_for_scan()
	{
		$url_array = [];

		/* default post type */
		$default_post_type = array(
			'page',
			'post'
		);

		global $wpdb;
		$post_types_in = implode(',', array_fill(0, count($default_post_type), '%s'));
		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type IN ($post_types_in) AND post_status IN ('publish','draft') ORDER BY ID ASC",
				...$default_post_type
			)
		);

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

		if (!current_user_can('manage_options')) {
			wp_send_json_error(null, 403);
		}

		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'media_hygiene_nonce')) {
			wp_die(esc_html(__('Security check failed. Hacking attempt detected.', MEDIA_HYGIENE)));
		}

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
		$wmh_scan_option_data = get_option('wmh_scan_option_data', []);

		/* exclude extension string */
		$exclude_exs = isset($wmh_scan_option_data['ex_file_ex']) ? $wmh_scan_option_data['ex_file_ex'] : '';

		/* total attachments count */
		$total_attachments = (int) get_option('wmh_all_attachment_ids');

		/* get post_type attachment data by offset */
		$per_post = isset($wmh_scan_option_data['number_of_image_scan']) ? (int) $wmh_scan_option_data['number_of_image_scan'] : 30;
		$offset = ($ajax_call - 1) * $per_post;
		$attachments = $this->fn_wmh_get_post_type_attachment_data($offset, $per_post);

		/* count total ajax call */
		$total_ajax_call = ceil(($total_attachments / $per_post));

		/* get progress bar width and percentage */
		$percentage = (100 / $total_ajax_call);
		$progress_bar_width = number_format(($progress_bar + $percentage), 2);

		if (!empty($attachments)) {
			/* size array */
			$media_size_array = array();
			/* get all intermediate image sizes */
			$size_type = get_intermediate_image_sizes();
			array_push($size_type, 'full');

			/* Batch-load mime type and post_date — 1 query instead of N individual get_post() calls */
			$mime_map = [];
			$date_map = [];
			$id_list  = implode(',', array_map('intval', $attachments));
			$batch_rows = $this->conn->get_results(
				"SELECT ID, post_mime_type, post_date FROM {$this->wp_posts} WHERE ID IN ($id_list)",
				ARRAY_A
			);
			foreach ($batch_rows as $row) {
				$mime_map[(int)$row['ID']] = $row['post_mime_type'];
				$date_map[(int)$row['ID']] = $row['post_date'];
			}
			/* Prime the post metadata cache — eliminates per-attachment meta DB queries
			 * (covers wp_get_attachment_metadata, wp_get_original_image_url, wp_get_attachment_url) */
			update_meta_cache('post', array_map('intval', $attachments));

			foreach ($attachments as $id) {
				try {
				$all_urls = array();
				/* default flg */
				$flg = 0;
				/* get post mime type */
				$post_mime_type = sanitize_mime_type($mime_map[(int)$id] ?? '');
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

				/* check if this attachment is known-used via pre-computed index (built in Step 2 call 12) */
				if ($flg == 0 && !empty($all_urls)) {
					$attachment_basenames = [];
					foreach ($all_urls as $attachment_url) {
						$b = wp_basename($attachment_url);
						if ($b) $attachment_basenames[] = $b;
					}
					$attachment_basenames = array_values(array_filter(array_unique($attachment_basenames)));

					if (!empty($attachment_basenames)) {
						$bn_ph = implode(',', array_fill(0, count($attachment_basenames), '%s'));
						$is_known_used = (bool) $this->conn->get_var(
							$this->conn->prepare(
								"SELECT 1 FROM {$this->wmh_scan_known_used}
								 WHERE post_id = %d OR basename IN ($bn_ph) LIMIT 1",
								array_merge([(int) $id], $attachment_basenames)
							)
						);
						if ($is_known_used) {
							$flg = 1;
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
				$post_date = $date_map[(int)$id] ?? '';

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
				} catch (\Throwable $e) {
					$this->fn_wmh_log('step3_classify', 'Skipped attachment ' . $id . ': ' . $e->getMessage());
				}
			}
		}

		if ($ajax_call == $total_ajax_call) {
			/* truncate temp table data is scan is complete */
			$this->conn->query(' TRUNCATE TABLE ' . $this->wmh_temp . ' ');
			$this->conn->query(' TRUNCATE TABLE ' . $this->wmh_save_scan_content . ' ');
			$this->conn->query(' TRUNCATE TABLE ' . $this->wmh_scan_known_used . ' ');
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
		return $this->conn->get_col(
			$this->conn->prepare(
				"SELECT ID FROM {$this->wp_posts}
				 WHERE post_type = 'attachment' AND post_status != 'trash'
				 ORDER BY ID ASC
				 LIMIT %d OFFSET %d",
				(int) $per_post,
				(int) $offset
			)
		);
	}

	/* check post content — returns array of upload basenames found in post_content rows */
	public function fn_wmh_check_post_content()
	{
		$basenames = [];
		$chunk_size = 200;
		$offset = 0;
		do {
			$sql = $this->conn->prepare(
				'SELECT post_content FROM ' . $this->wp_posts . ' WHERE post_type = "post" AND post_content != "" LIMIT %d OFFSET %d',
				$chunk_size, $offset
			);
			$rows = $this->conn->get_results($sql, ARRAY_A);
			if (empty($rows)) break;
			foreach ($rows as $row) {
				preg_match_all('/\/wp-content\/uploads\/[^"\')\s,>]+/', $row['post_content'], $m);
				if (!empty($m[0])) {
					foreach ($m[0] as $u) {
						$b = wp_basename(rtrim($u, '\\/.,;'));
						if ($b && strpos($b, '.') !== false) {
							$basenames[] = $b;
						}
					}
				}
			}
			$basenames = array_unique($basenames);
			$offset += $chunk_size;
		} while (count($rows) === $chunk_size);
		return array_values($basenames);
	}

	/* check page content — returns array of upload basenames found in page post_content rows */
	public function fn_wmh_check_page_content()
	{
		$basenames = [];
		$chunk_size = 200;
		$offset = 0;
		do {
			$sql = $this->conn->prepare(
				'SELECT post_content FROM ' . $this->wp_posts . ' WHERE post_type = "page" AND post_content != "" LIMIT %d OFFSET %d',
				$chunk_size, $offset
			);
			$rows = $this->conn->get_results($sql, ARRAY_A);
			if (empty($rows)) break;
			foreach ($rows as $row) {
				preg_match_all('/\/wp-content\/uploads\/[^"\')\s,>]+/', $row['post_content'], $m);
				if (!empty($m[0])) {
					foreach ($m[0] as $u) {
						$b = wp_basename(rtrim($u, '\\/.,;'));
						if ($b && strpos($b, '.') !== false) {
							$basenames[] = $b;
						}
					}
				}
			}
			$basenames = array_unique($basenames);
			$offset += $chunk_size;
		} while (count($rows) === $chunk_size);
		return array_values($basenames);
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
			wp_send_json_error(null, 403);
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "media_hygiene_nonce")) {
			$this->fn_wmh_log('row_action_trash', 'Nonce verification failed.');
			wp_die(esc_html__('Security check. Hacking not allowed', MEDIA_HYGIENE), '', array('response' => 403));
		}

		@set_time_limit(300);

		if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'row_action_trash') {
			$post_id    = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
			$media_size = isset($_POST['file_size']) ? sanitize_text_field($_POST['file_size']) : 0;

			if ($post_id <= 0) {
				$this->fn_wmh_log('row_action_trash', 'Invalid post_id received.');
				echo json_encode(['flg' => -1, 'message' => esc_html__('Invalid media ID.', MEDIA_HYGIENE)]);
				wp_die();
			}

			if (get_post_type($post_id) !== 'attachment') {
				$this->fn_wmh_log('row_action_trash', "post_id={$post_id} is not an attachment.");
				echo json_encode(['flg' => -1, 'message' => esc_html__('Item is not a valid media attachment.', MEDIA_HYGIENE)]);
				wp_die();
			}

			$trashed_from_media_posts = wp_trash_post($post_id);
			if ($trashed_from_media_posts) {
				$this->conn->delete($this->wmh_unused_media_post_id, array('post_id' => $post_id));
				$update_statistics_array = array(
					'call' => 'single_trash',
					'count' => 1,
					'size' => $media_size
				);
				$this->fn_wmh_update_statistics_data_on_delete($update_statistics_array);
				echo json_encode(['flg' => 1, 'message' => esc_html__('File trashed successfully.', MEDIA_HYGIENE)]);
			} else {
				$this->fn_wmh_log('row_action_trash', "wp_trash_post failed for post_id={$post_id}.");
				echo json_encode(['flg' => 0, 'message' => esc_html__('There was an error trashing this media file.', MEDIA_HYGIENE)]);
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
			wp_send_json_error(null, 403);
		}

		$wp_nonce = sanitize_text_field($_POST['nonce'] ?? '');
		if (!wp_verify_nonce($wp_nonce, 'trash_page_media_nonce')) {
			$this->fn_wmh_log('trash_page_media', 'Nonce verification failed.');
			wp_die(esc_html__('Security check. Hacking not allowed', MEDIA_HYGIENE), '', array('response' => 403));
		}

		@set_time_limit(300);

		$wmh_scan_option_data = get_option('wmh_scan_option_data', true);
		$media_per_page_input = 10;
		if (isset($wmh_scan_option_data['media_per_page_input']) && $wmh_scan_option_data['media_per_page_input'] != '' && $wmh_scan_option_data['media_per_page_input'] != 0) {
			$media_per_page_input = (int) $wmh_scan_option_data['media_per_page_input'];
		}

		$per_post = $media_per_page_input;
		$paged    = absint($_POST['paged'] ?? 1);
		if ($paged < 1) { $paged = 1; }
		$offset   = $per_post * ($paged - 1);

		$attachment_cat = isset($_POST['attachment_cat']) ? sanitize_text_field($_POST['attachment_cat']) : '';
		$filter_date    = isset($_POST['date'])           ? sanitize_text_field($_POST['date'])           : '';

		$where_parts  = [];
		$where_values = [];

		if ($attachment_cat === 'images') {
			$where_parts[]  = 'p.post_mime_type LIKE %s';
			$where_values[] = '%image%';
		} elseif ($attachment_cat === 'documents') {
			$where_parts[]  = 'p.post_mime_type LIKE %s';
			$where_values[] = '%application%';
		} elseif ($attachment_cat === 'audio') {
			$where_parts[]  = 'p.post_mime_type LIKE %s';
			$where_values[] = '%audio%';
		} elseif ($attachment_cat === 'video') {
			$where_parts[]  = 'p.post_mime_type LIKE %s';
			$where_values[] = '%video%';
		} elseif ($attachment_cat === 'others') {
			foreach (['%image%', '%application%', '%audio%', '%video%'] as $not_mime) {
				$where_parts[]  = 'p.post_mime_type NOT LIKE %s';
				$where_values[] = $not_mime;
			}
		}

		if ($filter_date !== '') {
			$where_parts[]  = 'p.post_date LIKE %s';
			$where_values[] = '%' . $this->conn->esc_like($filter_date) . '%';
		}

		if (!empty($where_parts)) {
			$extra_where = ' AND ' . implode(' AND ', $where_parts);
			$sql = $this->conn->prepare(
				'SELECT u.post_id, u.size FROM ' . $this->wmh_unused_media_post_id . ' u
				 INNER JOIN ' . $this->wp_posts . ' p ON p.ID = u.post_id
				 WHERE p.post_type = \'attachment\'' . $extra_where . ' LIMIT %d OFFSET %d',
				...array_merge($where_values, [$per_post, $offset])
			);
		} else {
			$sql = $this->conn->prepare(
				'SELECT post_id, size FROM ' . $this->wmh_unused_media_post_id . ' LIMIT %d OFFSET %d',
				$per_post, $offset
			);
		}
		$rows    = $this->conn->get_results($sql, ARRAY_A);

		if (empty($rows)) {
			echo json_encode(['flg' => 0, 'message' => esc_html__('There is no unused media to trash.', MEDIA_HYGIENE)]);
			wp_die();
		}

		$trashed_ids  = [];
		$size_sum     = 0;
		foreach ($rows as $row) {
			$post_id = (int) $row['post_id'];
			$size_sum += (float) $row['size'];
			$result  = wp_trash_post($post_id);
			if ($result) {
				$trashed_ids[] = $post_id;
			} else {
				$this->fn_wmh_log('trash_page_media', "wp_trash_post failed for post_id={$post_id}.");
				$wmh_general = new wmh_general();
				$wmh_general->fn_wmh_error_log('Scan trash page media', 'Attachment not trashed for trash page media');
			}
		}

		/* Batch DELETE from plugin table for all successfully trashed IDs */
		if (!empty($trashed_ids)) {
			$id_ph = implode(',', array_fill(0, count($trashed_ids), '%d'));
			$this->conn->query(
				$this->conn->prepare(
					'DELETE FROM ' . $this->wmh_unused_media_post_id . ' WHERE post_id IN (' . $id_ph . ')',
					$trashed_ids
				)
			);
		}

		$trashed_count = count($trashed_ids);
		if ($trashed_count > 0) {
			$update_statistics_array = [
				'call'  => 'page_trash',
				'count' => $trashed_count,
				'size'  => $size_sum,
			];
			$this->fn_wmh_update_statistics_data_on_delete($update_statistics_array);
			$flg     = 1;
			$message = sprintf(
				__('%d file(s) moved to Trash (%s freed from dashboard count).', MEDIA_HYGIENE),
				$trashed_count,
				size_format($size_sum)
			);
		} else {
			$flg     = 0;
			$message = esc_html__('No media files could be trashed on this page.', MEDIA_HYGIENE);
			$this->fn_wmh_log('trash_page_media', 'No items trashed from page ' . $paged);
		}

		echo json_encode(['flg' => $flg, 'message' => $message, 'trashed_count' => $trashed_count]);
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
		if (!current_user_can('manage_options')) {
			wp_send_json_error(null, 403);
		}


		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'media_hygiene_nonce')) {
			wp_die(esc_html(__('Security check failed. Hacking attempt detected.', MEDIA_HYGIENE)));
		}

		if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'whitelist_single_image_call') {

			if (isset($_POST['post_id']) && $_POST['post_id'] != '') {

				$post_id = (int) sanitize_text_field($_POST['post_id']);
				$whitelist_posts_result = array();
				$whitelist_posts_sql = $this->conn->prepare('SELECT * FROM ' . $this->wmh_unused_media_post_id . ' WHERE post_id = %d', $post_id);
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
		if (!current_user_can('manage_options')) {
			wp_send_json_error(null, 403);
		}

		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'media_hygiene_nonce')) {
			wp_die(esc_html(__('Security check failed. Hacking attempt detected.', MEDIA_HYGIENE)));
		}

		if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'blacklist_single_image_call') {

			if (isset($_POST['post_id']) && $_POST['post_id'] != '') {

				$post_id = (int) sanitize_text_field($_POST['post_id']);
				$whitelist_posts_result = array();
				$whitelist_posts_sql = $this->conn->prepare('SELECT * FROM ' . $this->wmh_whitelist_media_post_id . ' WHERE post_id = %d', $post_id);
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
		if (!current_user_can('manage_options')) {
			wp_send_json_error(null, 403);
		}

		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'media_hygiene_nonce')) {
			wp_die(esc_html(__('Security check failed. Hacking attempt detected.', MEDIA_HYGIENE)));
		}

		$attachment_cat_raw = isset($_POST['attachment_cat']) ? sanitize_text_field($_POST['attachment_cat']) : '';
		$date_raw           = isset($_POST['date'])           ? sanitize_text_field($_POST['date'])           : '';
		$list_element_raw   = isset($_POST['list_element'])   ? sanitize_text_field($_POST['list_element'])   : '';

		$list_element = '';
		if ($list_element_raw === 'blacklist') {
			$list_element = '&type=blacklist';
		} elseif ($list_element_raw === 'whitelist') {
			$list_element = '&type=whitelist';
		} elseif ($list_element_raw === 'trash') {
			$list_element = '&type=trash';
		}

		if ($attachment_cat_raw !== '' && $date_raw !== '') {
			$flg = 1;
			$url = admin_url() . 'admin.php?page=wmh-media-hygiene' . $list_element . '&attachment_cat=' . $attachment_cat_raw . '&date=' . $date_raw;
			$output = array( 'flg' => $flg, 'url' => $url );
		} elseif ($attachment_cat_raw !== '' && $date_raw === '') {
			$flg = 2;
			$url = admin_url() . 'admin.php?page=wmh-media-hygiene' . $list_element . '&attachment_cat=' . $attachment_cat_raw;
			$output = array( 'flg' => $flg, 'url' => $url );
		} elseif ($attachment_cat_raw === '' && $date_raw !== '') {
			$flg = 3;
			$url = admin_url() . 'admin.php?page=wmh-media-hygiene' . $list_element . '&date=' . $date_raw;
			$output = array( 'flg' => $flg, 'url' => $url );
		} else {
			$flg = 4;
			$url = admin_url() . 'admin.php?page=wmh-media-hygiene' . $list_element;
			$output = array( 'flg' => $flg, 'url' => $url );
		}

		echo json_encode($output);
		wp_die();
	}

	public function fn_wmh_bulk_action_trash()
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(null, 403);
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "media_hygiene_nonce")) {
			$this->fn_wmh_log('bulk_action_trash', 'Nonce verification failed.');
			wp_die(esc_html__('Security check. Hacking not allowed', MEDIA_HYGIENE), '', array('response' => 403));
		}

		@set_time_limit(300);

		$flg     = 0;
		$message = esc_html__('Something went wrong trashing media.', MEDIA_HYGIENE);

		if (
			isset($_POST['action']) && sanitize_text_field($_POST['action']) === 'bulk_action_trash' &&
			isset($_POST['bulk_action_val']) && sanitize_text_field($_POST['bulk_action_val']) === 'trash' &&
			isset($_POST['chek_box_val']) && !empty($_POST['chek_box_val'])
		) {
			/* Sanitise IDs — cast to int, drop zeros */
			$raw_ids  = rest_sanitize_array($_POST['chek_box_val']);
			$post_ids = array_values(array_filter(array_map('intval', $raw_ids)));

			/* Validate sizes as numeric */
			$size_array = [];
			foreach (rest_sanitize_array($_POST['size'] ?? []) as $s) {
				if (is_numeric($s)) { $size_array[] = (float) $s; }
			}

			if (empty($post_ids)) {
				echo json_encode(['flg' => -1, 'message' => esc_html__('No valid media IDs received.', MEDIA_HYGIENE)]);
				wp_die();
			}

			$trashed_ids = [];
			foreach ($post_ids as $d_id) {
				$result = wp_trash_post($d_id);
				if ($result) {
					$trashed_ids[] = $d_id;
				} else {
					$this->fn_wmh_log('bulk_action_trash', "wp_trash_post failed for post_id={$d_id}.");
					$wmh_general = new wmh_general();
					$wmh_general->fn_wmh_error_log('Scan trash bulk action', 'Attachment not trashed for bulk action trash');
				}
			}

			/* Batch DELETE from plugin table */
			if (!empty($trashed_ids)) {
				$id_ph = implode(',', array_fill(0, count($trashed_ids), '%d'));
				$this->conn->query(
					$this->conn->prepare(
						'DELETE FROM ' . $this->wmh_unused_media_post_id . ' WHERE post_id IN (' . $id_ph . ')',
						$trashed_ids
					)
				);
			}

			$trashed_count = count($trashed_ids);
			$update_statistics_array = [
				'call'  => 'bulk_trash',
				'count' => $trashed_count,
				'size'  => array_sum($size_array),
			];
			$this->fn_wmh_update_statistics_data_on_delete($update_statistics_array);
			$flg     = 1;
			$message = esc_html(sprintf(
				__('Total images trashed: %d, Free up space: %s', MEDIA_HYGIENE),
				$trashed_count,
				size_format(array_sum($size_array))
			));
		}

		echo json_encode(['flg' => $flg, 'message' => $message]);
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
		if (!current_user_can('manage_options')) {
			wp_send_json_error(null, 403);
		}

		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'media_hygiene_nonce')) {
			wp_die(esc_html(__('Security check failed. Hacking attempt detected.', MEDIA_HYGIENE)));
		}

		if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'bulk_action_to_whitelist') {
			if (isset($_POST['bulk_action_val']) && sanitize_text_field($_POST['bulk_action_val']) == 'whitelist') {
				$chek_box_val = rest_sanitize_array($_POST['chek_box_val']);
				$size = array();
				$media_size_array = array();
				foreach ($chek_box_val as $d_id) {
					$sql = $this->conn->prepare('SELECT * FROM ' . $this->wmh_unused_media_post_id . ' WHERE post_id = %d', (int) $d_id);
					$data = $this->conn->get_row($sql, ARRAY_A);
					$data['id'] = '';
					if (isset($data) && !empty($data)) {
						$whitelist_post_id_inserted = $this->conn->insert($this->wmh_whitelist_media_post_id, $data);
						if ($whitelist_post_id_inserted) {
							$deleted = $this->conn->delete($this->wmh_unused_media_post_id, array('post_id' => (int) $d_id));
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

		if (!current_user_can('manage_options')) {
			wp_send_json_error(null, 403);
		}

		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'media_hygiene_nonce')) {
			wp_die(esc_html(__('Security check failed. Hacking attempt detected.', MEDIA_HYGIENE)));
		}

		if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'bulk_action_to_blacklist') {
			if (isset($_POST['bulk_action_val']) && sanitize_text_field($_POST['bulk_action_val']) == 'blacklist') {
				$chek_box_val = rest_sanitize_array($_POST['chek_box_val']);
				$size = array();
				$media_size_array = array();
				foreach ($chek_box_val as $d_id) {
					$sql = $this->conn->prepare('SELECT * FROM ' . $this->wmh_whitelist_media_post_id . ' WHERE post_id = %d', (int) $d_id);
					$data = $this->conn->get_row($sql, ARRAY_A);
					$data['id'] = '';
					if (isset($data) && !empty($data)) {
						$whitelist_post_id_inserted = $this->conn->insert($this->wmh_unused_media_post_id, $data);
						if ($whitelist_post_id_inserted) {
							$deleted = $this->conn->delete($this->wmh_whitelist_media_post_id, array('post_id' => (int) $d_id));
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
			wp_send_json_error(null, 403);
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "media_hygiene_nonce")) {
			wp_die(esc_html__('Security check. Hacking not allowed', MEDIA_HYGIENE), '', array('response' => 403));
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
			wp_send_json_error(null, 403);
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "media_hygiene_nonce")) {
			wp_die(esc_html__('Security check. Hacking not allowed', MEDIA_HYGIENE), '', array('response' => 403));
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
			wp_send_json_error(null, 403);
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "wmh_bulk_restore")) {
			wp_die(esc_html__('Security check. Hacking not allowed', MEDIA_HYGIENE), '', array('response' => 403));
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
			$message = __('Media restored successfully. Please note that it may take some time to update the dashboard statistics after you click OK.', MEDIA_HYGIENE);
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
			wp_send_json_error(null, 403);
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "media_hygiene_nonce")) {
			$this->fn_wmh_log('delete_permanently_single', 'Nonce verification failed.');
			wp_die(esc_html__('Security check. Hacking not allowed', MEDIA_HYGIENE), '', array('response' => 403));
		}

		$post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;

		if ($post_id <= 0) {
			$this->fn_wmh_log('delete_permanently_single', 'Invalid post_id received.');
			echo json_encode(['flg' => -1, 'message' => esc_html__('Invalid media ID.', MEDIA_HYGIENE)]);
			wp_die();
		}

		if (get_post_type($post_id) !== 'attachment') {
			$this->fn_wmh_log('delete_permanently_single', "post_id={$post_id} is not an attachment.");
			echo json_encode(['flg' => -1, 'message' => esc_html__('Item is not a valid media attachment.', MEDIA_HYGIENE)]);
			wp_die();
		}

		/* Fetch URL BEFORE deletion — wp_get_attachment_url() returns false after the post is deleted */
		$post_mime_type = sanitize_mime_type(get_post_mime_type($post_id));
		$guid = str_contains($post_mime_type, 'image')
			? wp_get_original_image_url($post_id)
			: wp_get_attachment_url($post_id);

		$deleted_from_media_posts = wp_delete_attachment($post_id, true);
		if ($deleted_from_media_posts) {
			/* Log to deleted media AFTER successful delete, using the pre-fetched URL */
			if ($guid) {
				$this->conn->insert($this->wmh_deleted_media, [
					'id'           => '',
					'post_id'      => $post_id,
					'url'          => $guid,
					'date_created' => date('Y-m-d H:i:s'),
					'date_updated' => date('Y-m-d H:i:s'),
				]);
			}
			$this->conn->delete($this->wmh_unused_media_post_id, ['post_id' => $post_id]);
			echo json_encode(['flg' => 1, 'message' => esc_html__('File deleted successfully.', MEDIA_HYGIENE)]);
		} else {
			$this->fn_wmh_log('delete_permanently_single', "wp_delete_attachment failed for post_id={$post_id}.");
			echo json_encode(['flg' => 0, 'message' => esc_html__('There was an error deleting this media file.', MEDIA_HYGIENE)]);
		}
		wp_die();
	}

	public function fn_wmh_bulk_action_delete()
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(null, 403);
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "media_hygiene_nonce")) {
			$this->fn_wmh_log('bulk_action_delete', 'Nonce verification failed.');
			wp_die(esc_html__('Security check. Hacking not allowed', MEDIA_HYGIENE), '', array('response' => 403));
		}

		@set_time_limit(300);

		$flg     = 0;
		$message = esc_html__('Something went wrong deleting media.', MEDIA_HYGIENE);

		if (
			isset($_POST['bulk_action_val']) && sanitize_text_field($_POST['bulk_action_val']) === 'delete' &&
			isset($_POST['chek_box_val']) && !empty($_POST['chek_box_val'])
		) {
			/* Sanitise IDs — cast to int, drop zeros */
			$raw_ids  = rest_sanitize_array($_POST['chek_box_val']);
			$post_ids = array_values(array_filter(array_map('intval', $raw_ids)));

			/* Validate sizes as numeric */
			$size_array = [];
			foreach (rest_sanitize_array($_POST['size'] ?? []) as $s) {
				if (is_numeric($s)) { $size_array[] = (float) $s; }
			}

			if (empty($post_ids)) {
				echo json_encode(['flg' => -1, 'message' => esc_html__('No valid media IDs received.', MEDIA_HYGIENE)]);
				wp_die();
			}

			$deleted_ids  = [];
			$log_rows     = [];
			$log_values   = [];
			$now          = date('Y-m-d H:i:s');

			foreach ($post_ids as $d_id) {
				if (get_post_type($d_id) !== 'attachment') {
					$this->fn_wmh_log('bulk_action_delete', "post_id={$d_id} is not an attachment, skipping.");
					continue;
				}

				$post_mime_type = sanitize_mime_type(get_post_mime_type($d_id));

				/* Fetch URL BEFORE deletion — wp_get_attachment_url returns false after delete */
				$guid = str_contains($post_mime_type, 'image')
					? wp_get_original_image_url($d_id)
					: wp_get_attachment_url($d_id);

				$deleted = wp_delete_attachment($d_id, true);
				if ($deleted) {
					$deleted_ids[] = $d_id;
					if ($guid) {
						$log_rows[]   = '(%d, %s, %s, %s)';
						$log_values[] = $d_id;
						$log_values[] = $guid;
						$log_values[] = $now;
						$log_values[] = $now;
					}
				} else {
					$this->fn_wmh_log('bulk_action_delete', "wp_delete_attachment failed for post_id={$d_id}.");
					$wmh_general = new wmh_general();
					$wmh_general->fn_wmh_error_log('Scan delete bulk action', 'Attachment not deleted for bulk action delete');
				}
			}

			/* Batch INSERT into deleted media log */
			if (!empty($log_rows)) {
				$this->conn->query(
					$this->conn->prepare(
						'INSERT IGNORE INTO ' . $this->wmh_deleted_media . ' (post_id, url, date_created, date_updated) VALUES ' . implode(',', $log_rows),
						$log_values
					)
				);
			}

			/* Batch DELETE from plugin table */
			if (!empty($deleted_ids)) {
				$id_ph = implode(',', array_fill(0, count($deleted_ids), '%d'));
				$this->conn->query(
					$this->conn->prepare(
						'DELETE FROM ' . $this->wmh_unused_media_post_id . ' WHERE post_id IN (' . $id_ph . ')',
						$deleted_ids
					)
				);
			}

			$deleted_count = count($deleted_ids);
			$flg           = 1;
			$message       = esc_html(sprintf(
				__('Total images deleted: %d, Free up space: %s', MEDIA_HYGIENE),
				$deleted_count,
				size_format(array_sum($size_array))
			));
		}

		echo json_encode(['flg' => $flg, 'message' => $message]);
		wp_die();
	}

	public function fn_wmh_delete_permanently()
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(null, 403);
		}

		$wp_nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';
		if (!wp_verify_nonce($wp_nonce, "wmh_delete_permanently")) {
			$this->fn_wmh_log('delete_permanently', 'Nonce verification failed.');
			wp_die(esc_html__('Security check. Hacking not allowed', MEDIA_HYGIENE), '', array('response' => 403));
		}

		@set_time_limit(300);

		$ajax_call = (int) ($_POST['ajax_call'] ?? 0);
		$per_post  = 25;

		/* Call 0: initialise — count trashed attachments with a direct SQL query */
		if ($ajax_call === 0) {
			$trash_count = (int) $this->conn->get_var(
				$this->conn->prepare(
					'SELECT COUNT(*) FROM ' . $this->wp_posts . ' WHERE post_type = %s AND post_status = %s',
					'attachment', 'trash'
				)
			);

			if ($trash_count === 0) {
				echo json_encode([
					'flg'          => 0,
					'message'      => esc_html__('There are currently no media items in the trash.', MEDIA_HYGIENE),
					'progress_bar' => 0,
				]);
				wp_die();
			}

			update_option('wmh_bulk_delete_permanently_total_trash_media', $trash_count, 'no');
			echo json_encode([
				'flg'          => 1,
				'ajax_call'    => 1,
				'message'      => esc_html__('Pending ...', MEDIA_HYGIENE),
				'progress_bar' => 0,
			]);
			wp_die();
		}

		$total_initial    = (int) get_option('wmh_bulk_delete_permanently_total_trash_media', 0);
		$total_ajax_call  = ($total_initial > 0) ? (int) ceil($total_initial / $per_post) : 1;

		/* Fetch next batch of trashed attachments */
		$post_ids = get_posts([
			'post_type'      => 'attachment',
			'post_status'    => 'trash',
			'posts_per_page' => $per_post,
			'fields'         => 'ids',
		]);

		/* Empty batch → all done */
		if (empty($post_ids)) {
			delete_option('wmh_bulk_delete_permanently_total_trash_media');
			echo json_encode([
				'flg'          => 2,
				'ajax_call'    => $ajax_call,
				'message'      => esc_html__('Media deleted successfully. Please note that it may take some time to update the dashboard statistics after you click OK.', MEDIA_HYGIENE),
				'progress_bar' => 100,
			]);
			wp_die();
		}

		$deleted_ids = [];
		foreach ($post_ids as $a_id) {
			$result = wp_delete_attachment((int) $a_id, true);
			if ($result) {
				$deleted_ids[] = (int) $a_id;
			} else {
				$this->fn_wmh_log('delete_permanently', "wp_delete_attachment failed for post_id={$a_id}.");
			}
		}

		/* Stuck detection — if nothing was deleted from the plugin table, stop to avoid infinite loop */
		if (!empty($deleted_ids)) {
			$id_ph = implode(',', array_fill(0, count($deleted_ids), '%d'));
			$this->conn->query(
				$this->conn->prepare(
					'DELETE FROM ' . $this->wmh_unused_media_post_id . ' WHERE post_id IN (' . $id_ph . ')',
					$deleted_ids
				)
			);
		}

		if (empty($deleted_ids)) {
			delete_option('wmh_bulk_delete_permanently_total_trash_media');
			$this->fn_wmh_log('delete_permanently', 'Stuck: wp_delete_attachment returned false for all items in batch at ajax_call=' . $ajax_call);
			echo json_encode([
				'flg'     => -1,
				'message' => esc_html__('Delete operation appears stuck — no items could be deleted in this batch. Please check the error log.', MEDIA_HYGIENE),
			]);
			wp_die();
		}

		/* Server-side progress: count remaining trashed attachments */
		$remaining    = (int) $this->conn->get_var(
			$this->conn->prepare(
				'SELECT COUNT(*) FROM ' . $this->wp_posts . ' WHERE post_type = %s AND post_status = %s',
				'attachment', 'trash'
			)
		);
		$progress_bar = ($total_initial > 0)
			? number_format(min((($total_initial - $remaining) / $total_initial) * 100, 100), 2)
			: 100;

		if ($remaining === 0 || $ajax_call >= $total_ajax_call) {
			delete_option('wmh_bulk_delete_permanently_total_trash_media');
			echo json_encode([
				'flg'          => 2,
				'ajax_call'    => $ajax_call,
				'message'      => esc_html__('Media deleted successfully. Please note that it may take some time to update the dashboard statistics after you click OK.', MEDIA_HYGIENE),
				'progress_bar' => 100,
			]);
			wp_die();
		}

		echo json_encode([
			'flg'             => 1,
			'ajax_call'       => $ajax_call + 1,
			'message'         => esc_html__('Pending ...', MEDIA_HYGIENE),
			'total_ajax_call' => $total_ajax_call,
			'progress_bar'    => $progress_bar,
		]);
		wp_die();
	}

	public function fn_wmh_fetch_data_from_elementor()
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(null, 403);
		}

		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'media_hygiene_nonce')) {
			wp_die(esc_html(__('Security check failed. Hacking attempt detected.', MEDIA_HYGIENE)));
		}

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
