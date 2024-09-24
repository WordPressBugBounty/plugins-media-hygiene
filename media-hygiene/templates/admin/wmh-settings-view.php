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

    /* exclude file extenion */
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
    <div class="notice notice-settings is-dismissible mt-3" style="display:none">
        <p></p>
    </div>
    <div class="card col-md-12 rounded-0 border-top-0 p-0">
        <div class="wmh_settings_container">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link active rounded-0" id="image-scan-tab" data-bs-toggle="tab" data-bs-target="#image-scan" role="tab" aria-controls="image-scan" aria-selected="true"><?php _e('Scan', MEDIA_HYGIENE); ?></button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link rounded-0" id="addons-tab" data-bs-toggle="tab" data-bs-target="#addons" role="tab" aria-controls="addons" aria-selected="false"><?php _e('Supported Tools', MEDIA_HYGIENE); ?></button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link rounded-0" id="status-tab" data-bs-toggle="tab" data-bs-target="#status" role="tab" aria-controls="status" aria-selected="true"><?php _e('System', MEDIA_HYGIENE); ?></button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="image-scan" role="tabpanel" aria-labelledby="image-scan-tab">
                    <div class="main-area" id="image-scan-area">

                        <form id="wmh-save-scan-option-form">

                            <div class="wmh-settings row">
                                <!-- Exclude file according file extension -->
                                <div class="col-xl-6 col-sm-6 mb-3">
                                    <div class="card wmh-scanner-setting-card rounded-0">
                                        <div class="wmh-scanner-setting-title-switch-2">
                                            <div class="wmh-settings-title">
                                                <h6><?php _e('File Exclusion', MEDIA_HYGIENE); ?></h6>
                                                <span class="wmh-tooltip-info tooltip-1"><i class="fa-solid fa-circle-question"></i>
                                                    <span class="right">
                                                        <p>
                                                            <?php _e('Enter file extensions (e.g. png, jpg, etc.) to be excluded from the unused media dashboard.', MEDIA_HYGIENE); ?>
                                                        </p>
                                                        <i></i>
                                                    </span>
                                                </span>
                                            </div>

                                        </div>
                                        <div class="wmh-scanner-setting-info">
                                            <div class="row">
                                                <div class="col-xl-6 col-md-12 col-sm-12">
                                                    <input type="text" class="form-control ex-file-ex" id="ex-file-ex" name="ex-file-ex" value="<?php echo esc_attr($ex_file_ex); ?>">
                                                </div>
                                                <div class="col-xl-6 col-md-12 col-sm-12">
                                                    <a href='' id='restore-default-file-exe'>
                                                        <?php _e('Restore Default File Extenstion'); ?>
                                                        <i class="fa-solid fa-spinner fa-spin rdfe-loader" style="display:none;"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <!-- How many media want to show per page -->
                                <div class="col-xl-6 col-sm-6 mb-3">
                                    <div class="card wmh-scanner-setting-card rounded-0">
                                        <div class="wmh-scanner-setting-title-switch-2">
                                            <div class="wmh-settings-title">
                                                <h6><?php _e('Media items displayed per page', MEDIA_HYGIENE); ?></h6>
                                                <span class="wmh-tooltip-info tooltip-1"><i class="fa-solid fa-circle-question"></i>
                                                    <span class="right">
                                                        <p>
                                                            <?php _e('This number shows the upper limit of unused media files that can be displayed. The greater the number allows for larger number of media files to be deleted using the checkbox. Note: Higher number can also adversely impact the performance of your site and server. Change this number with caution.', MEDIA_HYGIENE); ?>
                                                        </p>
                                                        <i></i>
                                                    </span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="wmh-scanner-setting-info">
                                            <input type="number" class="form-control media-per-page-input" id="media-per-page-input" name="media-per-page-input" value="<?php echo esc_attr($media_per_page_input); ?>" onkeypress='return restrictAlphabets(event)'>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reminder for Notifications On Newly Uploaded Attachments Based on Timeframes -->
                                <div class="col-xl-6 col-sm-6 mb-3">
                                    <div class="card wmh-scanner-setting-card rounded-0">
                                        <div class="wmh-scanner-setting-title-switch-2">
                                            <div class="wmh-settings-title">
                                                <h6><?php _e('Reminder for Notifications On Newly Uploaded Attachments Based on Timeframes', MEDIA_HYGIENE); ?></h6>
                                                <span class="wmh-tooltip-info tooltip-1"><i class="fa-solid fa-circle-question"></i>
                                                    <span class="right">
                                                        <p>
                                                            <?php _e('This feature facilitates the automated dispatch of email notifications pertaining to recently uploaded attachments, aligned with specified timeframes', MEDIA_HYGIENE); ?>
                                                        </p>
                                                        <i></i>
                                                    </span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="wmh-scanner-setting-info">
                                            <select class="form-control" id="wmh-timeframes" name="wmh-timeframes">
                                                <option value="" <?php if ($wmh_timeframes == '') {
                                                                        echo "selected";
                                                                    } ?>><?php _e("--- Select Timeframes ---", MEDIA_HYGIENE); ?></option>
                                                <option value="none" <?php if ($wmh_timeframes == 'none') {
                                                                            echo "selected";
                                                                        } ?>><?php _e("None", MEDIA_HYGIENE); ?></option>
                                                <option value="daily" <?php if ($wmh_timeframes == 'daily') {
                                                                            echo "selected";
                                                                        } ?>><?php _e("Daily", MEDIA_HYGIENE); ?></option>
                                                <option value="weekly" <?php if ($wmh_timeframes == 'weekly') {
                                                                            echo "selected";
                                                                        } ?>><?php _e("Weekly", MEDIA_HYGIENE); ?></option>
                                                <option value="biweekly" <?php if ($wmh_timeframes == 'biweekly') {
                                                                                echo "selected";
                                                                            } ?>><?php _e("Bi Weekly", MEDIA_HYGIENE); ?></option>
                                                <option value="monthly" <?php if ($wmh_timeframes == 'monthly') {
                                                                            echo "selected";
                                                                        } ?>><?php _e("Monthly", MEDIA_HYGIENE); ?></option>
                                                <option value="quarterly" <?php if ($wmh_timeframes == 'quarterly') {
                                                                                echo "selected";
                                                                            } ?>><?php _e("Quarterly", MEDIA_HYGIENE); ?></option>
                                            </select>

                                            <div class="wmh-email-notification" style="margin-top:10px;">
                                                <h6><?php _e('Send Notifiction to (multiple emails with comma separated values):', MEDIA_HYGIENE); ?></h6>
                                                <input type="text" id="wmh-email-notification-input" name="wmh-email-notification-input" class="form-control" value="<?php echo esc_attr($email_notification_send_to); ?>" placeholder="Leave blank for admin email" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--  Delete data on unintall plugin && Menu postion-->
                                <div class="col-xl-3 col-sm-6 mb-3">
                                    <!-- Delete data on uninstall plugin -->
                                    <div class="card wmh-scanner-setting-card rounded-0">
                                        <div class="wmh-scanner-setting-title-switch">
                                            <div class="wmh-settings-title">
                                                <h6><?php _e('Delete Data On Uninstall Plugin', MEDIA_HYGIENE); ?></h6>
                                            </div>
                                            <div class="wmh-switch">
                                                <input type="checkbox" class="delete-data-on-uninstall-switch" id="delete-data-on-uninstall-switch" name="delete-data-on-uninstall-switch" value="<?php echo esc_attr($delete_data_on_uninstall_plugin); ?>" <?php echo esc_attr($delete_data_on_uninstall_plugin_checked); ?>>
                                                <label class="delete-data-on-uninstall-switch-label" for="delete-data-on-uninstall-switch"></label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Menu Position-->
                                    <div class="card wmh-scanner-setting-card rounded-0">
                                        <div class="wmh-scanner-setting-title-switch-2">
                                            <div class="wmh-settings-title">
                                                <h6><?php _e('Menu Position', MEDIA_HYGIENE); ?></h6>
                                                <span class="wmh-tooltip-info tooltip-1"><i class="fa-solid fa-circle-question"></i>
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
                                        <div class="wmh-scanner-setting-info">
                                            <input type="number" class="form-control menu-position-input" id="menu-position-input" name="menu-position-input" value="<?php echo esc_attr($menu_position_input); ?>" onkeypress='return restrictAlphabets(event)'>
                                        </div>
                                    </div>

                                </div>

                                <!-- Error log -->
                                <div class="col-xl-3 col-sm-6 mb-3">
                                    <div class="card wmh-scanner-setting-card rounded-0">
                                        <div class="wmh-scanner-setting-title-switch">
                                            <div class="wmh-settings-title">
                                                <h6><?php _e('Error Log', MEDIA_HYGIENE); ?></h6>
                                                <span class="wmh-tooltip-info tooltip-1"><i class="fa-solid fa-circle-question"></i>
                                                    <span class="right">
                                                        <p>
                                                            <?php _e('Please refer FAQ. <a href="https://mediahygiene.com/faq/#error-log" target="_blank">FAQ</a>', MEDIA_HYGIENE); ?>
                                                        </p>
                                                        <i></i>
                                                    </span>
                                                </span>
                                            </div>

                                            <!-- <div class="wmh-switch">
                                                <input type="checkbox" class="error-log-switch" id="error-log-switch" name="error-log-switch" value="<?php /* echo esc_attr($error_log); ?>" <?php echo esc_attr($error_log_checked); */ ?>>
                                                <label class="error-log-switch-label" for="error-log-switch"></label>
                                            </div> -->

                                        </div>
                                    </div>
                                </div>


                            </div>
                            <div class="wmh-heading-savebutton mt-2">
                                <div class="wmh-save-settings-button">
                                    <input type="hidden" name="action" value="save_scan_settings_call" />
                                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('save_scan_settings_nonce')); ?>">
                                    <button type="submit" id="save-scan-option-button" class="button button-primary save-scan-option-button wmh-btn"><i class="fa-solid fa-spinner fa-spin save-settings-loader" style="display:none;"></i>&nbsp;<?php _e('Save Settings', MEDIA_HYGIENE); ?></button>
                                </div>
                            </div>

                        </form>

                        <!--  Anonymous Analytics Permission -->
                        <div class="card p-0 rounded-0">
                            <h5 class="card-header p-3"><?php _e('Anonymous Analytics Permission', MEDIA_HYGIENE); ?></h5>
                            <div class="card-body col-md-12 col-lg-12 col-xl-12 p-md-4">
                                <div class="mb-3 wmh-switch d-flex">
                                    <input type="checkbox" class="mh-analytics-switch mr-2" id="mh-analytics-switch" name="mh-analytics-switch" <?php if (isset($permission_for_send_data) && $permission_for_send_data == 'on') {
                                                                                                                                                    echo esc_attr('checked');
                                                                                                                                                } else {
                                                                                                                                                    echo '';
                                                                                                                                                } ?>>
                                    <label class="mh-analytics-switch-label mt-1" for="mh-analytics-switch"></label>
                                    <div class="wmh-on">
                                        <b class=""><?php _e('Media Hygiene Analytics', MEDIA_HYGIENE); ?>&nbsp;&nbsp;<i class="fa-solid fa-spinner fa-spin analytics-loader" style="display:none;"></i></b>
                                        <p class='m-0'><?php _e('I agree to share anonymous data with the Media Hygiene to help improve the software. I only share number of files, type of files and file sizes. No filenames or identifying information is sent.', MEDIA_HYGIENE); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="tab-pane fade" id="addons" role="tabpanel" aria-labelledby="addons-tab">
                    <div class="main-area" id="addons-area">

                        <div class="container">
                            <div class="pt-3 m-auto">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Plugins + Theme + Page Builders', MEDIA_HYGIENE); ?></th>
                                            <th><?php _e('Free', MEDIA_HYGIENE); ?></th>
                                            <th><?php _e('Pro', MEDIA_HYGIENE); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><?php _e('Woocommerce', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Advanced Custom Fields (ACF)', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('PODS', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Custom Field Suite', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('All In One Seo', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Yoast Seo', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('SEO Press', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Slider Revolution', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Meta Slider', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Smart Slider', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Elementor (Do not include templates)', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Divi', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Avada', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('WP Bakery', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Beaver Builder', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Bricks', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Visual Composer', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Flatsome', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Enfold', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Ocean WP', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Custom Post Type', MEDIA_HYGIENE); ?></td>
                                            <td><i class="fa-solid fa-xmark" style="color: #b20a0a;"></i></td>
                                            <td><i class="fa-solid fa-check" style="color: #028000;"></i></td>
                                        </tr>
                                    </tbody>

                                </table>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="tab-pane fade" id="status" role="tabpanel" aria-labelledby="status-tab">
                    <div class="main-area" id="status-area">
                        <div class="wmh-sr-btn mt-3">
                            <a href="<?php echo esc_url($redirect_url); ?>" class="btn btn-primary wmh-button ml-lg-2 ml-md-2 align-items-center rounded-0" target="_blank">
                                <?php _e('System Report', MEDIA_HYGIENE); ?>
                            </a>

                        </div>
                        <div class="wmh-sr-btn mt-1">
                            <small><?php _e('Button will redirect you to default WordPress Site Health Page. Click on <b>"Copy site info to clipboard"</b> to copy system report.', MEDIA_HYGIENE); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>