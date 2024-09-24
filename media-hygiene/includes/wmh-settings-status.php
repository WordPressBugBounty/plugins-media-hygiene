<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');


if (!class_exists('WP_Debug_Data')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
}
if (!class_exists('WP_Site_Health')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
}
$health_check_site_status = WP_Site_Health::get_instance();


class wmh_settings_status extends WP_Debug_Data
{

    public function fn_wmh_get_site_health_data()
    {
        WP_Debug_Data::check_for_updates();
        $info = WP_Debug_Data::debug_data();
        $unset_key_array = array(
            'wp-paths-sizes',
            'wp-dropins',
            'wp-parent-theme',
            'wp-mu-plugins',
            'wp-database',
            'wp-constants',
            'wp-filesystem',
            'wpforms'

        );
        foreach ($unset_key_array as $unset_fields) {
            unset($info[$unset_fields]);
        }
        return $info;
    }
}
