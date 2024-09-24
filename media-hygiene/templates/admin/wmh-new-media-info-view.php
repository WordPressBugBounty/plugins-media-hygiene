<?php

global $wpdb;
$wp_posts = $wpdb->prefix . 'posts';

/* get end time when scan media. */
$wmh_end_time = get_option('wmh_end_time');
if (isset($wmh_end_time) && $wmh_end_time != '') {
    $post_id_sql = 'SELECT * FROM ' . $wp_posts . ' WHERE post_date > "' . $wmh_end_time . '" AND post_type = "attachment"';
    $post_id_result = $wpdb->get_results($post_id_sql, ARRAY_A);
}

?>

<!-- new media added information -->
<?php if (isset($post_id_result) && !empty($post_id_result)) { ?>
    <div class="notice notice-warning is-dismissible  mb-0">
        <p><b><?php _e('IMPORTANT: ', MEDIA_HYGIENE); ?></b><?php _e('New media file(s) have been added since last scan. Please re-scan your media library to find all unused media.', MEDIA_HYGIENE); ?></p>
    </div>
<?php } ?>