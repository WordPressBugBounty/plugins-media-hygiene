<?php

/* header view */
$wmh_general = new wmh_general();
$wmh_general->fn_wmh_get_template('wmh-header-view.php');

global $wpdb;
$wmh_error_log = $wpdb->prefix.MH_PREFIX . 'error_log';
$error_log_sql = 'SELECT * FROM ' . $wmh_error_log . '';;
$error_log_result = $wpdb->get_results($error_log_sql, ARRAY_A);

/* get scan option data. */
$display_in_list = false;
$wmh_scan_option_data = get_option('wmh_scan_option_data', true);
if (isset($wmh_scan_option_data['error_log'])) {
    if ($wmh_scan_option_data['error_log'] == 'on') {
        $display_in_list = true;
    }
}

?>

<div class="card p-0 rounded-0">
    <h5 class="card-header p-3"><?php _e('Error Log', MEDIA_HYGIENE); ?></h5>
    <div class="card-body col-md-12 col-lg-12 col-xl-12 p-md-4">
        <div class="wrap">
            <div class="mt-2 mb-3">
                <?php if ($display_in_list == true) {
                    if (isset($error_log_result) && !empty($error_log_result)) { ?>
                        <form id="clear-error-log-form">
                            <input type="hidden" name="action" value="clear_error_log_action">
                            <input type="hidden" name="nonce" value="<?php echo esc_html(wp_create_nonce('clear_error_log_nonce')); ?>">
                            <button class="button button-primary wmh-btn" id="clear-error-log"><i class="fa-solid fa-spinner fa-spin error-log-btn-loader" style="display:none;"></i>&nbsp;<?php _e('Clear Error Log'); ?></button>
                        </form>
                <?php }
                } ?>
            </div>
            <div class="row">
                <table class="table cell-border compact stripe d-table" id="error-log-list">
                    <thead>
                        <th><?php _e('No', MEDIA_HYGIENE); ?></th>
                        <th><?php _e('Module', MEDIA_HYGIENE); ?></th>
                        <th><?php _e('Error', MEDIA_HYGIENE); ?></th>
                        <th><?php _e('Date Created', MEDIA_HYGIENE); ?></th>
                    </thead>
                    <tbody>
                        <?php if ($display_in_list == true) {
                            if (isset($error_log_result) && !empty($error_log_result)) {
                                foreach ($error_log_result as $log) {
                                    $log_date = date("F j, Y, g:i a", strtotime($log['date_created'])); ?>
                                    <tr>
                                        <td><?php echo esc_html($log['id']); ?></td>
                                        <td><?php echo esc_html($log['module']); ?></td>
                                        <td><?php echo esc_html($log['error']); ?></td>
                                        <td><?php echo esc_html($log_date); ?></td>
                                    </tr>
                        <?php }
                            }
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>