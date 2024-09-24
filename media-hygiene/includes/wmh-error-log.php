<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class wmh_error_log
{

    public $conn;
    public $wmh_error_log;

    public function __construct()
    {

        global $wpdb;
        $this->conn = $wpdb;
        $this->wmh_error_log = $this->conn->prefix . MH_PREFIX . 'error_log';
        /* clear error log action */
        add_action('wp_ajax_clear_error_log_action', array($this, 'fn_wmh_clear_error_log_action'));
    }

    public function fn_wmh_clear_error_log_action()
    {
        if (!current_user_can('manage_options')) {
			return false;
		}
        
        /* check nonce here. */
        $wp_nonce = sanitize_text_field($_POST['nonce']);
        if (!wp_verify_nonce($wp_nonce, 'clear_error_log_nonce')) {
            die(esc_html(__('Security check. Hacking not allowed', MEDIA_HYGIENE)));
        }
        $clear_error_log_sql = ' TRUNCATE TABLE ' . $this->wmh_error_log . ' ';
        $cleared = $this->conn->query($clear_error_log_sql);
        if ($cleared) {
            $flg = 1;
            $message = esc_html(__('Error log cleared', MEDIA_HYGIENE));
        } else {
            $flg = 1;
            $message = esc_html(__('Something is wrong to clear error log', MEDIA_HYGIENE));
        }
        $output = array(
            'flg' => $flg,
            'message' => $message,
        );
        echo json_encode($output);
        wp_die();
    }
}

$wmh_error_log = new wmh_error_log();
