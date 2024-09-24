<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class wmh_settings
{

    public $conn;

    public function __construct()
    {

        global $wpdb;
        $this->conn = $wpdb;
        /* save wmh-settings data in wp option. */
        add_action('wp_ajax_save_scan_settings_call', array($this, 'fn_wmh_save_scan_settings_call'));
        /* store permission to send scan data and store scan data on server */
        add_action('wp_ajax_send_data_to_server_action', array($this, 'fn_wmh_send_data_to_server_action'));
    }

    public function fn_wmh_save_scan_settings_call()
    {

        if (!current_user_can('manage_options')) {
            return false;
        }

        /* Check nonce here. */
        $wp_nonce = sanitize_text_field($_POST['nonce']);
        if (!wp_verify_nonce($wp_nonce, 'save_scan_settings_nonce')) {
            die(esc_html(__('Security check. Hacking not allowed', MEDIA_HYGIENE)));
        }

        /* Delete data on uninstall plugin. */
        $delete_data_on_uninstall_switch = 'off';
        if (isset($_POST['delete-data-on-uninstall-switch']) && $_POST['delete-data-on-uninstall-switch'] == 'on') {
            $delete_data_on_uninstall_switch = sanitize_text_field($_POST['delete-data-on-uninstall-switch']);
        }

        /* Error log. */
        $error_log_switch = 'off';
        if (isset($_POST['error-log-switch']) && $_POST['error-log-switch'] == 'on') {
            $error_log_switch = sanitize_text_field($_POST['error-log-switch']);
        }

        /* media want to show per page */
        $media_per_page_input = '10';
        if (isset($_POST['media-per-page-input']) && $_POST['media-per-page-input'] != '') {
            $media_per_page_input = sanitize_text_field($_POST['media-per-page-input']);
        }

        /* menu position input */
        $menu_position_input = "";
        if (isset($_POST['menu-position-input']) && $_POST['menu-position-input'] != '') {
            $menu_position_input = sanitize_text_field($_POST['menu-position-input']);
        }
        /* Exclude file extension */
        $ex_file_ex = '';
        if (isset($_POST['ex-file-ex']) && $_POST['ex-file-ex'] != '') {
            $ex_file_ex = sanitize_text_field($_POST['ex-file-ex']);
        }

        /* timeframes */
        $wmh_timeframes = '';
        if (isset($_POST['wmh-timeframes']) && $_POST['wmh-timeframes'] != '') {
            $wmh_timeframes = sanitize_text_field($_POST['wmh-timeframes']);
        }

        /* email notification send to */
        $wmh_email_notification = '';
        if (isset($_POST['wmh-email-notification-input']) && $_POST['wmh-email-notification-input'] != '') {
            $wmh_email_notification = sanitize_text_field($_POST['wmh-email-notification-input']);
        }

        $wmh_scan_option_data = array(
            'delete_data_on_uninstall_plugin' =>  $delete_data_on_uninstall_switch,
            'error_log' => $error_log_switch,
            'media_per_page_input' => $media_per_page_input,
            'ex_file_ex' => $ex_file_ex,
            'wmh_timeframes' => $wmh_timeframes,
            'number_of_image_scan' => '30',
            'email_notification_send_to' => $wmh_email_notification,
            'menu_position_input' => $menu_position_input
        );

        if (isset($wmh_scan_option_data) && !empty($wmh_scan_option_data)) {
            $scan_option_data_saved = update_option('wmh_scan_option_data',  $wmh_scan_option_data, 'no');
            if ($scan_option_data_saved) {
                $flg = 1;
                $message = esc_html(__('Settings saved.', MEDIA_HYGIENE));
                $output = array(
                    'flg' => $flg,
                    'message' => $message
                );
            } else {
                $flg = 1;
                $message = esc_html(__('Setting already saved.', MEDIA_HYGIENE));
                $output = array(
                    'flg' => $flg,
                    'message' => $message
                );
            }
            echo json_encode($output);
        }

        wp_die();
    }

    public function fn_wmh_send_data_to_server_action()
    {
        $output = array();
        if (isset($_POST['action']) && $_POST['action'] == 'send_data_to_server_action') {

            if (isset($_POST['switch_status']) && $_POST['switch_status'] == 'on') {
                $status = sanitize_text_field($_POST['switch_status']);
            } else if (isset($_POST['switch_status']) && $_POST['switch_status'] == 'off') {
                $status = sanitize_text_field($_POST['switch_status']);
            }

            if ($status == 'on') {
                /* saved permission in wp_option */
                $updated = update_option('wmh_send_data_to_server_permission', $status, 'no');
            } else {
                /* saved permission in wp_option */
                $updated = update_option('wmh_send_data_to_server_permission', $status, 'no');
            }
            if ($updated) {
                $flg = 1;
                $message = esc_html(__('Permission saved.', MEDIA_HYGIENE));
                $output = array(
                    'flg' => $flg,
                    'message' => $message
                );
            } else {
                $flg = 0;
                $message = esc_html(__('Permission already saved.', MEDIA_HYGIENE));
                $output = array(
                    'flg' => $flg,
                    'message' => $message
                );
            }
        }
        echo json_encode($output);
        wp_die();
    }
}

$wmh_settings = new wmh_settings();
