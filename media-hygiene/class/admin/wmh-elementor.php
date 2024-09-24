<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class wmh_elementor
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

    /* get elementor data function. */
    public function fn_wmh_get_elementor_data()
    {
        $elementor_sql = ' SELECT post_id, meta_value FROM ' . $this->wp_postmeta . ' WHERE meta_key = "_elementor_data" ';
        $elementor_data = $this->conn->get_results($elementor_sql, ARRAY_A);
        $elementor_result = array();
        if (isset($elementor_data) && !empty($elementor_data)) {
            foreach ($elementor_data as $ed) {
                $post_id = $ed['post_id'];
                $meta_value = $ed['meta_value'];
                $post_type = get_post_type($post_id);
                if ($post_type != 'revision') {
                    array_push($elementor_result, $meta_value);
                }
            }
        } else {
            $module = 'Elementor';
            $error = 'elementor data not found';
            $wmh_general = new wmh_general();
            $wmh_general->fn_wmh_error_log($module, $error);
        }
        return $elementor_result;
    }
}
