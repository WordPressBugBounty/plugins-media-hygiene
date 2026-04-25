<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

global $wpdb;

/* get scan option data. */
$wmh_scan_option_data = get_option('wmh_scan_option_data', true);

if (isset($wmh_scan_option_data) && !empty($wmh_scan_option_data)) {

    /* delete data on uninstall plugin. */
    if (isset($wmh_scan_option_data['delete_data_on_uninstall_plugin']) && $wmh_scan_option_data['delete_data_on_uninstall_plugin'] == 'off') {
        $delete_data_on_uninstall_plugin = $wmh_scan_option_data['delete_data_on_uninstall_plugin'];
        $delete_data_on_uninstall_plugin_checked = '';
    } else {
        $delete_data_on_uninstall_plugin = $wmh_scan_option_data['delete_data_on_uninstall_plugin'];
        $delete_data_on_uninstall_plugin_checked = 'checked';
    }

    /* error log. */
    if (isset($wmh_scan_option_data['error_log']) && $wmh_scan_option_data['error_log'] == 'off') {
        $error_log = $wmh_scan_option_data['error_log'];
        $error_log_checked = '';
    } else {
        $error_log = $wmh_scan_option_data['error_log'];
        $error_log_checked = 'checked';
    }

    /* exclude file extension */
    if (isset($wmh_scan_option_data['ex_file_ex']) && $wmh_scan_option_data['ex_file_ex'] != '') {
        $ex_file_ex = $wmh_scan_option_data['ex_file_ex'];
    } else {
        $ex_file_ex = $wmh_scan_option_data['ex_file_ex'];
    }

    /* show media per page */
    $media_per_page_input = 10;
    if (isset($wmh_scan_option_data['media_per_page_input']) && ($wmh_scan_option_data['media_per_page_input'] != '' || $wmh_scan_option_data['media_per_page_input'] != 0)) {
        $media_per_page_input = $wmh_scan_option_data['media_per_page_input'];
    }

    /* menu position number */
    $menu_position_input = "";
    if (isset($wmh_scan_option_data['menu_position_input']) && ($wmh_scan_option_data['menu_position_input'] != '' || $wmh_scan_option_data['menu_position_input'] != 0)) {
        $menu_position_input = $wmh_scan_option_data['menu_position_input'];
    }

    /* timeframes. */
    if (isset($wmh_scan_option_data['wmh_timeframes']) && $wmh_scan_option_data['wmh_timeframes'] != '') {
        $wmh_timeframes = $wmh_scan_option_data['wmh_timeframes'];
    } else {
        $wmh_timeframes = $wmh_scan_option_data['wmh_timeframes'];
    }

    /* email notification send to */
    $email_notification_send_to = '';
    if (isset($wmh_scan_option_data['email_notification_send_to']) && $wmh_scan_option_data['email_notification_send_to'] != '') {
        $email_notification_send_to = $wmh_scan_option_data['email_notification_send_to'];
    }

    /* get all plugins list. */
    $all_plugins = get_plugins();

    /* get all theme list */
    $all_themes = wp_get_themes();
}

/* Get data about permission checkbox */
$permission_for_send_data = get_option('wmh_send_data_to_server_permission');

/* system report redirect url */
$redirect_url = admin_url() . 'site-health.php?tab=debug';

?>

<?php
$wmh_general = new wmh_general();
$wmh_general->fn_wmh_get_template('wmh-header-view.php');
?>

<div class="wpm-height">
    <div class="notice notice-settings is-dismissible mt-3" style="display:none"><p></p></div>

    <div class="card col-md-12 rounded-0 border-top-0 p-0">
        <div class="wmh_settings_container">

            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link active rounded-0" id="image-scan-tab" data-bs-toggle="tab" data-bs-target="#image-scan" role="tab" aria-controls="image-scan" aria-selected="true">
                        <i class="fa-solid fa-sliders" style="margin-right:6px;"></i><?php _e('Scan', MEDIA_HYGIENE); ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link rounded-0" id="addons-tab" data-bs-toggle="tab" data-bs-target="#addons" role="tab" aria-controls="addons" aria-selected="false">
                        <i class="fa-solid fa-puzzle-piece" style="margin-right:6px;"></i><?php _e('Supported Tools', MEDIA_HYGIENE); ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link rounded-0" id="status-tab" data-bs-toggle="tab" data-bs-target="#status" role="tab" aria-controls="status" aria-selected="false">
                        <i class="fa-solid fa-circle-info" style="margin-right:6px;"></i><?php _e('System', MEDIA_HYGIENE); ?>
                    </button>
                </li>
            </ul>

            <div class="tab-content">

                <!-- ===== SCAN TAB ===== -->
                <div class="tab-pane fade show active" id="image-scan" role="tabpanel" aria-labelledby="image-scan-tab">
                    <div class="main-area wmh-settings-tab-body">

                        <form id="wmh-save-scan-option-form">

                            <!-- Section: Media Display -->
                            <div class="wmh-settings-section">
                                <div class="wmh-settings-section-label"><?php _e('Media Display', MEDIA_HYGIENE); ?></div>
                                <div class="row">

                                    <!-- File Exclusion -->
                                    <div class="col-xl-6 col-md-6 col-sm-12 mb-3">
                                        <div class="wmh-setting-card">
                                            <div class="wmh-setting-card-header">
                                                <div class="wmh-setting-card-title">
                                                    <i class="fa-solid fa-filter-circle-xmark wmh-setting-icon"></i>
                                                    <span><?php _e('File Exclusion', MEDIA_HYGIENE); ?></span>
                                                    <span class="wmh-tooltip-info tooltip-1">
                                                        <i class="fa-solid fa-circle-question"></i>
                                                        <span class="right">
                                                            <p><?php _e('Enter file extensions (e.g. png, jpg, etc.) to be excluded from the unused media dashboard.', MEDIA_HYGIENE); ?></p>
                                                            <i></i>
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="wmh-setting-card-body">
                                                <input type="text" class="form-control ex-file-ex" id="ex-file-ex" name="ex-file-ex" value="<?php echo esc_attr($ex_file_ex); ?>" placeholder="e.g. ttf, otf, woff, css">
                                                <div class="mt-2">
                                                    <a href="" id="restore-default-file-exe" class="wmh-text-link">
                                                        <i class="fa-solid fa-rotate-left" style="margin-right:4px;"></i><?php _e('Restore Default Extensions', MEDIA_HYGIENE); ?>
                                                        <i class="fa-solid fa-spinner fa-spin rdfe-loader" style="display:none;"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Media Per Page -->
                                    <div class="col-xl-6 col-md-6 col-sm-12 mb-3">
                                        <div class="wmh-setting-card">
                                            <div class="wmh-setting-card-header">
                                                <div class="wmh-setting-card-title">
                                                    <i class="fa-solid fa-table-cells wmh-setting-icon"></i>
                                                    <span><?php _e('Items Per Page', MEDIA_HYGIENE); ?></span>
                                                    <span class="wmh-tooltip-info tooltip-1">
                                                        <i class="fa-solid fa-circle-question"></i>
                                                        <span class="right">
                                                            <p><?php _e('How many unused media files are shown per page. Higher numbers allow bulk-selecting more files at once but may slow down the page on large libraries.', MEDIA_HYGIENE); ?></p>
                                                            <i></i>
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="wmh-setting-card-body">
                                                <input type="number" class="form-control media-per-page-input" id="media-per-page-input" name="media-per-page-input" value="<?php echo esc_attr($media_per_page_input); ?>" min="1" max="200" onkeypress="return restrictAlphabets(event)">
                                                <p class="wmh-field-hint"><?php _e('Recommended: 10–50 items.', MEDIA_HYGIENE); ?></p>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Section: Email Notifications -->
                            <div class="wmh-settings-section">
                                <div class="wmh-settings-section-label"><?php _e('Email Notifications', MEDIA_HYGIENE); ?></div>
                                <div class="wmh-setting-card">
                                    <div class="wmh-setting-card-header">
                                        <div class="wmh-setting-card-title">
                                            <i class="fa-solid fa-bell wmh-setting-icon"></i>
                                            <span><?php _e('New Upload Reminder', MEDIA_HYGIENE); ?></span>
                                            <span class="wmh-tooltip-info tooltip-1">
                                                <i class="fa-solid fa-circle-question"></i>
                                                <span class="right">
                                                    <p><?php _e('Receive an email summary of newly uploaded attachments at your chosen frequency.', MEDIA_HYGIENE); ?></p>
                                                    <i></i>
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="wmh-setting-card-body">
                                        <div class="row align-items-end">
                                            <div class="col-xl-3 col-md-4 col-sm-12 mb-3 mb-md-0">
                                                <label class="wmh-field-label" for="wmh-timeframes"><?php _e('Frequency', MEDIA_HYGIENE); ?></label>
                                                <select class="form-control" id="wmh-timeframes" name="wmh-timeframes">
                                                    <option value="" <?php selected($wmh_timeframes, ''); ?>><?php _e('— Disabled —', MEDIA_HYGIENE); ?></option>
                                                    <option value="none" <?php selected($wmh_timeframes, 'none'); ?>><?php _e('None', MEDIA_HYGIENE); ?></option>
                                                    <option value="daily" <?php selected($wmh_timeframes, 'daily'); ?>><?php _e('Daily', MEDIA_HYGIENE); ?></option>
                                                    <option value="weekly" <?php selected($wmh_timeframes, 'weekly'); ?>><?php _e('Weekly', MEDIA_HYGIENE); ?></option>
                                                    <option value="biweekly" <?php selected($wmh_timeframes, 'biweekly'); ?>><?php _e('Bi-Weekly', MEDIA_HYGIENE); ?></option>
                                                    <option value="monthly" <?php selected($wmh_timeframes, 'monthly'); ?>><?php _e('Monthly', MEDIA_HYGIENE); ?></option>
                                                    <option value="quarterly" <?php selected($wmh_timeframes, 'quarterly'); ?>><?php _e('Quarterly', MEDIA_HYGIENE); ?></option>
                                                </select>
                                            </div>
                                            <div class="col-xl-9 col-md-8 col-sm-12">
                                                <label class="wmh-field-label" for="wmh-email-notification-input"><?php _e('Send to (comma-separated emails)', MEDIA_HYGIENE); ?></label>
                                                <input type="text" id="wmh-email-notification-input" name="wmh-email-notification-input" class="form-control" value="<?php echo esc_attr($email_notification_send_to); ?>" placeholder="<?php esc_attr_e('Leave blank to use the admin email', MEDIA_HYGIENE); ?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Plugin Behaviour -->
                            <div class="wmh-settings-section">
                                <div class="wmh-settings-section-label"><?php _e('Plugin Behaviour', MEDIA_HYGIENE); ?></div>
                                <div class="row">

                                    <!-- Delete on Uninstall -->
                                    <div class="col-xl-4 col-md-6 col-sm-12 mb-3">
                                        <div class="wmh-setting-card wmh-setting-card-toggle">
                                            <div class="wmh-setting-card-toggle-inner">
                                                <div class="wmh-setting-card-title">
                                                    <i class="fa-solid fa-trash-can wmh-setting-icon"></i>
                                                    <span><?php _e('Delete Data on Uninstall', MEDIA_HYGIENE); ?></span>
                                                </div>
                                                <div class="wmh-switch">
                                                    <input type="checkbox" class="delete-data-on-uninstall-switch" id="delete-data-on-uninstall-switch" name="delete-data-on-uninstall-switch" value="<?php echo esc_attr($delete_data_on_uninstall_plugin); ?>" <?php echo esc_attr($delete_data_on_uninstall_plugin_checked); ?>>
                                                    <label class="delete-data-on-uninstall-switch-label" for="delete-data-on-uninstall-switch"></label>
                                                </div>
                                            </div>
                                            <p class="wmh-toggle-hint"><?php _e('Remove all plugin data from the database when the plugin is uninstalled.', MEDIA_HYGIENE); ?></p>
                                        </div>
                                    </div>

                                    <!-- Menu Position -->
                                    <div class="col-xl-4 col-md-6 col-sm-12 mb-3">
                                        <div class="wmh-setting-card">
                                            <div class="wmh-setting-card-header">
                                                <div class="wmh-setting-card-title">
                                                    <i class="fa-solid fa-list-ol wmh-setting-icon"></i>
                                                    <span><?php _e('Menu Position', MEDIA_HYGIENE); ?></span>
                                                    <span class="wmh-tooltip-info tooltip-1">
                                                        <i class="fa-solid fa-circle-question"></i>
                                                        <span class="right">
                                                            <p><?php _e("Menu Structure", MEDIA_HYGIENE); ?></p>
                                                            <ul>
                                                                <li><?php _e("2 - Dashboard", MEDIA_HYGIENE); ?></li>
                                                                <li class="wmh-separator"><?php _e("4 – Separator", MEDIA_HYGIENE); ?></li>
                                                                <li><?php _e("5 - Posts", MEDIA_HYGIENE); ?></li>
                                                                <li><?php _e("10 - Media", MEDIA_HYGIENE); ?></li>
                                                                <li><?php _e("15 - Links", MEDIA_HYGIENE); ?></li>
                                                                <li><?php _e("20 - Pages", MEDIA_HYGIENE); ?></li>
                                                                <li><?php _e("25 - Comments", MEDIA_HYGIENE); ?></li>
                                                                <li class="wmh-separator"><?php _e("59 – Separator", MEDIA_HYGIENE); ?></li>
                                                                <li><?php _e("60 - Appearance", MEDIA_HYGIENE); ?></li>
                                                                <li><?php _e("65 - Plugins", MEDIA_HYGIENE); ?></li>
                                                                <li><?php _e("70 - Users", MEDIA_HYGIENE); ?></li>
                                                                <li><?php _e("75 - Tools", MEDIA_HYGIENE); ?></li>
                                                                <li><?php _e("80 - Settings", MEDIA_HYGIENE); ?></li>
                                                                <li class="wmh-separator"><?php _e("99 – Separator", MEDIA_HYGIENE); ?></li>
                                                            </ul>
                                                            <i></i>
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="wmh-setting-card-body">
                                                <input type="number" class="form-control menu-position-input" id="menu-position-input" name="menu-position-input" value="<?php echo esc_attr($menu_position_input); ?>" placeholder="e.g. 25" onkeypress="return restrictAlphabets(event)">
                                                <p class="wmh-field-hint"><?php _e('Leave blank for default position.', MEDIA_HYGIENE); ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Error Log -->
                                    <div class="col-xl-4 col-md-6 col-sm-12 mb-3">
                                        <div class="wmh-setting-card wmh-setting-card-info">
                                            <div class="wmh-setting-card-title">
                                                <i class="fa-solid fa-triangle-exclamation wmh-setting-icon"></i>
                                                <span><?php _e('Error Log', MEDIA_HYGIENE); ?></span>
                                            </div>
                                            <p class="wmh-toggle-hint"><?php _e('View a log of errors encountered during scans.', MEDIA_HYGIENE); ?></p>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=wmh-media-hygiene&tab=error_log')); ?>" class="wmh-text-link">
                                                <i class="fa-solid fa-arrow-right" style="margin-right:4px;"></i><?php _e('View Error Log', MEDIA_HYGIENE); ?>
                                            </a>
                                            <span class="wmh-tooltip-info tooltip-1" style="margin-left:8px;">
                                                <i class="fa-solid fa-circle-question"></i>
                                                <span class="right">
                                                    <p><?php _e('Please refer FAQ.', MEDIA_HYGIENE); ?> <a href="https://mediahygiene.com/faq/#error-log" target="_blank"><?php _e('Read FAQ', MEDIA_HYGIENE); ?></a></p>
                                                    <i></i>
                                                </span>
                                            </span>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Save button -->
                            <div class="wmh-settings-save-row">
                                <input type="hidden" name="action" value="save_scan_settings_call" />
                                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('save_scan_settings_nonce')); ?>">
                                <button type="submit" id="save-scan-option-button" class="button button-primary save-scan-option-button wmh-btn">
                                    <i class="fa-solid fa-spinner fa-spin save-settings-loader" style="display:none;"></i>
                                    <i class="fa-solid fa-floppy-disk" style="margin-right:5px;"></i><?php _e('Save Settings', MEDIA_HYGIENE); ?>
                                </button>
                            </div>

                        </form>

                        <!-- Analytics -->
                        <div class="wmh-settings-section">
                            <div class="wmh-settings-section-label"><?php _e('Privacy', MEDIA_HYGIENE); ?></div>
                            <div class="wmh-setting-card wmh-setting-card-toggle">
                                <div class="wmh-setting-card-toggle-inner">
                                    <div class="wmh-setting-card-title">
                                        <i class="fa-solid fa-shield-halved wmh-setting-icon"></i>
                                        <span><?php _e('Anonymous Usage Data', MEDIA_HYGIENE); ?></span>
                                        <i class="fa-solid fa-spinner fa-spin analytics-loader" style="display:none; margin-left:8px;"></i>
                                    </div>
                                    <div class="wmh-switch">
                                        <input type="checkbox" class="mh-analytics-switch" id="mh-analytics-switch" name="mh-analytics-switch"
                                            <?php if (isset($permission_for_send_data) && $permission_for_send_data == 'on') echo 'checked'; ?>>
                                        <label class="mh-analytics-switch-label" for="mh-analytics-switch"></label>
                                    </div>
                                </div>
                                <p class="wmh-toggle-hint"><?php _e('Share anonymous data (number of files, file types, file sizes) to help improve Media Hygiene. No filenames or identifying information is ever sent.', MEDIA_HYGIENE); ?></p>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ===== SUPPORTED TOOLS TAB ===== -->
                <div class="tab-pane fade" id="addons" role="tabpanel" aria-labelledby="addons-tab">
                    <div class="main-area wmh-settings-tab-body">

                        <!-- Upgrade banner -->
                        <div class="wmh-pro-banner">
                            <div class="wmh-pro-banner-text">
                                <strong><?php _e('Unlock full compatibility with Pro', MEDIA_HYGIENE); ?></strong>
                                <span><?php _e('Get support for WooCommerce, ACF, Divi, Avada, and 15+ more tools.', MEDIA_HYGIENE); ?></span>
                            </div>
                            <a href="https://mediahygiene.com/pricing/" target="_blank" class="wmh-pro-banner-btn"><?php _e('Upgrade to Pro', MEDIA_HYGIENE); ?> &rarr;</a>
                        </div>

                        <div class="wmh-table-scroll">
                        <table class="widefat wmh-compat-table">
                            <thead>
                                <tr>
                                    <th class="wmh-compat-col-tool"><?php _e('Plugin / Theme / Page Builder', MEDIA_HYGIENE); ?></th>
                                    <th class="wmh-compat-col-tier"><?php _e('Free', MEDIA_HYGIENE); ?></th>
                                    <th class="wmh-compat-col-tier wmh-compat-col-pro"><?php _e('Pro', MEDIA_HYGIENE); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $tools = array(
                                    array('Elementor',                       true,  true),
                                    array('WooCommerce',                     false, true),
                                    array('Advanced Custom Fields (ACF)',    false, true),
                                    array('PODS',                            false, true),
                                    array('Custom Field Suite',              false, true),
                                    array('All In One SEO',                  false, true),
                                    array('Yoast SEO',                       false, true),
                                    array('SEO Press',                       false, true),
                                    array('Slider Revolution',               false, true),
                                    array('Meta Slider',                     false, true),
                                    array('Smart Slider',                    false, true),
                                    array('Divi',                            false, true),
                                    array('Avada',                           false, true),
                                    array('WP Bakery',                       false, true),
                                    array('Beaver Builder',                  false, true),
                                    array('Bricks',                         false, true),
                                    array('Visual Composer',                 false, true),
                                    array('Flatsome',                        false, true),
                                    array('Enfold',                          false, true),
                                    array('Ocean WP',                        false, true),
                                    array('Custom Post Type',                false, true),
                                );
                                foreach ($tools as $i => $tool) :
                                    $row_class = ($i % 2 === 0) ? '' : 'alternate';
                                ?>
                                <tr class="<?php echo esc_attr($row_class); ?>">
                                    <td><?php echo esc_html(__($tool[0], MEDIA_HYGIENE)); ?></td>
                                    <td>
                                        <?php if ($tool[1]) : ?>
                                            <span class="wmh-compat-yes"><i class="fa-solid fa-check"></i></span>
                                        <?php else : ?>
                                            <span class="wmh-compat-no"><i class="fa-solid fa-xmark"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="wmh-compat-col-pro">
                                        <span class="wmh-compat-yes"><i class="fa-solid fa-check"></i></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div><!-- .wmh-table-scroll -->

                    </div>
                </div>

                <!-- ===== SYSTEM TAB ===== -->
                <div class="tab-pane fade" id="status" role="tabpanel" aria-labelledby="status-tab">
                    <div class="main-area wmh-settings-tab-body">

                        <div class="row">

                            <!-- System info cards -->
                            <div class="col-xl-8 col-md-12">
                                <div class="wmh-settings-section-label"><?php _e('Environment', MEDIA_HYGIENE); ?></div>
                                <div class="wmh-table-scroll">
                                <table class="widefat wmh-system-table">
                                    <tbody>
                                        <tr>
                                            <td class="wmh-system-label"><?php _e('WordPress Version', MEDIA_HYGIENE); ?></td>
                                            <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                                        </tr>
                                        <tr class="alternate">
                                            <td class="wmh-system-label"><?php _e('PHP Version', MEDIA_HYGIENE); ?></td>
                                            <td><?php echo esc_html(PHP_VERSION); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="wmh-system-label"><?php _e('PHP Memory Limit', MEDIA_HYGIENE); ?></td>
                                            <td><?php echo esc_html(ini_get('memory_limit')); ?></td>
                                        </tr>
                                        <tr class="alternate">
                                            <td class="wmh-system-label"><?php _e('PHP Max Execution Time', MEDIA_HYGIENE); ?></td>
                                            <td><?php echo esc_html(ini_get('max_execution_time')); ?>s</td>
                                        </tr>
                                        <tr>
                                            <td class="wmh-system-label"><?php _e('WordPress Memory Limit', MEDIA_HYGIENE); ?></td>
                                            <td><?php echo esc_html(WP_MEMORY_LIMIT); ?></td>
                                        </tr>
                                        <tr class="alternate">
                                            <td class="wmh-system-label"><?php _e('Active Theme', MEDIA_HYGIENE); ?></td>
                                            <td><?php $theme = wp_get_theme(); echo esc_html($theme->get('Name') . ' ' . $theme->get('Version')); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="wmh-system-label"><?php _e('Multisite', MEDIA_HYGIENE); ?></td>
                                            <td><?php echo is_multisite() ? esc_html__('Yes', MEDIA_HYGIENE) : esc_html__('No', MEDIA_HYGIENE); ?></td>
                                        </tr>
                                        <tr class="alternate">
                                            <td class="wmh-system-label"><?php _e('Media Hygiene Version', MEDIA_HYGIENE); ?></td>
                                            <td><?php echo esc_html(MH_FILE_VERSION); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                                </div><!-- .wmh-table-scroll -->
                            </div>

                            <!-- System report -->
                            <div class="col-xl-4 col-md-12 mt-xl-0 mt-3">
                                <div class="wmh-settings-section-label"><?php _e('Full Report', MEDIA_HYGIENE); ?></div>
                                <div class="wmh-setting-card wmh-setting-card-info">
                                    <div class="wmh-setting-card-title" style="margin-bottom:10px;">
                                        <i class="fa-solid fa-file-lines wmh-setting-icon"></i>
                                        <span><?php _e('WordPress Site Health', MEDIA_HYGIENE); ?></span>
                                    </div>
                                    <p class="wmh-toggle-hint"><?php _e('Open the WordPress Site Health page, then click "Copy site info to clipboard" to get a full system report.', MEDIA_HYGIENE); ?></p>
                                    <a href="<?php echo esc_url($redirect_url); ?>" class="button button-primary wmh-btn" target="_blank">
                                        <i class="fa-solid fa-arrow-up-right-from-square" style="margin-right:5px;"></i><?php _e('System Report', MEDIA_HYGIENE); ?>
                                    </a>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

            </div><!-- .tab-content -->
        </div><!-- .wmh_settings_container -->
    </div>
</div>
