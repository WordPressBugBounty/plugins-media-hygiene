<?php
$trash_media_count = wp_count_attachments()->trash;
?>

<?php if ($trash_media_count) { ?>
    <div class="wmh-container-bulk-restore-delete mt-2">
        <!-- button -->
        <div class="wmh-bulk-btn">
            <!-- Restore -->
            <form id="wmh-bulk-restore-form">
                <input type="hidden" name="action" value="wmh_bulk_restore" />
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('wmh_bulk_restore')); ?>">
                <input type="hidden" id="wmh-bulk-restore-ajax-call" name="ajax_call" value="0" />
                <button type="button" class="btn btn-sm btn-success" id="wmh-bulk-restore-btn">
                    <i class="fa-solid fa-spinner fa-spin bulk-restore-loader" style="display:none;"></i>
                    <?php _e('Bulk Restore', MEDIA_HYGIENE); ?>
                </button>
            </form>
            <!-- Delete Permanently -->
            <form id="wmh-delete-permanently-form">
                <input type="hidden" name="action" value="wmh_delete_permanently" />
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('wmh_delete_permanently')); ?>">
                <input type="hidden" id="wmh-delete-permanently-ajax-call" name="ajax_call" value="0" />
                <button type="button" class="btn btn-sm btn-danger" id="wmh-delete-permanently-btn">
                    <i class="fa-solid fa-spinner fa-spin bulk-delete-permanently-loader" style="display:none;"></i>
                    <?php _e('Bulk Delete Permanently', MEDIA_HYGIENE); ?>
                </button>
            </form>
        </div>
        <!-- Progress bar -->
        <div class="wmh-bulk-progress-bar">
            <!-- For Restore -->
            <div class="progress wmh-bulk-restore-progress-bar h-75 mb-0" style="display: none;">
                <div class="progress-bar progress-bar-striped bg-success h-75" role="progressbar"></div>
            </div>
            <span class="wmh-bulk-restore-message"></span>
            <!-- For Delete Permanently -->
            <div class="progress wmh-delete-permanently-progress-bar h-75 mb-0" style="display: none;">
                <div class="progress-bar progress-bar-striped bg-danger h-75" role="progressbar"></div>
            </div>
            <span class="wmh-bulk-delete-permanently-message"></span>
        </div>
    </div>
<?php } ?>