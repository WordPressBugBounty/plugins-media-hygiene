<?php

require_once ABSPATH . 'wp-admin/includes/plugin.php';

/* if uninstall.php is not called by WordPress, die */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

/* get all plugin list */
$all_plugins = get_plugins();

/* get scan option data. */
$wmh_scan_option_data = get_option('wmh_scan_option_data');

if (isset($wmh_scan_option_data) && !empty($wmh_scan_option_data) && $wmh_scan_option_data['delete_data_on_uninstall_plugin'] == 'on') {

    if (!array_key_exists('media-hygiene-pro/media-hygiene-pro.php', $all_plugins)) {

        /* drop wmh_unused_media_post_id table from database */
        $wmh_unused_media_post_id = $wpdb->prefix . 'wmh_unused_media_post_id';
        $wpdb->query("DROP TABLE IF EXISTS " . $wmh_unused_media_post_id);

        /* drop wmh_whitelist_media_post_id table. */
        $wmh_whitelist_media_post_id = $wpdb->prefix . 'wmh_whitelist_media_post_id';
        $wpdb->query("DROP TABLE IF EXISTS " . $wmh_whitelist_media_post_id);

        /* drop wmh_error_log from database */
        $wmh_error_log = $wpdb->prefix . 'wmh_error_log';
        $wpdb->query("DROP TABLE IF EXISTS " . $wmh_error_log);

        /* drop wmh_temp from database */
        $wmh_temp = $wpdb->prefix . 'wmh_temp';
        $wpdb->query("DROP TABLE IF EXISTS " . $wmh_temp);

        /* drop wmh_used_media_post_id */
        $wmh_used_media_post_id = $wpdb->prefix . 'wmh_used_media_post_id';
        $wpdb->query("DROP TABLE IF EXISTS " . $wmh_used_media_post_id);

        /* drop wmh_deleted_media  */
        $wmh_deleted_media = $wpdb->prefix . 'wmh_deleted_media';
        $wpdb->query("DROP TABLE IF EXISTS " . $wmh_deleted_media);

        /* drop wmh_save_scan_content  */
        $wmh_save_scan_content = $wpdb->prefix . 'wmh_save_scan_content';
        $wpdb->query("DROP TABLE IF EXISTS " . $wmh_save_scan_content);

        /* remove from option */
        delete_option('wmh_scan_option_data');
        delete_option('wmh_exclude_file_type');
        delete_option('wmh_start_time');
        delete_option('wmh_end_time');
        delete_option('wmh_scan_status');
        delete_option('wmh_send_data_to_server_permission');
        delete_option('wmh_scan_complete');
        /* general summery */
        delete_option('wmh_media_count');
        delete_option('wmh_total_media_size');
        delete_option('wmh_total_unused_media_count');
        delete_option('wmh_unused_media_size');
        delete_option('wmh_use_media_count');
        delete_option('wmh_use_media_size');
        /* media breakdown */
        delete_option('wmh_media_breakdown');
        /* media info */
        delete_option('wmh_media_type_info');
        delete_option('wmh_new_update_after_scan');
        delete_option('wmh_page_url_content');
        delete_option('wmh_whitelist_media_post_ids');
        delete_option('wmh_all_attachment_ids');
        delete_option('wmh_database_version');
        delete_option('wmh_database_version_2');
        delete_option('wmh_scan_status_new');
        /* new table delete  */
        delete_option('wmh_create_new_table_save_scan_content');
        /* save content option delete */
        delete_option('wmh_post_content_data');
        delete_option('wmh_page_content_data');
        delete_option('wmh_page_post_feature_image_ids_data');
        delete_option('wmh_elementor_data');
        delete_option('wmh_divi_post_content_data');
        delete_option('wmh_bricks_post_content_data');
        delete_option('wmh_bricks_temp_header_data');
        delete_option('wmh_bricks_temp_footer_data');
        delete_option('wmh_bricks_temp_page_data');
        delete_option('wmh_vc_post_content_data');
        delete_option('wmh_vc_tmp_data_data');
        delete_option('wmh_enfold_layerslider_data');
        delete_option('wmh_theme_mode_data');
        delete_option('wmh_ocean_logo_data');
        delete_option('wmh_whitelist_media_post_ids');
        /* remove from option */
        delete_option('wmh_delete_media_start_time');
        delete_option('wmh_delete_media_end_time');
        delete_option('wmh_delete_all_media_count');
        delete_option('wmh_directory_scan_start_time');
        delete_option('wmh_directory_scan_end_time');
        delete_option('wmh_licence_key_user_info');
        delete_option('wmh_dir_delete_media_start_time');
        delete_option('wmh_dir_delete_media_end_time');
        delete_option('wmh_dir_delete_all_media_total_media_count');
        delete_option('wmh_last_scan_directory_name');
        delete_option('wmh_send_data_to_server_permission');
        delete_option('wmh_exclude_file_type');
        delete_option('wmh_scan_complete');
        delete_option('wmh_dir_scan_complete');
        delete_option('wmh_exclude_directory');
        delete_option('wmh_close_analytics_permission_permanently');
        delete_option('wmh_plugin_db_version');
        delete_option('wmh_plugin_db_version_upgrade');

        /* remove scheduled hook */
        wp_clear_scheduled_hook('fn_mh_daily_cron_job');
        wp_clear_scheduled_hook('fn_mh_biweekly_cron_job');
        wp_clear_scheduled_hook('fn_mh_weekly_cron_job');
        wp_clear_scheduled_hook('fn_mh_monthly_cron_job');
        wp_clear_scheduled_hook('fn_mh_quarterly_cron_job');
    }
}
