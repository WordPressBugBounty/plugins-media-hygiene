<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class wmh_ocean_wp
{

    public $conn;
    public $wp_posts;
    public $wp_postmeta;

    public function __construct()
    {
        global $wpdb;
        $this->conn = $wpdb;
        $this->wp_posts = $this->conn->prefix . 'posts';
        $this->wp_postmeta = $this->conn->prefix . 'postmeta';
    }

    public function fn_wmh_theme_mods_oceanwp()
    {
        $ocean_array = array();

        /* get theme data */
        $themes_mode = get_option('theme_mods_oceanwp');

        if (isset($themes_mode)) {

            /* get custom logo */
            $custom_logo = '';
            if (isset($themes_mode['custom_logo']) && $themes_mode['custom_logo'] != '') {
                $custom_logo = $themes_mode['custom_logo'];
            }

            /* get ocean theme background image */
            $background_image = '';
            if (isset($themes_mode['ocean_background_image']) && $themes_mode['ocean_background_image'] != '') {
                $background_image = $themes_mode['ocean_background_image'];
            }

            /* make array for return value */
            $ocean_array = array(
                'custom_logo' => $custom_logo,
                'background_image' => $background_image
            );
        }

        return $ocean_array;
    }

    /* get oceanWP theme template or library data */
    public function fn_wmh_get_ocean_wp_library_data()
    {

        $lib_sql = 'SELECT ID FROM ' . $this->wp_posts . ' WHERE post_type = "oceanwp_library"';
        $lib_data = $this->conn->get_results($lib_sql, ARRAY_A);

        $ocean_logo = array();
        $ocean_custom_logo = array();
        $ocean_custom_retina_logo = array();

        if (isset($lib_data) && !empty($lib_data)) {
            foreach ($lib_data as $lib) {
                if ($lib['ID']) {
                    /* get ocean custom logo */
                    if (get_post_meta($lib['ID'], 'ocean_custom_logo', true)) {
                        $ocean_custom_logo[] = get_post_meta($lib['ID'], 'ocean_custom_logo', true);
                    }
                    /* get ocean custom retina logo */
                    if (get_post_meta($lib['ID'], 'ocean_custom_retina_logo', true)) {
                        $ocean_custom_retina_logo[] = get_post_meta($lib['ID'], 'ocean_custom_retina_logo', true);
                    }
                }
            }
        }

        $ocean_logo = array(
            'ocean_custom_logo' => $ocean_custom_logo,
            'ocean_custom_retina_logo' => $ocean_custom_retina_logo
        );

        return $ocean_logo;
    }
}