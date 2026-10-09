<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

/* Endpoint on mediahygiene.com that receives deactivation payloads. */
if (!defined('WMH_FEEDBACK_ENDPOINT')) {
    define('WMH_FEEDBACK_ENDPOINT', 'https://mediahygiene.com/wp-json/wmh/v1/deactivation');
}

class wmh_plugin_feedback
{
    public function __construct()
    {
        add_action('wp_ajax_wmh_customer_feedback', array($this, 'fn_wmh_customer_feedback'));
    }

    public function fn_wmh_customer_feedback()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(null, 403);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'wmh_customer_feedback')) {
            wp_die(esc_html__('Security check. Hacking not allowed', MEDIA_HYGIENE), '', array('response' => 403));
        }

        $process_type  = isset($_POST['process_type'])  ? (int) $_POST['process_type']                    : 0;
        $checked_val   = isset($_POST['checked_val'])   ? (int) $_POST['checked_val']                     : 0;
        $feedback_text = isset($_POST['feedback_text']) ? sanitize_textarea_field($_POST['feedback_text']) : '';
        $share_contact = isset($_POST['share_contact']) && sanitize_text_field($_POST['share_contact']) === '1';

        /* deactivate regardless of whether feedback was submitted */
        deactivate_plugins('media-hygiene/media-hygiene.php');

        /* Send when the user pressed Deactivate and gave us SOMETHING:
         * a reason, a comment, or both. Skip & Deactivate (process type 1)
         * still sends nothing at all.
         *
         * The reason used to be required. Observed on a real deactivation:
         * someone typed a comment and ticked the consent box but picked no
         * radio, and all of it was discarded. A written comment is the most
         * useful thing on this form, so refusing it unless it is also
         * categorised throws away the best feedback we get. */
        $wmh_has_reason  = ($checked_val >= 1 && $checked_val <= 8);
        $wmh_has_comment = ($feedback_text !== '');

        if ($process_type === 2 && ($wmh_has_reason || $wmh_has_comment)) {

            $reason_map = array(
                1 => 'The plugin is not working as expected',
                2 => 'I found a better plugin',
                3 => 'It is not what I was looking for',
                4 => 'The plugin is not working',
                5 => 'I could not understand how to use it',
                6 => 'The plugin is great, but I need a specific feature that you do not support',
                7 => 'It is a temporary deactivation - I am troubleshooting in the issue',
                8 => 'Other',
            );

            /* Keep the picked reason intact. It used to be replaced by the
             * user's free text on "Other", which is why the reason totals on
             * the server were polluted with one-off strings and junk input
             * instead of grouping under Other. The comment now travels in its
             * own field, for every reason, not just Other. */
            /* No reason picked is a real state now, so do not index the map
             * with a 0. Say plainly that the reason was not given rather
             * than inventing one or sending an empty string, so the
             * dashboard can tell 'did not say' apart from a real answer. */
            $reason_text = $wmh_has_reason
                ? $reason_map[$checked_val]
                : 'No reason selected';

            /* Tier 1, non-PII technical context, always sent */
            $payload = array(
                'plugin'      => 'free',
                'plugin_ver'  => MH_FILE_VERSION,
                'wp_ver'      => get_bloginfo('version'),
                'php_ver'     => PHP_VERSION,
                'reason_id'   => $checked_val,
                'reason_text' => $reason_text,
                'comment'     => $feedback_text,
                'scan_done'   => (int) (bool) get_option('wmh_scan_status'),
            );

            /* Tier 2, PII, only with explicit consent */
            if ($share_contact) {
                $user = wp_get_current_user();
                $payload['user_name']  = sanitize_text_field($user->data->display_name);
                $payload['user_email'] = sanitize_email($user->data->user_email);
                $payload['site_url']   = esc_url_raw(site_url());
            }

            /*
             * Non-blocking: fire-and-forget so the deactivation response
             * returns to the user immediately without waiting on mediahygiene.com.
             */
            wp_remote_post(WMH_FEEDBACK_ENDPOINT, array(
                'timeout'     => 8,
                'blocking'    => false,
                'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
                'body'        => wp_json_encode($payload),
                'data_format' => 'body',
            ));
        }

        echo wp_json_encode(array(
            'flg' => 1,
            'msg' => __('Media Hygiene is deactivated.', MEDIA_HYGIENE),
        ));
        wp_die();
    }
}

$wmh_plugin_feedback = new wmh_plugin_feedback();
