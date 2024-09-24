<?php

if (file_exists(MH_FILE_PATH . '/templates/admin/wmh-header-view.php')) {
    $wmh_general= new wmh_general();
    $wmh_general->fn_wmh_get_template('wmh-header-view.php');
}

global $wpdb;
$deleted_media_result = [];
$wmh_deleted_media = $wpdb->prefix . MH_PREFIX . 'deleted_media';
$deleted_media_sql = 'SELECT * FROM ' . $wmh_deleted_media . '';
$deleted_media_result = $wpdb->get_results($deleted_media_sql, ARRAY_A);

?>

<div class="wpm-height">
    <div class="card p-0 rounded-0">
        <h5 class="card-header p-3"><?php _e('Deleted Media', MEDIA_HYGIENE); ?></h5>
        <div class="card-body col-md-12 col-lg-12 col-xl-12 p-md-4">
            <div class="wrap">
                <?php if (!empty($deleted_media_result)) { ?>
                    <div class="mt-2 mb-3">
                        <form id="deleted-media-list-form">
                            <input type="hidden" name="action" value="deleted_media_list_action">
                            <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('deleted_media_list_nonce')); ?>">
                            <button class="button button-primary wmh-btn" id="deleted-list-btn"><i class="fa-solid fa-spinner fa-spin deleted-list-btn-loader" style="display:none;"></i>&nbsp;<?php _e('Clear list'); ?></button>
                        </form>
                    </div>
                <?php } ?>
                <div class="row">
                    <table class="table cell-border compact stripe d-table" id="wmh-deleted-media-list">
                        <thead>
                            <th><?php _e('No', MEDIA_HYGIENE); ?></th>
                            <th><?php _e('Post Id', MEDIA_HYGIENE); ?></th>
                            <th><?php _e('Url', MEDIA_HYGIENE); ?></th>
                            <th><?php _e('Date Created', MEDIA_HYGIENE); ?></th>
                            <th><?php _e('Date Updated', MEDIA_HYGIENE); ?></th>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>