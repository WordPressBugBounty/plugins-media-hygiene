<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class wmh_deleted_media
{

    public $conn;
    public $wmh_unused_media_post_id;
    public $wmh_whitelist_media_post_id;
    public $wmh_deleted_dir_files;
    public $wmh_error_log;
    public $wmh_temp;
    public $wmh_deleted_media;

    public function __construct()
    {
        global $wpdb;
        $this->conn = $wpdb;
        $this->wmh_unused_media_post_id = $this->conn->prefix . MH_PREFIX . 'unused_media_post_id';
        $this->wmh_whitelist_media_post_id = $this->conn->prefix . MH_PREFIX . 'whitelist_media_post_id';
        $this->wmh_deleted_dir_files = $this->conn->prefix . MH_PREFIX . 'deleted_dir_files';
        $this->wmh_error_log = $this->conn->prefix . MH_PREFIX . 'error_log';
        $this->wmh_temp = $this->conn->prefix . MH_PREFIX . 'temp';
        $this->wmh_deleted_media = $this->conn->prefix . MH_PREFIX . 'deleted_media';
        /* deleted media list table */
        add_action('wp_ajax_get_deleted_media_list', array($this, 'fn_wmh_get_deleted_media_list'));
        /* delete list */
        add_action('wp_ajax_deleted_media_list_action', array($this, 'fn_wmh_deleted_media_list_action'));
    }

    public function fn_wmh_get_deleted_media_list()
    {
        $data = array();
        $sql = 'SELECT * FROM ' . $this->wmh_deleted_media . ' ';
        $data = $this->conn->get_results($sql, ARRAY_A);
        echo json_encode(array('data' => $data));
        wp_die();
    }

    public function fn_wmh_deleted_media_list_action()
    {
        if (!current_user_can('manage_options')) {
			return false;
		}

        /* check nonce here. */
        $wp_nonce = sanitize_text_field($_POST['nonce']);
        if (!wp_verify_nonce($wp_nonce, 'deleted_media_list_nonce')) {
            die(esc_html(__('Security check. Hacking not allowed', MEDIA_HYGIENE)));
        }

        /* table name */
        $delete_media_list_sql = ' TRUNCATE TABLE ' . $this->wmh_deleted_media . ' ';
        $cleared = $this->conn->query($delete_media_list_sql);
        if ($cleared) {
            $flg = 1;
            $message = esc_html(__('Media list clear', MEDIA_HYGIENE));
        } else {
            $flg = 1;
            $message = esc_html(__('Something is wrong to clear media list', MEDIA_HYGIENE));
        }
        $output = array(
            'flg' => $flg,
            'message' => $message,
        );
        echo json_encode($output);
        wp_die();
    }
}

new wmh_deleted_media();
