<?php

global $wpdb;
$wmh_unused_media_post_id = $wpdb->prefix . MH_PREFIX . 'unused_media_post_id';
$filter_data = '';
if (isset($_GET['page'])) {
    /* attachment. */
    if (isset($_GET['attachment_cat'])) {
        if ($_GET['attachment_cat'] == 'images') {
            $filter_data = sanitize_text_field($_GET['attachment_cat']);
        } else  if ($_GET['attachment_cat'] == 'video') {
            $filter_data = sanitize_text_field($_GET['attachment_cat']);
        } else  if ($_GET['attachment_cat'] == 'audio') {
            $filter_data = sanitize_text_field($_GET['attachment_cat']);
        } else  if ($_GET['attachment_cat'] == 'documents') {
            $filter_data = sanitize_text_field($_GET['attachment_cat']);
        } else  if ($_GET['attachment_cat'] == 'others') {
            $filter_data = sanitize_text_field($_GET['attachment_cat']);
        }
    } else {
        $filter_data = '';
    }
    /* date. */
    $filter_date = '';
    if (isset($_GET['date']) && $_GET['date'] != '') {
        $filter_date = sanitize_text_field($_GET['date']);
    }
    /* get paged number */
    if (isset($_GET['paged'])) {
        $paged = sanitize_text_field($_GET['paged']);
    } else {
        $paged = 1;
    }
}

/* get and check there is unused media available or not. */
$unused_media_data = array();
$table_exists_view = $this->conn->get_var("SHOW TABLES LIKE '$wmh_unused_media_post_id'") == $wmh_unused_media_post_id;
if ($table_exists_view) {
    $unused_media_sql = 'SELECT count(post_id) as count_post_id FROM ' . $wmh_unused_media_post_id . ' ';
    $unused_media_data = $wpdb->get_row($unused_media_sql, ARRAY_A);
}

/* get last scan date and time. */
function fn_wmh_get_last_scan_date_and_time()
{

    $wmh_end_time = get_option('wmh_end_time');
    $time = '';
    if (isset($wmh_end_time) && $wmh_end_time != '') {
        $time = fn_wmh_time_elapsed_string($wmh_end_time, true);
    }
    return $time;
}

/* converting timestamp to time ago. */
/* converting timestamp to time ago. */
function fn_wmh_time_elapsed_string($datetime, $full = true)
{
    $now = new DateTime(date('Y-m-d h:i:s'));
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diffInWeeks = floor($diff->days / 7);
    $diffInDays = $diff->days - ($diffInWeeks * 7);

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        $count = (isset($diff->$k) ? $diff->$k : 0);
        if ($count) {
            $v = $count . ' ' . $v . ($count > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if ($diffInWeeks) {
        $string['w'] = $diffInWeeks . ' week' . ($diffInWeeks > 1 ? 's' : '');
    }

    if ($diffInDays) {
        $string['d'] = $diffInDays . ' day' . ($diffInDays > 1 ? 's' : '');
    }

    if (!$full) {
        $string = array_slice($string, 0, 1);
    }

    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

$wmh_end_time = fn_wmh_get_last_scan_date_and_time();

/* get scan status complete or interrupted */
$wmh_scan_complete = get_option('wmh_scan_complete');
$com_int_status = '';
if (isset($wmh_scan_complete)) {
    if ($wmh_scan_complete == 'completed') {
        $com_int_status = __('(scan completed)', MEDIA_HYGIENE);
    } else if ($wmh_scan_complete == 'interrupted') {
        $com_int_status = __('(scan interrupted)', MEDIA_HYGIENE);
    }
}

$wmh_plugin_db_version_upgrade = get_option('wmh_plugin_db_version_upgrade');

?>

<div class="wpm-height">
    <div class="wmh-container mb-4">
        <div class="notice notice-scan is-dismissible mt-0 mb-3" style="display:none">
            <p></p>
        </div>
        <div class="wmh-wrap">
            <!-- Scan warning-->
            <div class="notice notice-warning scan-nt-warn is-dismissible mb-3 mt-0" style="display:none">
                <p><b><?php _e("WARNING: ", MEDIA_HYGIENE); ?></b><?php _e("Do not close the window until the scan completes. You may continue to work in a different browser or browser tab.", MEDIA_HYGIENE); ?></p>
            </div>
            <div class="wmh-btn-time">
                <!-- Scan button-->
                <div class="wmh-button-form">
                    <form id="wmh-scan-form">
                        <input type="hidden" name="action" value="scan_unused_images">
                        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('scan_unused_images_nonce')); ?>">
                        <input type="hidden" name="ajax_call" id="ajax_call" value="1" />
                        <input type="hidden" name="progress_bar" id="progress_bar" value="0" />
                        <?php if (isset($wmh_plugin_db_version_upgrade) && $wmh_plugin_db_version_upgrade == '1') { ?>
                            <button type="submit" id="scan-button" class="button button-primary wmh-btn">
                                <i class="fa-solid fa-spinner fa-spin scan-loader" style="display:none;"></i>&nbsp;<?php _e('Scan', MEDIA_HYGIENE); ?>
                            </button>
                        <?php } else { ?>
                            <button type="submit" class="button button-primary wmh-btn update-database-msg-btn">
                                <i class="fa-solid fa-spinner fa-spin scan-loader" style="display:none;"></i>&nbsp;<?php _e('Scan', MEDIA_HYGIENE); ?>
                            </button>
                        <?php } ?>
                        <span class="tooltip-1"><i class="fa-solid fa-circle-question"></i>
                            <span class="right">
                                <p>
                                    <?php _e('Two types of scans: Regular (Free) and Deep (Pro version)', MEDIA_HYGIENE); ?>
                                </p>
                                <a href='https://www.mediahygiene.com/pricing/' target='_blank'><?php _e('Upgrade to Pro'); ?></a>
                                <i></i>
                            </span>
                        </span>
                    </form>
                </div>
                <?php if (isset($unused_media_data) && isset($unused_media_data['count_post_id']) && $unused_media_data['count_post_id'] != '0' && $unused_media_data['count_post_id'] != '') { ?>
                    <!-- Download page media -->
                    <div class="wmh-download-page-media">
                        <form id="create-page-unused-media-zip-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="create_page_unused_media_zip_action">
                            <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('create_page_unused_media_zip_nonce')); ?>">
                            <input type="hidden" name="paged" value="<?php echo esc_attr($paged); ?>" />
                            <?php if (isset($wmh_plugin_db_version_upgrade) && $wmh_plugin_db_version_upgrade == '1') { ?>
                                <button type="submit" id="create-page-zip-button" class="button button-primary wmh-btn">
                                    <?php _e('Download Media', MEDIA_HYGIENE); ?>
                                </button>
                            <?php } else { ?>
                                <button type="submit" class="button button-primary wmh-btn update-database-msg-btn">
                                    <?php _e('Download Media', MEDIA_HYGIENE); ?>
                                </button>
                            <?php } ?>
                            <span class="tooltip-1"><i class="fa-solid fa-circle-question"></i>
                                <span class="right">
                                    <p>
                                        <?php _e('Download media by page only (Free). Get the ONE SHOT download feature (Pro version)', MEDIA_HYGIENE); ?>
                                    </p>
                                    <a href='https://www.mediahygiene.com/pricing/' target='_blank'><?php _e('Upgrade to Pro'); ?></a>
                                    <i></i>
                                </span>
                            </span>
                        </form>
                    </div>
                    <!-- trash current page media -->
                    <div class="wmh-trash-page-media">
                        <form id="wmh-trash-page-media-form">
                            <input type="hidden" name="action" value="trash_page_media">
                            <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('trash_page_media_nonce')); ?>">
                            <input type="hidden" name="paged" value="<?php echo esc_attr($paged); ?>" />
                            <?php if (isset($wmh_plugin_db_version_upgrade) && $wmh_plugin_db_version_upgrade == '1') { ?>
                                <button type="submit" id="trash-page-media-button" class="button button-danger wmh-btn">
                                    <i class="fa-solid fa-spinner fa-spin trash-page-media-loader" style="display:none;"></i>&nbsp;<?php _e('Trash Current Page Media', MEDIA_HYGIENE); ?>
                                </button>
                            <?php } else { ?>
                                <button type="submit" class="button button-danger wmh-btn update-database-msg-btn">
                                    <i class="fa-solid fa-spinner fa-spin trash-page-media-loader" style="display:none;"></i>&nbsp;<?php _e('Trash Current Page Media', MEDIA_HYGIENE); ?>
                                </button>
                            <?php } ?>
                            <span class="tooltip-1"><i class="fa-solid fa-circle-question"></i>
                                <span class="right">
                                    <p>
                                        <?php _e('Delete media by page only (Free). Get the ONE SHOT delete feature (Pro version)', MEDIA_HYGIENE); ?>
                                    </p>
                                    <a href='https://www.mediahygiene.com/pricing/' target='_blank'><?php _e('Upgrade to Pro'); ?></a>
                                    <i></i>
                                </span>
                            </span>
                        </form>
                    </div>
                <?php } ?>
                <div class="last-scan-time">
                    <?php if (isset($wmh_end_time) && $wmh_end_time != '') { ?>
                        <p>
                            <?php _e('Last scan:', MEDIA_HYGIENE); ?>
                            <strong>
                                <?php
                                echo esc_html($wmh_end_time . '&nbsp;' . $com_int_status);
                                ?>
                            </strong>
                        </p>
                    <?php } ?>
                </div>
            </div>
            <div class="progress" style="display:none">
                <div class="bar progress-bar progress-bar-striped progress-bar-animated" role="progressbar">
                    <p class="percent"></p>
                </div>
            </div>
            <div class="scan-data-status" style="display: none;">
                <span class="scan-data-title"></span>
                <span class="scan-data-steps"><b></b></span>
            </div>
        </div>
    </div>