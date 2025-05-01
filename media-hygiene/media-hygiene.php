<?php

/*
Plugin Name: Media Hygiene
Plugin URI: https://mediahygiene.com/
Description: A plugin to remove unused media from WordPress and free up space on hosting.
Version: 4.0.1
Author: Media Hygiene
Author URI: https://mediahygiene.com
License: Custom license, no Distribution allowed
*/

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class media_hygiene
{

    public function __construct()
    {
        /* define */
        define('MEDIA_HYGIENE', 'media-hygiene');
        define('MH_FILE_VERSION', '4.0.1');
        define('MH_PREFIX', 'wmh_');
        define('MH_FILE_PATH', plugin_dir_path(__FILE__));
        define('MH_FILE_URL', plugin_dir_url(__FILE__));
        define('MH_BASE_NAME', 'media-hygiene/media-hygiene.php');

        /* on activate plugin */
        /* add_action('activated_plugin', array($this, 'fn_wmh_active_plugin')); */
        /* update plugin */
        add_action('init', array($this, 'fn_wmh_update_plugin_for_free'));
        /* admin footer, feedback popup on deactive */
        add_action('admin_footer', array($this, 'fn_wmh_deactivation_plugin_feedback_popup'));
        /* enqueue script and style for jquery ui dialog */
        add_action('admin_enqueue_scripts', array($this, 'fn_wmh_enqueue_scripts'));
        /* init function */
        $this->fn_wmh_init();
    }

    public function fn_wmh_active_plugin($plugin)
    {
        /* if media hygiene is active then it will take to the home Page */
        if ($plugin == plugin_basename(__FILE__)) {
            /* redirect page */
            $redirect_page = 'admin.php?page=wmh-media-hygiene';
            exit(wp_redirect(admin_url($redirect_page)));
        }
    }

    public function fn_wmh_update_plugin_for_free()
    {
        if (get_option('wmh_plugin_db_version') === false) {
            update_option('wmh_plugin_db_version', '2.0.0');
        }
    }

    public function fn_wmh_init()
    {

        /* include */
        include_once('includes/wmh-general.php');
        include_once('includes/wmh-scan.php');
        include_once('includes/wmh-settings.php');
        include_once('includes/wmh-dashboard.php');
        include_once('includes/wmh-download-unused-media.php');
        include_once('includes/wmh-error-log.php');
        include_once('includes/wmh-deleted-media.php');
        include_once('includes/wmh-plugin-feedback.php');
        include_once('includes/wmh-my-cron-job.php');

        /* class */
        include_once('class/admin/wmh-elementor.php');
        include_once('class/admin/wmh-divi.php');
        include_once('class/admin/wmh-bricks.php');
        include_once('class/admin/wmh-enfold.php');
        include_once('class/admin/wmh-vc.php');
        include_once('class/admin/wmh-ocean-wp.php');

        /* register activation hook for create custom table in database */
        register_activation_hook(__FILE__,  array($wmh_general, 'fn_wmh_create_table'));
    }

    public function fn_wmh_deactivation_plugin_feedback_popup()
    {
        $wmh_general = new wmh_general();
        $wmh_general->fn_wmh_get_template('wmh-plugin-feedback-view.php');
    }

    public function fn_wmh_enqueue_scripts()
    {
        /* register */
        wp_register_style('wmh-custom-feedback-css', MH_FILE_URL . '/assets/css/wmh-custom-feedback.css', false, MH_FILE_VERSION, 'all');
        wp_register_script('wmh-custom-feedback-js', MH_FILE_URL . '/assets/js/wmh-custom-feedback.js', array('jquery'), MH_FILE_VERSION, true);

        /* enqueue */
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_style('wmh-custom-feedback-css');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('wmh-custom-feedback-js');

        /* localize script for wmh-custom-feedback-js. */
        wp_localize_script(
            'wmh-custom-feedback-js',
            'wmhFeedbackObj',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wmh_customer_feedback'),
            )
        );
    }
}

$media_hygiene = new media_hygiene();
