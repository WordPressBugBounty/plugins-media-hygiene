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
        <?php if (isset($wmh_plugin_db_version) && ( $wmh_plugin_db_version == '2.0.0' || $wmh_plugin_db_version <= '2000' )&& $wmh_plugin_db_version_upgrade == false && $wmh_plugin_db_version_upgrade != 1) {  ?>

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
        <div class="notice notice-alert notice-error mb-0">
            <p>
                <b class="important-notice">
                    <?php _e('IMPORTANT NOTICE:',  MEDIA_HYGIENE); ?>
                </b>
                <?php
                _e('<b class="important-notice-text">Before using this plugin, make sure you have a proper backup of your website</b>.<br>Use Media Hygiene with caution (and always backup first). The dynamic nature of WordPress and its theme and plugin ecosystem may result in mislabelling files for deletion. If you experience an issue, please contact our support team via WordPress Repository. Our premium version provides additional features, direct access to the support team within the plugin, and disabling this warning message. We are committed to continuously improving Media Hygiene. If you enjoy it, please leave us a <a href="https://wordpress.org/support/plugin/media-hygiene/reviews/#new-post" target="_blank">review</a>. Thank you for choosing our plugin to keep your media library clean and tidy.', MEDIA_HYGIENE);
                ?>
            </p>
        </div>

        <!-- scan status notice -->
        <?php if (isset($scan_status) && ($scan_status == '0' || $scan_status == '')) { ?>
            <div class="notice notice-alert notice-error mb-0">
                <p>
                    <b class="important-notice">
                        <?php _e('IMPORTANT NOTICE:',  MEDIA_HYGIENE); ?>
                    </b>
                    <?php
                    _e('It seems that a scan has not been performed yet. Depending on the server and size of your site, it may take some time to complete a scan. Please initiate a scan and be patient.', MEDIA_HYGIENE);
                    ?>
                </p>
            </div>
        <?php } ?>
        <!-- Upgrade Notice -->
        <?php if (isset($wmh_scan_status_new) && ($wmh_scan_status_new == '0' || $wmh_scan_status_new == '') && get_option('wmh_database_version') == '1000') { ?>
            <div class="notice notice-alert notice-error mb-0">
                <p>
                    <b class="important-notice">
                        <?php _e('NOTICE:',  MEDIA_HYGIENE); ?>
                    </b>
                    <b><?php _e('We\'ve recently upgraded our plugin\'s database, and it\'s essential to perform a scan for optimal functionality. This scan will detect any potential issues, apply necessary updates, and ensure a seamless experience with our plugin. You can continue using the plugin without interruptions. If you have any questions or need assistance, feel free to reach out to our support team.', MEDIA_HYGIENE);
                        ?></b>
                </p>
            </div>
        <?php } ?>
        <!-- Premium plugin and theme installed info -->
        <?php if ($pro_plugin_installed === true) { ?>
            <div class="notice notice-info mb-0">
                <p>
                    <b class="important-notice">
                        <?php _e('ATTENTION:',  MEDIA_HYGIENE); ?>
                    </b>
                    <?php
                    _e('Media Hygiene (Free) has detected the following plugins installed:  Please upgrade to the <a href="https://mediahygiene.com/pricing/" target="_blank">pro</a> version to detect unused images in these plugins. Please backup your website and use caution when selecting media files for deletion.', MEDIA_HYGIENE);
                    ?>
                </p>
                <?php
                _e($pro_plugin_list_html, MEDIA_HYGIENE);
                ?>
            </div>
        <?php } ?>

        <!-- anonymous analytics permission notice -->
        <?php
        if ($wmh_close_analytics_permission_permanently != 'Yes') {
            if ($permission_for_send_data == 'on') {
        ?>
                <div class="notice notice-info is-dismissible analyzing-notice mb-0">
                    <p>
                        <b class="important-notice-text">
                            <?php _e('DATA PRIVACY:',  MEDIA_HYGIENE); ?>
                        </b>
                        <?php
                        _e('Media Hygiene\'s plugin optimizes your WordPress media library by analyzing the types and sizes of media files. This data is vital for enhancing your library\'s organization and performance. If you wish to adjust the data collection settings, you can do so at any time by clicking through to the settings page. We are committed to respecting your data preferences.', MEDIA_HYGIENE);
                        ?>
                        <!--
                    <button class="button" id="wmh-aap-btn">
                        <?php
                        //_e('No', MEDIA_HYGIENE);
                        ?>
                    </button> -->

                    </p>
                </div>
        <?php }
        } ?>
    </div>
</div>