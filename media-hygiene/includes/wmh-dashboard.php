<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class wmh_dashboard
{
    public $conn;
    public $wp_posts;
    public $wmh_unused_media_post_id;
    public $wmh_whitelist_media_post_id;
    public $wp_upload_dir;
    public $basedir;
    public $wmh_used_media_post_id;
    public $wmh_error_log;
    public $wmh_temp;
    public $wmh_deleted_media;
    public $wmh_save_scan_content;

    public function __construct()
    {

        global $wpdb;
        $this->conn = $wpdb;
        $this->wp_upload_dir = wp_upload_dir();
        $this->basedir = $this->wp_upload_dir['basedir'];
        $this->wp_posts = $this->conn->prefix . 'posts';
        $this->wmh_unused_media_post_id = $this->conn->prefix . MH_PREFIX . 'unused_media_post_id';
        $this->wmh_whitelist_media_post_id = $this->conn->prefix . MH_PREFIX . 'whitelist_media_post_id';
        $this->wmh_used_media_post_id = $this->conn->prefix . MH_PREFIX . 'used_media_post_id';
        $this->wmh_error_log = $this->conn->prefix . MH_PREFIX . 'error_log';
        $this->wmh_temp = $this->conn->prefix . MH_PREFIX . 'temp';
        $this->wmh_deleted_media = $this->conn->prefix . MH_PREFIX . 'deleted_media';
        $this->wmh_save_scan_content = $this->conn->prefix . MH_PREFIX . 'save_scan_content';
        /* fetch statistics data  */
        add_action('wp_ajax_fetch_statistics_data', array($this, 'fn_wmh_fetch_statistics_data'));

        /* anonymous analytics permission */
        add_action('wp_ajax_wmh_aap_action', [$this, 'fn_wmh_aap_action']);

        /* anonymous analytics permission close permanently */
        add_action('wp_ajax_wmh_aap_close_notice_permanently_action', [$this, 'fn_wmh_aap_close_notice_permanently_action']);

        /* update database by version */
        add_action('wp_ajax_database_update_wmh_by_version', array($this, 'fn_wmh_database_update_wmh_by_version'));
    }

    public function fn_wmh_fetch_statistics_data()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'media_hygiene_nonce')) {
            wp_die(esc_html(__('Security check failed. Hacking attempt detected.', MEDIA_HYGIENE)));
        }

        $flg = 0;

        /* check ajax call number */
        $statistics_ajax_call = sanitize_text_field($_POST['statistics_ajax_call']);

        if ($statistics_ajax_call == '1') {
            update_option('wmh_media_count', '0', 'no');
            update_option('wmh_total_media_size', '0', 'no');
            update_option('wmh_total_unused_media_count', '0', 'no');
            update_option('wmh_unused_media_size', '0', 'no');
            update_option('wmh_use_media_count', '0', 'no');
            update_option('wmh_use_media_size', '0', 'no');
            update_option('wmh_media_type_info', '', 'no');
            update_option('wmh_media_breakdown', '', 'no');
        }

        /* get scan status */
        $wmh_scan_status = get_option('wmh_scan_status');

        if (isset($wmh_scan_status) && $wmh_scan_status == '1' && $wmh_scan_status != '' && $wmh_scan_status != '0') {

            /* get general summery data */

            $media_count = 0;
            $total_media_size = 0;
            $total_unused_media_count = 0;
            $unused_media_count_size = 0;
            $use_media_count = 0;
            $used_media_count_size = 0;
            $media_type_info = array();
            $media_breakdown = array();

            /* get total media count */
            if ($statistics_ajax_call == '1') {
                $media_count = $this->fn_wmh_get_count_media();
                update_option('wmh_media_count', $media_count, 'no');
                $flg = '1';
                $progress_bar = 11.11;
            }

            /* get total media size. */
            if ($statistics_ajax_call == '2') {
                $total_media_size = $this->fn_fetch_total_media_size();
                update_option('wmh_total_media_size', $total_media_size, 'no');
                $flg = '1';
                $progress_bar = 22.22;
            }

            /* get count unused media. */
            if ($statistics_ajax_call == '3') {
                $total_unused_media_count = $this->fn_wmh_get_count_unused_media();
                update_option('wmh_total_unused_media_count', $total_unused_media_count, 'no');
                $flg = '1';
                $progress_bar = 33.33;
            }

            /* get unused media size. */
            if ($statistics_ajax_call == '4') {
                $unused_media_count_size = $this->fn_fetch_total_unused_media_size();
                update_option('wmh_unused_media_size', $unused_media_count_size, 'no');
                $flg = '1';
                $progress_bar = 44.44;
            }

            /* get used media count. */
            if ($statistics_ajax_call == '5') {
                $use_media_count = $this->fn_wmh_get_used_media_count();
                update_option('wmh_use_media_count', $use_media_count, 'no');
                $flg = '1';
                $progress_bar = 55.55;
            }

            if ($statistics_ajax_call == '6') {
                $used_media_count_size = $this->fn_fetch_total_used_media_size();
                update_option('wmh_use_media_size', $used_media_count_size, 'no');
                $flg = '1';
                $progress_bar = 66.66;
            }

            /* media type info */
            if ($statistics_ajax_call == '7') {
                $media_type_info = $this->fn_wmh_media_type_info();
                update_option('wmh_media_type_info', $media_type_info, 'no');
                $flg = '1';
                $progress_bar = 77.77;
            }

            /* get media brekdown */
            if ($statistics_ajax_call == '8') {

                $media_breakdown = $this->fn_wmh_get_media_breakdown();
                update_option('wmh_media_breakdown', $media_breakdown, 'no');
                $flg = '1';
                $progress_bar = 88.88;
            }

            /* get send data to server permission */
            if ($statistics_ajax_call == '9') {
                /* declare data array */
                $data_array = array();
                /* get send data to server permission */
                $permission_status = get_option('wmh_send_data_to_server_permission');
                if ($permission_status == 'on') {
                    /* total media count */
                    $toal_media_count = get_option('wmh_media_count');
                    /* get total media size */
                    $total_media_size = size_format(get_option('wmh_total_media_size'));
                    /* get used media count */
                    $used_media_count = get_option('wmh_use_media_count');
                    /* get used media count size*/
                    $used_media_count_size = size_format(get_option('wmh_use_media_size'));
                    /* get unused media count */
                    $unused_media_count = get_option('wmh_total_unused_media_count');
                    /* get unused media count size */
                    $unused_media_count_size = size_format(get_option('wmh_unused_media_size'));
                    /* insert array as data array */
                    $data_array = array(
                        "site_url" => site_url(),
                        "total_media" => $toal_media_count,
                        "total_media_size" => $total_media_size,
                        "used_media" => $used_media_count,
                        "used_media_size" => $used_media_count_size,
                        "unused_media" => $unused_media_count,
                        "unused_media_size" => $unused_media_count_size,
                        "date_created" => date('Y-m-d H:i:s'),
                        "date_updated" => date('Y-m-d H:i:s')
                    );
                    /* send result data to server */
                    if (!empty($data_array)) {
                        $this->fn_wmh_send_scan_data_to_server($data_array);
                    }
                }
                $flg = '2';
                $progress_bar = 100;
            }

            $output = array(
                'flg' => $flg,
                'ajax_call' => $statistics_ajax_call,
                'progress_bar_width' => $progress_bar
            );
            echo json_encode($output);
            wp_die();
        }
    }

    /* get total media count. */
    public function fn_wmh_get_count_media()
    {
        $count = wp_count_attachments();
        if (isset($count->trash)) {
            unset($count->trash);
        }
        $count = array_sum(json_decode(json_encode($count), true));
        return $count;
    }

    public function fn_fetch_total_media_size()
    {
        $final_file_size = 0;
        /* unused media size */
        $unused_media_size = $this->fn_fetch_total_unused_media_size();
        /* used media size */
        $used_media_size = $this->fn_fetch_total_used_media_size();
        /* final size */
        $final_file_size = ($unused_media_size + $used_media_size);
        return $final_file_size;
    }

    public function fn_fetch_total_unused_media_size()
    {
        /* get unused media size */
        $unused_media_size_sql = 'SELECT SUM(size) as file_size FROM ' . $this->wmh_unused_media_post_id . ' ';
        $unused_media_size_result = $this->conn->get_row($unused_media_size_sql, ARRAY_A);

        $unused_media_size = 0;
        if (isset($unused_media_size_result['file_size']) && $unused_media_size_result['file_size'] != '' && $unused_media_size_result['file_size'] != 0) {
            $unused_media_size = $unused_media_size_result['file_size'];
        }

        return $unused_media_size;
    }

    /* get count unused media. */
    public function fn_wmh_get_count_unused_media()
    {
        $unused_media_count_sql = 'SELECT COUNT(id) as unused_media_count FROM ' . $this->wmh_unused_media_post_id . '';
        $unused_media_count_result = $this->conn->get_row($unused_media_count_sql, ARRAY_A);
        $unused_media_count = '0';
        if (isset($unused_media_count_result['unused_media_count']) && $unused_media_count_result['unused_media_count'] != '' && $unused_media_count_result['unused_media_count'] != '0') {
            $unused_media_count = $unused_media_count_result['unused_media_count'];
        }
        return $unused_media_count;
    }

    /* get used media count */
    public function fn_wmh_get_used_media_count()
    {
        $used_media_count_sql = 'SELECT COUNT(id) as used_media_count FROM ' . $this->wmh_used_media_post_id . '';
        $used_media_count_result = $this->conn->get_row($used_media_count_sql, ARRAY_A);
        $used_media_count = '0';
        if (isset($used_media_count_result['used_media_count']) && $used_media_count_result['used_media_count'] != '' && $used_media_count_result['used_media_count'] != '0') {
            $used_media_count = $used_media_count_result['used_media_count'];
        }
        return $used_media_count;
    }

    /* get used media size */
    public function fn_fetch_total_used_media_size()
    {
        /* get used media size */
        $used_media_size_sql = 'SELECT SUM(size) as file_size FROM ' . $this->wmh_used_media_post_id . ' ';
        $used_media_size_result = $this->conn->get_row($used_media_size_sql, ARRAY_A);

        $used_media_size = 0;
        if (isset($used_media_size_result['file_size']) && $used_media_size_result['file_size'] != '' && $used_media_size_result['file_size'] != 0) {
            $used_media_size = $used_media_size_result['file_size'];
        }

        return $used_media_size;
    }

    public function fn_wmh_media_type_info()
    {
        $result = [];
        $ext_per = '';
        $file_size_edit = '';
        $media_type_array = [];

        /* get total unused media count */
        $total_unused_media_count = get_option('wmh_total_unused_media_count');

        if ($total_unused_media_count != '' && $total_unused_media_count > 0) {

            $sql = 'SELECT ext, COUNT(ext) as ext_count, SUM(size) as file_size FROM ' . $this->wmh_unused_media_post_id . ' GROUP BY ext';
            $result = $this->conn->get_results($sql, ARRAY_A);

            $ext = '';
            $ext_count = '';
            $file_size_edit = '';
            $ext_per = '';

            if (!empty($result)) {
                foreach ($result as $v) {
                    /* ext */
                    if ($v['ext'] != '') {
                        $ext = $v['ext'];
                    }
                    /* ext count */
                    if ($v['ext_count'] != '') {
                        $ext_count = $v['ext_count'];
                    }
                    /* edit size */
                    if ($v['file_size'] != '') {
                        $file_size_edit = size_format($v['file_size']);
                    }
                    /* count percentage */
                    if ($v['ext_count'] != '') {
                        $ext_per = round(($v['ext_count'] * 100) / $total_unused_media_count, 4);
                    }
                    $media_type_array[] = [
                        'ext' => $ext,
                        'ext_count' => $ext_count,
                        'file_size' => $file_size_edit,
                        'ext_per' => $ext_per
                    ];
                }
            }
        }

        return $media_type_array;
    }

    /* get information about attchment type information, count and percentage. */
    public function fn_wmh_get_media_breakdown()
    {
        $result = [];
        $cat_per = '';
        $media_breakdown = [];

        /* get total unused media count */
        $total_unused_media_count = get_option('wmh_total_unused_media_count');

        if ($total_unused_media_count != '' && $total_unused_media_count > 0) {
            /* get data from data base */
            $sql = 'SELECT attachment_cat, COUNT( attachment_cat ) as cat_count FROM ' . $this->wmh_unused_media_post_id . ' GROUP BY attachment_cat';
            $result = $this->conn->get_results($sql, ARRAY_A);

            $attachment_cat = '';
            $cat_count = '';
            $cat_per = '';

            if (!empty($result)) {
                foreach ($result as $v) {
                    /* attachment cat */
                    if ($v['attachment_cat'] != '') {
                        $attachment_cat = $v['attachment_cat'];
                    }
                    /* cat count */
                    if ($v['cat_count'] != '') {
                        $cat_count = $v['cat_count'];
                        /* count percentage */
                        $cat_per = round(($cat_count * 100) / $total_unused_media_count, 4);
                    }
                    $media_breakdown[] = [
                        'attachment_cat' => $attachment_cat,
                        'cat_count' => $cat_count,
                        'cat_per' => $cat_per
                    ];
                }
            }
        }
        return $media_breakdown;
    }

    public function fn_wmh_send_scan_data_to_server($scan_results = array())
    {

        if (!empty($scan_results)) {

            $scan_results['action'] = 'scan-report';
            $content = json_encode($scan_results);
            $url = 'https://mediahygiene.com/wp-content/plugins/mh-license-server/';
            $args = array(
                'body'        => $content,
                'timeout'     => '5',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(),
                'cookies'     => array(),
            );
            wp_remote_post($url, $args);
        }
    }

    public function fn_wmh_aap_action()
    {

        if (!current_user_can('manage_options')) {
            return false;
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'media_hygiene_nonce')) {
            wp_die(esc_html(__('Security check failed. Hacking attempt detected.', MEDIA_HYGIENE)));
        }

        /* default */
        $flg = 0;
        $message = __('Something is wrong', MEDIA_HYGIENE);

        if (isset($_POST['action']) && $_POST['action'] == 'wmh_aap_action') {
            $updated = update_option('wmh_send_data_to_server_permission', 'off');
        }

        if ($updated) {
            $flg = 1;
            $message = __('Anonymous Analytics Permission is updated', MEDIA_HYGIENE);
        }

        $output = [
            'flg' => $flg,
            'message' => $message
        ];
        echo json_encode($output);
        wp_die();
    }

    public function fn_wmh_aap_close_notice_permanently_action()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'media_hygiene_nonce')) {
            wp_die(esc_html(__('Security check failed. Hacking attempt detected.', MEDIA_HYGIENE)));
        }

        if (isset($_POST['action']) && $_POST['action'] == 'wmh_aap_close_notice_permanently_action') {
            update_option('wmh_close_analytics_permission_permanently', 'Yes');
        }
        wp_die();
    }

    public function fn_wmh_database_update_wmh_by_version()
    {

        if (!current_user_can('manage_options')) {
            return false;
        }

        /* default */
        $flg = 0;
        //$msg = __('Something is wrong to update database', MEDIA_HYGIENE);

        /* check nonce here. */
        $wp_nonce = sanitize_text_field($_POST['nonce']);
        if (!wp_verify_nonce($wp_nonce, 'database_update_wmh_by_version_nonce')) {
            die(esc_html(__('Security check. Hacking not allowed', MEDIA_HYGIENE)));
        }

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $wmh_database_version = get_option('wmh_database_version');
        $wmh_database_version_2 = get_option('wmh_database_version_2');

        if (($wmh_database_version == '' || $wmh_database_version === false)) {

            /* First Database Change */
            /* wmh_whitelist_media_post_id */
            $wmh_whitelist_media_post_id = MH_PREFIX . 'whitelist_media_post_id';
            /* wp_wmh_whitelist_media_post_id */
            $wp_wmh_whitelist_media_post_id = $this->conn->prefix . MH_PREFIX . 'whitelist_media_post_id';
            /* check wmh_whitelist_media_post_id table exits or not */
            $old_query = $this->conn->prepare('SHOW TABLES LIKE %s', $this->conn->esc_like($wmh_whitelist_media_post_id));
            /* check wp_wmh_whitelist_media_post_id table exits or not */
            $new_query = $this->conn->prepare('SHOW TABLES LIKE %s', $this->conn->esc_like($wp_wmh_whitelist_media_post_id));
            /* rename */
            if ($this->conn->get_var($old_query) == $wmh_whitelist_media_post_id) {
                if (!$this->conn->get_var($new_query) == $wp_wmh_whitelist_media_post_id) {
                    $this->conn->query("RENAME TABLE " . $wmh_whitelist_media_post_id . " TO " . $wp_wmh_whitelist_media_post_id);
                }
            }

            /* wmh_unused_media_post_id */
            $wmh_unused_media_post_id = MH_PREFIX . 'unused_media_post_id';
            /* wp_wmh_unused_media_post_id */
            $wp_wmh_unused_media_post_id = $this->conn->prefix . MH_PREFIX . 'unused_media_post_id';
            /* check wmh_unused_media_post_id table exits or not */
            $old_query = $this->conn->prepare('SHOW TABLES LIKE %s', $this->conn->esc_like($wmh_unused_media_post_id));
            /* check wp_wmh_unused_media_post_id table exits or not */
            $new_query = $this->conn->prepare('SHOW TABLES LIKE %s', $this->conn->esc_like($wp_wmh_unused_media_post_id));
            /* rename */
            if ($this->conn->get_var($old_query) == $wmh_unused_media_post_id) {
                if (!$this->conn->get_var($new_query) == $wp_wmh_whitelist_media_post_id) {
                    $this->conn->query("RENAME TABLE " . $wmh_unused_media_post_id . " TO " . $wp_wmh_unused_media_post_id);
                }
            }

            /* wmh_error_log */
            $wmh_error_log = MH_PREFIX . 'error_log';
            /* wp_wmh_error_log */
            $wp_wmh_error_log = $this->conn->prefix . MH_PREFIX . 'error_log';
            /* check wmh_error_log table exits or not */
            $old_query = $this->conn->prepare('SHOW TABLES LIKE %s', $this->conn->esc_like($wmh_error_log));
            /* check wp_wmh_error_log  exits or not */
            $new_query = $this->conn->prepare('SHOW TABLES LIKE %s', $this->conn->esc_like($wp_wmh_error_log));
            /* rename */
            if ($this->conn->get_var($old_query) == $wmh_error_log) {
                if (!$this->conn->get_var($new_query) == $wp_wmh_error_log) {
                    $this->conn->query("RENAME TABLE " . $wmh_error_log . " TO " . $wp_wmh_error_log);
                }
            }

            /* Second Database Change */
            $wmh_unused_media_post_id = $this->conn->prefix . 'wmh_unused_media_post_id';
            $wmh_whitelist_media_post_id = $this->conn->prefix . 'wmh_whitelist_media_post_id';

            /* wmh_temp*/
            $wmh_temp = $this->conn->prefix . 'wmh_temp';
            /* wmh_used_media_post_id */
            $wmh_used_media_post_id = $this->conn->prefix . 'wmh_used_media_post_id';
            /* wmh_deleted_media  */
            $wmh_deleted_media = $this->conn->prefix . 'wmh_deleted_media';


            /* wmh_unused_media_post_id */
            $wmh_unused_media_post_id_new = $this->conn->prefix . MH_PREFIX . 'unused_media_post_id';
            $query_1_1 = $this->conn->prepare(
                "SELECT COUNT(*) as fields FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'size'",
                $this->conn->dbname,
                $wmh_unused_media_post_id_new
            );
            $wmh_unused_media_post_id_new_result = $this->conn->get_var($query_1_1);
            if ($wmh_unused_media_post_id_new_result == 0) {
                $version_1_0_4_1 = "ALTER TABLE " . $wmh_unused_media_post_id . " ADD COLUMN size VARCHAR(255) NULL COMMENT 'file size'";
                $this->conn->query($version_1_0_4_1);
            }

            /* wmh_whitelist_media_post_id */
            $wmh_whitelist_media_post_id_new = $this->conn->prefix . MH_PREFIX . 'whitelist_media_post_id';
            $query_2_2 = $this->conn->prepare(
                "SELECT COUNT(*) as fields FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'size'",
                $this->conn->dbname,
                $wmh_whitelist_media_post_id_new
            );
            $wmh_whitelist_media_post_id_new_result = $this->conn->get_var($query_2_2);
            if ($wmh_whitelist_media_post_id_new_result == 0) {
                $version_1_0_4_2 = "ALTER TABLE " . $wmh_whitelist_media_post_id . " ADD COLUMN size VARCHAR(255) NULL COMMENT 'file size'";
                $this->conn->query($version_1_0_4_2);
            }

            /* create wmh_temp */
            $wmh_temp_sql = "CREATE TABLE IF NOT EXISTS " . $this->wmh_temp . "(
            `id` int NOT NULL AUTO_INCREMENT,
            `post_id` int NOT NULL,
            `attachment_cat` varchar(255) NOT NULL,
            `post_date` datetime NOT NULL,
            `size` varchar(255) NOT NULL,
            `date_created` datetime NOT NULL,
            `date_updated` datetime NOT NULL,
            PRIMARY KEY (`id`));";
            dbDelta($wmh_temp_sql);

            /* create wmh_used_media_post_id */
            $wmh_used_media_post_id_sql = "CREATE TABLE IF NOT EXISTS " . $this->wmh_used_media_post_id . "(
            `id` int NOT NULL AUTO_INCREMENT,
            `post_id` int NOT NULL,
            `attachment_cat` varchar(255) NOT NULL,
            `post_date` datetime NOT NULL,
            `size` varchar(255) NOT NULL,
            `date_created` datetime NOT NULL,
            `date_updated` datetime NOT NULL,
            PRIMARY KEY (`id`));";
            dbDelta($wmh_used_media_post_id_sql);

            /* wmh_deleted_media */
            $wmh_deleted_media_sql = "CREATE TABLE IF NOT EXISTS " . $this->wmh_deleted_media . "(
            `id` int NOT NULL AUTO_INCREMENT,
            `post_id` varchar(1000) NOT NULL,
            `url` varchar(1000) NOT NULL,
            `date_created` datetime NOT NULL,
            `date_updated` datetime NOT NULL,
            PRIMARY KEY (`id`));";
            dbDelta($wmh_deleted_media_sql);

            $flg = 1;
        }


        if (($wmh_database_version_2 == '' || $wmh_database_version_2 === false)) {

            /* wmh_unused_media_post_id */
            $wmh_unused_media_post_id_new = $this->conn->prefix . MH_PREFIX . 'unused_media_post_id';
            $query_1 = $this->conn->prepare(
                "SELECT COUNT(*) as fields FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'ext'",
                $this->conn->dbname,
                $wmh_unused_media_post_id_new
            );
            $wmh_unused_media_post_id_new_result = $this->conn->get_var($query_1);
            if ($wmh_unused_media_post_id_new_result == 0) {
                $version_2_0_0_1 = "ALTER TABLE " . $wmh_unused_media_post_id_new . " ADD COLUMN ext VARCHAR(255) NOT NULL AFTER attachment_cat";
                $this->conn->query($version_2_0_0_1);
            }

            /* wmh_whitelist_media_post_id */
            $wmh_whitelist_media_post_id_new = $this->conn->prefix . MH_PREFIX . 'whitelist_media_post_id';
            $query_2 = $this->conn->prepare(
                "SELECT COUNT(*) as fields FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'ext'",
                $this->conn->dbname,
                $wmh_whitelist_media_post_id_new
            );
            $wmh_whitelist_media_post_id_new_result = $this->conn->get_var($query_2);
            if ($wmh_whitelist_media_post_id_new_result == 0) {
                $version_2_0_0_2 = "ALTER TABLE " . $wmh_whitelist_media_post_id_new . " ADD COLUMN ext VARCHAR(255) NOT NULL AFTER attachment_cat";
                $this->conn->query($version_2_0_0_2);
            }

            /* wmh_temp */
            $wmh_temp_new = $this->conn->prefix . MH_PREFIX . 'temp';
            $query_3 = $this->conn->prepare(
                "SELECT COUNT(*) as fields FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'ext'",
                $this->conn->dbname,
                $wmh_temp_new
            );
            $wmh_temp_new_result = $this->conn->get_var($query_3);
            if ($wmh_temp_new_result == 0) {
                $version_2_0_0_3 = "ALTER TABLE " . $wmh_temp_new . " ADD COLUMN ext VARCHAR(255) NOT NULL AFTER attachment_cat";
                $this->conn->query($version_2_0_0_3);
            }

            /* wmh_used_media_post_id */
            $wmh_used_media_post_id_new = $this->conn->prefix . MH_PREFIX . 'used_media_post_id';
            $query_4 = $this->conn->prepare(
                "SELECT COUNT(*) as fields FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'ext'",
                $this->conn->dbname,
                $wmh_used_media_post_id_new
            );
            $wmh_used_media_post_id_new_result = $this->conn->get_var($query_4);
            if ($wmh_used_media_post_id_new_result == 0) {
                $version_2_0_0_4 = "ALTER TABLE " . $wmh_used_media_post_id_new . " ADD COLUMN ext VARCHAR(255) NOT NULL AFTER attachment_cat";
                $this->conn->query($version_2_0_0_4);
            }

            $flg = 1;
        }

        /* get scan option data. */
        $wmh_scan_option_data = get_option('wmh_scan_option_data', true);

        if (!isset($wmh_scan_option_data['media_per_page_input'])) {
            $wmh_scan_option_data['media_per_page_input'] = 10;
        }

        /* timeframse option value update */
        if (!isset($wmh_scan_option_data['wmh_timeframes'])) {
            $wmh_scan_option_data['wmh_timeframes'] = "quarterly";
        }

        update_option('wmh_scan_option_data', $wmh_scan_option_data);

        /* delete option */
        update_option('wmh_plugin_db_version_upgrade', '1', 'no');
        $output = array(
            'flg' => $flg
        );
        echo json_encode($output);
        wp_die();
    }
}

$wmh_dashboard = new wmh_dashboard();
