<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class wmh_plugin_feedback
{

    public function __construct()
    {
        /* feedback call or deactive */
        add_action('wp_ajax_wmh_customer_feedback', array($this, 'fn_wmh_customer_feedback'));
    }

    public function fn_wmh_customer_feedback()
    {   
        if (!current_user_can('manage_options')) {
			wp_send_json_error(null, 403);
		}
        
        /* default */
        $flg = 0;
        $msg = __('Something is wrong', MEDIA_HYGIENE);

        /* check nonce here. */
        $wp_nonce = sanitize_text_field($_POST['nonce']);
        if (!wp_verify_nonce($wp_nonce, 'wmh_customer_feedback')) {
            wp_die(esc_html__('Security check. Hacking not allowed', MEDIA_HYGIENE), '', array('response' => 403));
        }

        $process_type = '';
        if (isset($_POST['process_type']) && $_POST['process_type'] != '') {
            $process_type = sanitize_text_field($_POST['process_type']);
        }

        $feedback_text = '';
        if (isset($_POST['feedback_text']) && $_POST['feedback_text'] != '') {
            $feedback_text = sanitize_textarea_field($_POST['feedback_text']);
        }

        $cheked_val = '';
        if (isset($_POST['checked_val']) && $_POST['checked_val'] != '') {
            $cheked_val = sanitize_textarea_field($_POST['checked_val']);
        }

        /* process of deactivate plugin */
        $plugin_key = 'media-hygiene/media-hygiene.php';
        deactivate_plugins($plugin_key);

        if ($process_type != '' && $process_type == 2) {
            $body = '';
            if ($cheked_val == 1) {
                $body = __('The plugin is not working as expected', MEDIA_HYGIENE);
            } else if ($cheked_val == 2) {
                $body = __('I found a better plugin', MEDIA_HYGIENE);
            } else if ($cheked_val == 3) {
                $body = __('It is not what I was looking for', MEDIA_HYGIENE);
            } else if ($cheked_val == 4) {
                $body = __('The plugin is not working', MEDIA_HYGIENE);
            } else if ($cheked_val == 5) {
                $body = __('I could not understand how to use it', MEDIA_HYGIENE);
            } else if ($cheked_val == 6) {
                $body = __('The plugin is great, but I need a specific feature that you do not support', MEDIA_HYGIENE);
            } else if ($cheked_val == 7) {
                $body = __('It is a temporary deactivation - I am troubleshooting in the issue', MEDIA_HYGIENE);
            } else if ($cheked_val == 8 && $feedback_text != '') {
                $body = $feedback_text;
            }

            /* only include personal data when the user explicitly opted in */
            $share_contact = isset($_POST['share_contact']) && sanitize_text_field($_POST['share_contact']) === '1';

            $display_name = '';
            $user_email   = '';
            $site_url     = site_url();

            if ($share_contact) {
                $user         = wp_get_current_user();
                $user_email   = $user->data->user_email;
                $display_name = $user->data->display_name;
            }

            /* send email */
            $to      = array('support@mediahygiene.com');
            $subject = 'Media Hygiene Free Version Deactivated - ' . date('Y-m-d h:i:s');
            $message = '<div>';
            if ($share_contact) {
                $message .= '<p><b>Display Name: </b>' . esc_html($display_name) . '</p>';
                $message .= '<p><b>User email: </b>' . esc_html($user_email) . '</p>';
                $message .= '<p><b>Site url: </b>' . esc_url($site_url) . '</p>';
            }
            $message .= '<p><b>Reason: </b>' . esc_html($body) . '</p>';
            $message .= '</div>';
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($to, $subject, $message, $headers);
        }

        $flg = 1;
        $msg = __('Media Hygiene is deactivated.', MEDIA_HYGIENE);
        $output = array(
            'flg' => $flg,
            'msg' => $msg
        );
        echo json_encode($output);
        wp_die();
    }
}

$wmh_plugin_feedback = new wmh_plugin_feedback();
