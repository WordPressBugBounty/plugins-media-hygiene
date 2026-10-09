<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

global $wpdb;
$wp_posts = $wpdb->prefix . 'posts';

/* get end time when scan media. */
$wmh_end_time = get_option('wmh_end_time');

$wmh_new_media_count = 0;
if (isset($wmh_end_time) && $wmh_end_time != '') {
    /* COUNT only: the previous version pulled every attachment row with
     * SELECT * just to test whether any existed. */
    $wmh_new_media_count = (int) $wpdb->get_var(
        $wpdb->prepare(
            'SELECT COUNT(ID) FROM ' . $wp_posts . ' WHERE post_date > %s AND post_type = %s AND post_status != %s',
            $wmh_end_time,
            'attachment',
            'trash'
        )
    );
}

?>

<!-- new media added information -->
<?php if ($wmh_new_media_count > 0) { ?>
    <div class="notice notice-warning is-dismissible mb-0">
        <p>
            <b><?php _e('New files since your last scan.', MEDIA_HYGIENE); ?></b>
            <?php
            printf(
                /* translators: 1: number of new files, 2: date of the last scan */
                esc_html(_n(
                    '%1$s file has been uploaded since your last scan on %2$s. Re-scan to find any that are unused.',
                    '%1$s files have been uploaded since your last scan on %2$s. Re-scan to find any that are unused.',
                    $wmh_new_media_count,
                    MEDIA_HYGIENE
                )),
                '<strong>' . esc_html(number_format_i18n($wmh_new_media_count)) . '</strong>',
                esc_html(date_i18n(get_option('date_format'), strtotime($wmh_end_time)))
            );
            ?>
        </p>
    </div>
<?php } ?>
