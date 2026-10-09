<?php

/*
Plugin Name: Media Hygiene
Plugin URI: https://mediahygiene.com/
Description: A plugin to remove unused media from WordPress and free up space on hosting.
Version: 6.1.0
Requires PHP: 8.0
Author: Media Hygiene
Author URI: https://mediahygiene.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: media-hygiene
Domain Path: /languages
*/

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class media_hygiene
{

    public function __construct()
    {
        /* define */
        define('MEDIA_HYGIENE', 'media-hygiene');
        define('MH_FILE_VERSION', '6.1.0');
        define('MH_PREFIX', 'wmh_');
        define('MH_FILE_PATH', plugin_dir_path(__FILE__));
        define('MH_FILE_URL', plugin_dir_url(__FILE__));
        define('MH_BASE_NAME', 'media-hygiene/media-hygiene.php');

        /* Load translations. Without this the languages folder is never read,
         * so a .mo file sitting in it does nothing. wp.org serves translations
         * from translate.wordpress.org for the plugin slug, and since 4.6 core
         * loads those by itself, but a site shipping its own .mo in
         * wp-content/languages/plugins or in the plugin folder still needs
         * this call. Hooked on init, which is when translations are ready. */
        add_action('init', array($this, 'fn_wmh_load_textdomain'));

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

    public function fn_wmh_load_textdomain()
    {
        load_plugin_textdomain(
            MEDIA_HYGIENE,
            false,
            dirname(MH_BASE_NAME) . '/languages'
        );
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
            update_option('wmh_plugin_db_version', '2.0.0', false);
        }

        /* One-time migration: existing users who had analytics silently enabled by default
         * never gave explicit consent. Reset them to 'pending' so they see the opt-in notice.
         * Only fires until the user completes the new opt-in flow (sets wmh_analytics_consent_explicit). */
        if (!get_option('wmh_analytics_consent_explicit')) {
            if (get_option('wmh_send_data_to_server_permission') === 'on') {
                update_option('wmh_send_data_to_server_permission', 'pending', 'no');
            }
        }
    }

    public function fn_wmh_init()
    {

        /* include */
        include_once(MH_FILE_PATH . 'includes/wmh-general.php');
        include_once(MH_FILE_PATH . 'includes/wmh-scan.php');
        include_once(MH_FILE_PATH . 'includes/wmh-settings.php');
        include_once(MH_FILE_PATH . 'includes/wmh-dashboard.php');
        include_once(MH_FILE_PATH . 'includes/wmh-download-unused-media.php');
        include_once(MH_FILE_PATH . 'includes/wmh-error-log.php');
        include_once(MH_FILE_PATH . 'includes/wmh-deleted-media.php');
        include_once(MH_FILE_PATH . 'includes/wmh-plugin-feedback.php');
        include_once(MH_FILE_PATH . 'includes/wmh-my-cron-job.php');

        /* class */
        include_once(MH_FILE_PATH . 'class/admin/wmh-elementor.php');
        include_once(MH_FILE_PATH . 'class/admin/wmh-divi.php');
        include_once(MH_FILE_PATH . 'class/admin/wmh-bricks.php');
        include_once(MH_FILE_PATH . 'class/admin/wmh-enfold.php');
        include_once(MH_FILE_PATH . 'class/admin/wmh-vc.php');
        include_once(MH_FILE_PATH . 'class/admin/wmh-ocean-wp.php');

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
        /* no leading slash: plugin_dir_url() already ends in one, and
           MH_FILE_URL . '/assets/...' produced a double slash in the URL */
        wp_register_style('wmh-custom-feedback-css', MH_FILE_URL . 'assets/css/wmh-custom-feedback.css', false, wmh_asset_version('assets/css/wmh-custom-feedback.css'), 'all');
        wp_register_script('wmh-custom-feedback-js', MH_FILE_URL . 'assets/js/wmh-custom-feedback.js', array('jquery'), wmh_asset_version('assets/js/wmh-custom-feedback.js'), true);

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
                /* The one button says whether pressing it sends anything. The
                 * box above lists exactly what that is, so the label is a
                 * promise the user can already check, and clearing the form is
                 * how they opt out. */
                'btnPlain' => __('Deactivate', MEDIA_HYGIENE),
                'btnSend'  => __('Send & Deactivate', MEDIA_HYGIENE),
                /* The comment box is always shown; only the prompt changes with
                 * the reason. A specific question gets a usable answer where a
                 * generic "Tell us more..." got silence. */
                'promptDefault'        => __('Anything you can tell us helps. Optional.', MEDIA_HYGIENE),
                'promptNotAsExpected'  => __('What did you expect it to do? For example: which images it missed, or what it listed as unused that is actually in use.', MEDIA_HYGIENE),
                'promptBetterPlugin'   => __('Which one did you switch to, and what does it do better?', MEDIA_HYGIENE),
                'promptNotWorking'     => __('What happened? Any error message or the screen it got stuck on helps.', MEDIA_HYGIENE),
                'promptConfusing'      => __('Which part was unclear?', MEDIA_HYGIENE),
                'promptMissingFeature' => __('Which feature do you need?', MEDIA_HYGIENE),
                'promptOther'          => __('Please tell us why you are deactivating...', MEDIA_HYGIENE),
            )
        );
    }
}

/**
 * Cache-busting version string for one of the plugin's own asset files.
 *
 * MH_FILE_VERSION only changes on release, so an edited CSS or JS file keeps
 * serving from the browser cache at the same ?ver= until the next version
 * bump. That hides changes during development and, worse, leaves anyone who
 * loaded a pre-release build holding stale code after the real release, since
 * the URL never changed. Using the file's modification time means the URL
 * changes whenever the file does.
 *
 * Falls back to MH_FILE_VERSION if the file cannot be stat'd, e.g. when an
 * opcache or a read-only mount makes filemtime() fail.
 *
 * @param string $relative_path Path under the plugin directory, e.g. 'assets/js/wmh-admin.js'.
 * @return string Version string for wp_register_script/style.
 */
function wmh_asset_version($relative_path)
{
    $file = MH_FILE_PATH . ltrim($relative_path, '/');
    $mtime = @filemtime($file);
    return $mtime ? (string) $mtime : MH_FILE_VERSION;
}

/**
 * Build a tagged link to the marketing site.
 *
 * Every outbound link in the plugin goes through here so the domain stays
 * consistent (no www/non-www split) and each placement is measurable in
 * analytics. Without the campaign tags there is no way to tell which notice
 * or button actually drives upgrades.
 *
 * @param string $path     Path on mediahygiene.com, e.g. 'pricing' or 'faq'.
 * @param string $medium   Where the link appears, e.g. 'header' or 'settings-tab'.
 * @param string $fragment Optional #anchor, without the leading '#'.
 * @param string $campaign utm_campaign value. Defaults to 'upgrade'; pass
 *                         something else for links that are not upsells.
 * @param bool   $raw      True returns the URL unescaped. Only for callers that
 *                         are not echoing into HTML, such as an admin menu slug:
 *                         core uses that value as a raw href, and esc_url would
 *                         encode the ampersands as &#038; and break the query
 *                         string. Never pass true when echoing into markup.
 * @return string URL, escaped for an href unless $raw is true.
 */
function wmh_site_link($path = 'pricing', $medium = 'plugin', $fragment = '', $raw = false, $campaign = 'upgrade')
{
    $url = 'https://mediahygiene.com/' . trim($path, '/') . '/';
    $url = add_query_arg(
        array(
            'utm_source'   => 'free-plugin',
            'utm_medium'   => sanitize_key($medium),
            /* Not every outbound link is an upsell. Tagging a privacy
             * policy or a docs link as 'upgrade' would put non-funnel
             * traffic into the funnel numbers. */
            'utm_campaign' => sanitize_key($campaign),
        ),
        $url
    );
    if ($fragment !== '') {
        $url .= '#' . $fragment;
    }
    return $raw ? esc_url_raw($url) : esc_url($url);
}

$media_hygiene = new media_hygiene();
