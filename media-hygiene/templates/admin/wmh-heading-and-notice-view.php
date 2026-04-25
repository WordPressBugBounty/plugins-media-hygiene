<?php

/* get scan status */
$scan_status = get_option('wmh_scan_status');
$wmh_scan_status_new = get_option('wmh_scan_status_new');

/* get all plugin list */
$all_plugins = get_plugins();
$all_plugins_keys = array_keys($all_plugins);

/* pro plugin */
$pro_plugins = array(
    'elementor/elementor.php',/* Okay */
    'elementor-pro/elementor-pro.php',/* Okay */
    'smart-slider-3/smart-slider-3.php',/* Okay */
    'smart-slider-3-pro/smart-slider-3.php',
    'ml-slider/ml-slider.php',/* Okay */
    'ml-slider-pro/ml-slider.php',
    'revslider/revslider.php',/* Okay */
    'revslider-pro/revslider.php',
    'woocommerce/woocommerce.php',/* Okay */
    'advanced-custom-fields/acf.php',/* Okay */
    'advanced-custom-fields-pro/acf.php',/* Okay */
    'pods/init.php',/* Okay */
    'pods-pro/init.php',
    'visualcomposer/plugin-wordpress.php',/* Okay */
    'visualcomposer-pro/plugin-wordpress.php',
    'custom-field-suite/cfs.php',/* Okay */
    'custom-field-suite-pro/cfs.php',
    'wp-seopress/seopress.php',/*Okay */
    'wp-seopress-pro/seopress-pro.php',/*Okay */
    'wordpress-seo/wp-seo.php',/* Okay Yoast */
    'wordpress-seo-premium/wp-seo-premium.php',/* Yoast Premium */
    'all-in-one-seo-pack/all_in_one_seo_pack.php',/* Okay */
    'all-in-one-seo-pack-pro/all_in_one_seo_pack_pro.php',
);

$pro_plugin_installed = false;
$pro_plugin_list_html = '<ul class="wmh-compatibility-notice">';
if (isset($all_plugins_keys) && !empty($all_plugins_keys)) {
    foreach ($all_plugins_keys as $plugin_key) {
        if (in_array($plugin_key, $pro_plugins)) {
            $pro_plugin_installed = true;
            /* ----------- new ---------------  */
            if ($plugin_key == 'elementor/elementor.php') {
                $pro_plugin_list_html .= __('<li>Elementor</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'elementor-pro/elementor-pro.php') {
                $pro_plugin_list_html .= __('<li>Elementor Pro</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'smart-slider-3/smart-slider-3.php') {
                $pro_plugin_list_html .= __('<li>Smart Slider</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'smart-slider-3-pro/smart-slider-3.php') {
                $pro_plugin_list_html .= __('<li>Smart Slider Pro</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'ml-slider/ml-slider.php') {
                $pro_plugin_list_html .= __('<li>Meta Slider</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'ml-slider-pro/ml-slider.php') {
                $pro_plugin_list_html .= __('<li>Meta Slider Pro</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'revslider/revslider.php') {
                $pro_plugin_list_html .= __('<li>Slider Revolution</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'revslider-pro/revslider.php') {
                $pro_plugin_list_html .= __('<li>Slider Revolution Pro</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'woocommerce/woocommerce.php') {
                $pro_plugin_list_html .= __('<li>Woocommerce</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'advanced-custom-fields/acf.php') {
                $pro_plugin_list_html .= __('<li>ACF(Advanced Custom Field)</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'advanced-custom-fields-pro/acf.php') {
                $pro_plugin_list_html .= __('<li>ACF(Advanced Custom Field Pro)</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'pods/init.php') {
                $pro_plugin_list_html .= __('<li>PODS</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'pods-pro/init.php') {
                $pro_plugin_list_html .= __('<li>PODS Pro</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'visualcomposer/plugin-wordpress.php') {
                $pro_plugin_list_html .= __('<li>Visual Composer</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'visualcomposer-pro/plugin-wordpress.php') {
                $pro_plugin_list_html .= __('<li>Visual Composer Pro</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'custom-field-suite/cfs.php') {
                $pro_plugin_list_html .= __('<li>Custom Field Suite</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'custom-field-suite-pro/cfs.php') {
                $pro_plugin_list_html .= __('<li>Custom Field Suite Pro</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'wp-seopress/seopress.php') {
                $pro_plugin_list_html .= __('<li>SEO Press</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'wp-seopress-pro/seopress-pro.php') {
                $pro_plugin_list_html .= __('<li>SEO Press Pro</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'wordpress-seo/wp-seo.php') {
                $pro_plugin_list_html .= __('<li>Yoast Seo</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'wordpress-seo-premium/wp-seo-premium.php') {
                $pro_plugin_list_html .= __('<li>Yoast Seo Premium</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'all-in-one-seo-pack/all_in_one_seo_pack.php') {
                $pro_plugin_list_html .= __('<li>All In One Seo</li>', MEDIA_HYGIENE);
            } else if ($plugin_key == 'all-in-one-seo-pack-pro/all_in_one_seo_pack_pro.php') {
                $pro_plugin_list_html .= __('<li>All In One Seo Pro</li>', MEDIA_HYGIENE);
            }
        }
    }
}
$pro_plugin_list_html .= '</ul>';


/* Get data about permission checkbox */
$permission_for_send_data = get_option('wmh_send_data_to_server_permission');

/* permanently close anonymous analytics permission */
$wmh_close_analytics_permission_permanently = get_option('wmh_close_analytics_permission_permanently');

/* get wmh_database_version  */
$wmh_plugin_db_version = get_option('wmh_plugin_db_version');
$wmh_plugin_db_version_upgrade = get_option('wmh_plugin_db_version_upgrade');

?>
<div class="row row-main mt-2">
    <div class="col-md-12 px-0">

        <!-- database version alert -->
        <?php if (isset($wmh_plugin_db_version) && ($wmh_plugin_db_version == '2.0.0' || $wmh_plugin_db_version <= '2000') && $wmh_plugin_db_version_upgrade == false && $wmh_plugin_db_version_upgrade != 1) {  ?>

            <div class="notice notice-alert notice-error mb-0">
                <p>
                    <strong>
                        <?php _e('Media Hygiene database update required.', MEDIA_HYGIENE); ?>
                    </strong>
                </p>
                <p>
                    <?php _e('Media Hygiene has been updated! To keep things running smoothly, we have to update your database to the newest version. The database process will run instantly and may not take time. The database process will only check for plugin tables and will not effect any other tables. If you don\'t see scan button, please upgrade the Media Hygiene Database to enable. So please be patience.', MEDIA_HYGIENE); ?>
                </p>
                <p>
                <form id="wmh-database-update-from">
                    <input type="hidden" name="action" value="database_update_wmh_by_version">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('database_update_wmh_by_version_nonce')); ?>">
                    <button class="button button-primary wmh-update-database-btn-highlight" id="wmh-update-database">
                        <i class="fa-solid fa-spinner fa-spin wmh-update-database-loader" style="display:none;"></i>&nbsp;
                        <?php _e('Update Media Hygiene Database', MEDIA_HYGIENE); ?>
                    </button>
                </form>
                </p>
            </div>

        <?php }  ?>

        <!-- notice alert -->
        <div class="notice notice-warning wmh-notice-backup mb-0">
            <p>
                <span class="wmh-notice-icon"><i class="fa-solid fa-shield-halved"></i></span>
                <b><?php _e('Always backup before making changes.', MEDIA_HYGIENE); ?></b>
                <?php _e('Media Hygiene scans your library for unused files, but the dynamic nature of WordPress (themes, plugins, custom fields) means some files may be incorrectly flagged. Review items carefully before deleting.', MEDIA_HYGIENE); ?>
                <a href="https://wordpress.org/support/plugin/media-hygiene/reviews/#new-post" target="_blank"><?php _e('Leave a review', MEDIA_HYGIENE); ?></a>
                <?php _e('if you find it useful — it helps a lot. Upgrade to', MEDIA_HYGIENE); ?>
                <a href="https://mediahygiene.com/pricing/" target="_blank"><?php _e('Media Hygiene Pro', MEDIA_HYGIENE); ?></a>
                <?php _e('to dismiss this notice and unlock advanced features.', MEDIA_HYGIENE); ?>
            </p>
        </div>

        <!-- scan status notice -->
        <?php if (isset($scan_status) && ($scan_status == '0' || $scan_status == '')) { ?>
            <div class="notice notice-info wmh-notice-scan mb-0">
                <p>
                    <span class="wmh-notice-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <b><?php _e('No scan has been run yet.', MEDIA_HYGIENE); ?></b>
                    <?php _e('Click the <strong>Scan</strong> button to analyze your media library. Scan time depends on your server and the number of media files — please be patient and do not close the window until it completes.', MEDIA_HYGIENE); ?>
                </p>
            </div>
        <?php } ?>
        <!-- Upgrade Notice -->
        <?php if (isset($wmh_scan_status_new) && ($wmh_scan_status_new == '0' || $wmh_scan_status_new == '') && get_option('wmh_database_version') == '1000') { ?>
            <div class="notice notice-info wmh-notice-db mb-0">
                <p>
                    <span class="wmh-notice-icon"><i class="fa-solid fa-rotate"></i></span>
                    <b><?php _e('A new scan is recommended after the recent database upgrade.', MEDIA_HYGIENE); ?></b>
                    <?php _e('The plugin database has been updated. Running a fresh scan ensures everything is detected correctly. You can continue using the plugin in the meantime.', MEDIA_HYGIENE); ?>
                </p>
            </div>
        <?php } ?>
        <!-- Premium plugin and theme installed info -->
        <?php if ($pro_plugin_installed === true) { ?>
            <div class="notice notice-info wmh-notice-compat mb-0">
                <p>
                    <span class="wmh-notice-icon"><i class="fa-solid fa-puzzle-piece"></i></span>
                    <b><?php _e('Premium plugins detected.', MEDIA_HYGIENE); ?></b>
                    <?php _e('The free version may not detect unused media inside these plugins. Upgrade to', MEDIA_HYGIENE); ?>
                    <a href="https://mediahygiene.com/pricing/" target="_blank"><?php _e('Media Hygiene Pro', MEDIA_HYGIENE); ?></a>
                    <?php _e('for full compatibility:', MEDIA_HYGIENE); ?>
                </p>
                <?php echo $pro_plugin_list_html; ?>
            </div>
        <?php } ?>

        <!-- anonymous analytics opt-in / status notice -->
        <?php if ($wmh_close_analytics_permission_permanently != 'Yes') {
            if ($permission_for_send_data === 'pending' || $permission_for_send_data === false || $permission_for_send_data === '') { ?>
                <div class="notice notice-info analyzing-notice is-dismissible wmh-notice-optin mb-0">
                    <p>
                        <span class="wmh-notice-icon"><i class="fa-solid fa-lock"></i></span>
                        <b><?php _e('Help improve Media Hygiene?', MEDIA_HYGIENE); ?></b>
                        <?php _e('Allow Media Hygiene to collect anonymous data about media file types and sizes. No personal data, filenames, or identifying information is ever sent.', MEDIA_HYGIENE); ?>
                    </p>
                    <p>
                        <button id="wmh-optin-allow" class="button button-primary"><?php _e('Yes, allow', MEDIA_HYGIENE); ?></button>
                        &nbsp;
                        <button id="wmh-optin-decline" class="button"><?php _e('No thanks', MEDIA_HYGIENE); ?></button>
                        <i class="fa-solid fa-spinner fa-spin wmh-optin-loader" style="display:none; margin-left:8px;"></i>
                    </p>
                </div>
            <?php }
        } ?>
    </div>
</div>