<?php

global $wpdb;
$filter_date = '';
$list_element = '';
if (isset($_GET['page'])) {
    /* check date */
    if (isset($_GET['date']) && $_GET['date'] != '') {
        $filter_date = sanitize_text_field($_GET['date']);
    }
    /* check blacklist, whitelist, or trash */
    $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'blacklist';
    if ($type === 'blacklist') {
        $list_element = 'blacklist';
    } elseif ($type === 'whitelist') {
        $list_element = 'whitelist';
    } elseif ($type === 'trash') {
        $list_element = 'trash';
    } else {
        $list_element = 'blacklist';
    }

    /* get attcahment cat */
    $attachment_cat = '';
    if (isset($_GET['attachment_cat']) && $_GET['attachment_cat'] != '') {
        $attachment_cat = sanitize_text_field($_GET['attachment_cat']);
    }
}

?>

<select class="wmh-filter-select sl-box" id="wmh-filter-select" name="wmh-filter-select">
    <option value="" <?php if (isset($_GET['page'])) {
                            echo esc_attr('selected');
                        } else {
                            echo '';
                        } ?>><?php _e('Select Category', MEDIA_HYGIENE); ?></option>
    <option value='all' <?php if (isset($attachment_cat) && $attachment_cat == 'all') {
                            echo esc_attr('selected');
                        } else {
                            echo '';
                        } ?>><?php _e('All', MEDIA_HYGIENE); ?></option>
    <option value='images' <?php if (isset($attachment_cat) && $attachment_cat == 'images') {
                                echo esc_attr('selected');
                            } else {
                                echo '';
                            } ?>><?php _e('Images', MEDIA_HYGIENE); ?></option>
    <option value='video' <?php if (isset($attachment_cat) && $attachment_cat == 'video') {
                                echo esc_attr('selected');
                            } else {
                                echo '';
                            } ?>><?php _e('Video', MEDIA_HYGIENE); ?></option>
    <option value='audio' <?php if (isset($attachment_cat) && $attachment_cat == 'audio') {
                                echo esc_attr('selected');
                            } else {
                                echo '';
                            } ?>><?php _e('Audio', MEDIA_HYGIENE); ?></option>
    <option value='documents' <?php if (isset($attachment_cat) && $attachment_cat == 'documents') {
                                    echo esc_attr('selected');
                                } else {
                                    echo '';
                                } ?>><?php _e('Documents', MEDIA_HYGIENE); ?></option>
    <option value='others' <?php if (isset($attachment_cat) && $attachment_cat == 'others') {
                                echo esc_attr('selected');
                            } else {
                                echo '';
                            } ?>><?php _e('Others', MEDIA_HYGIENE); ?></option>
</select>

<input type="hidden" name="list_element" id="list_element" value="<?php echo esc_attr($list_element); ?>" />
<input type="text" id="date" name="date" value="<?php echo esc_attr($filter_date); ?>" placeholder="<?php _e('YYYY-MM', MEDIA_HYGIENE); ?>">
<button type="submit" name="filter-submit" id="filter-submit" class="button"><?php _e("Filter", MEDIA_HYGIENE); ?></button>&nbsp;<img class="mb-1 filter-media-loader" src="<?php echo esc_url(site_url() . "/wp-includes/images/spinner.gif") ?>" style="display: none;" />