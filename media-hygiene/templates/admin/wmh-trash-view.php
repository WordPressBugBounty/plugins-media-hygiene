<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

$trash_media_count = wp_count_attachments()->trash;

/* How many times they would have pressed "Trash Current Page Media" to
 * put this many files here, at their own page size. Pro does it in one
 * pass. Shown only when that number is big enough to have hurt. */
$wmh_trash_option_data = get_option('wmh_scan_option_data', true);
$wmh_trash_per_page = 10;
if (is_array($wmh_trash_option_data) && !empty($wmh_trash_option_data['media_per_page_input'])) {
    $wmh_trash_per_page = (int) $wmh_trash_option_data['media_per_page_input'];
}
if ($wmh_trash_per_page < 1) {
    $wmh_trash_per_page = 10;
}
$wmh_trash_passes = (int) ceil($trash_media_count / $wmh_trash_per_page);
?>

<?php if ($trash_media_count && $wmh_trash_passes >= 5) { ?>
    <div class="notice notice-info wmh-notice-trash-upsell mb-0" style="margin:12px 0 0;">
        <p>
            <span class="wmh-notice-icon"><i class="fa-solid fa-circle-info"></i></span>
            <b><?php
                printf(
                    /* translators: 1: number of files in trash, 2: number of passes */
                    esc_html(_n(
                        'Clearing %1$s file took %2$s pass through the unused list.',
                        'Clearing %1$s files took %2$s passes through the unused list.',
                        $wmh_trash_passes,
                        MEDIA_HYGIENE
                    )),
                    esc_html(number_format_i18n($trash_media_count)),
                    esc_html(number_format_i18n($wmh_trash_passes))
                );
            ?></b>
            <?php esc_html_e('The free version works a page at a time. Media Hygiene Pro has Trash All, which does the whole list in one go, and reads media the free scan cannot see.', MEDIA_HYGIENE); ?>
            <a href="<?php echo wmh_site_link('features', 'trash-tab-passes'); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('See what Pro adds.', MEDIA_HYGIENE); ?></a>
        </p>
    </div>
<?php } ?>

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
                    <?php _e('Restore All', MEDIA_HYGIENE); ?>
                </button>
            </form>
            <!-- Delete Permanently -->
            <form id="wmh-delete-permanently-form">
                <input type="hidden" name="action" value="wmh_delete_permanently" />
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('wmh_delete_permanently')); ?>">
                <input type="hidden" id="wmh-delete-permanently-ajax-call" name="ajax_call" value="0" />
                <button type="button" class="btn btn-sm btn-danger" id="wmh-delete-permanently-btn">
                    <i class="fa-solid fa-spinner fa-spin bulk-delete-permanently-loader" style="display:none;"></i>
                    <?php _e('Empty Trash', MEDIA_HYGIENE); ?>
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