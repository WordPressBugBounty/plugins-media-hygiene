<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class wmh_bricks
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

    /* get page and post content data*/
    public function fn_wmh_bricks_get_data()
    {

        $sql = 'SELECT post_id FROM ' . $this->wp_postmeta . ' WHERE meta_value = "bricks"';
        $data = $this->conn->get_results($sql, ARRAY_A);
        $post_ids = array();
        $post_content_array = array();
        /* make post content type array */
        if (isset($data) && !empty($data)) {
            foreach ($data as $d_post_id) {
                if ($d_post_id['post_id']) {
                    $post_id = $d_post_id['post_id'];
                    array_push($post_ids, $post_id);
                }
            }
            if (!empty($post_ids)) {
                $post_ids_str = implode(',', $post_ids);
                if ($post_ids_str) {
                    $post_sql = 'SELECT meta_value FROM ' . $this->wp_postmeta . ' WHERE post_id IN(' . $post_ids_str . ') AND meta_key = "_bricks_page_content_2"';
                    $post_data = $this->conn->get_results($post_sql, ARRAY_A);
                    if (!empty($post_data)) {
                        foreach ($post_data as $post_content_data) {
                            $post_content = htmlentities($post_content_data['meta_value']);
                            array_push($post_content_array, $post_content);
                        }
                    } else {
                        $module = 'Bricks';
                        $error = 'Bricks type post_content data not set';
                        $wmh_general = new wmh_general();
                        $wmh_general->fn_wmh_error_log($module, $error);
                    }
                }
            } else {
                $module = 'Bricks';
                $error = 'Bricks post_ids not set';
                $wmh_general = new wmh_general();
                $wmh_general->fn_wmh_error_log($module, $error);
            }
        } else {
            $module = 'Bricks';
            $error = 'bricks page and post content data not set';
            $wmh_general = new wmh_general();
            $wmh_general->fn_wmh_error_log($module, $error);
        }
        return $post_content_array;
    }

    /* get briks template data for header */
    public function fn_wmh_get_bricks_template_header_data()
    {

        /* get post_type bricks _template post ids */
        $post_ids = $this->fn_wmh_get_bricks_template_post_ids();
        $post_content_array = array();

        if (!empty($post_ids)) {
            $post_ids_str = implode(',', $post_ids);
            if ($post_ids_str) {
                $post_sql = 'SELECT meta_value FROM ' . $this->wp_postmeta . ' WHERE post_id IN(' . $post_ids_str . ') AND meta_key = "_bricks_page_header_2"';
                $post_data = $this->conn->get_results($post_sql, ARRAY_A);
                if (!empty($post_data)) {
                    foreach ($post_data as $post_content_data) {
                        $post_content = htmlentities($post_content_data['meta_value']);
                        array_push($post_content_array, $post_content);
                    }
                } else {
                    $module = 'Bricks';
                    $error = 'Bricks header template data not set';
                    $wmh_general = new wmh_general();
                    $wmh_general->fn_wmh_error_log($module, $error);
                }
            }
        } else {
            $module = 'Bricks';
            $error = 'Bricks post ids not set for header template data';
            $wmh_general = new wmh_general();
            $wmh_general->fn_wmh_error_log($module, $error);
        }
        return $post_content_array;
    }

    /* get bricks template data for footer */
    public function fn_wmh_get_bricks_template_footer_data()
    {
        /* get post_type bricks _template post ids */
        $post_ids = $this->fn_wmh_get_bricks_template_post_ids();
        $post_content_array = array();

        if (!empty($post_ids)) {
            $post_ids_str = implode(',', $post_ids);
            if ($post_ids_str) {
                $post_sql = 'SELECT meta_value FROM ' . $this->wp_postmeta . ' WHERE post_id IN(' . $post_ids_str . ') AND meta_key = "_bricks_page_footer_2"';
                $post_data = $this->conn->get_results($post_sql, ARRAY_A);
                if (!empty($post_data)) {
                    foreach ($post_data as $post_content_data) {
                        $post_content = htmlentities($post_content_data['meta_value']);
                        array_push($post_content_array, $post_content);
                    }
                } else {
                    $module = 'Bricks';
                    $error = 'Bricks footer template data not set';
                    $wmh_general = new wmh_general();
                    $wmh_general->fn_wmh_error_log($module, $error);
                }
            }
        } else {
            $module = 'Bricks';
            $error = 'Bricks post ids not set for footer template data';
            $wmh_general = new wmh_general();
            $wmh_general->fn_wmh_error_log($module, $error);
        }
        return $post_content_array;
    }

    /* get bricks template data for page */
    public function fn_wmh_get_bricks_template_page_data()
    {

        /* get post_type bricks _template post ids */
        $post_ids = $this->fn_wmh_get_bricks_template_post_ids();
        $post_content_array = array();

        if (!empty($post_ids)) {
            $post_ids_str = implode(',', $post_ids);
            if ($post_ids_str) {
                $post_sql = 'SELECT meta_value FROM ' . $this->wp_postmeta . ' WHERE post_id IN(' . $post_ids_str . ') AND meta_key = "_bricks_page_content_2"';
                $post_data = $this->conn->get_results($post_sql, ARRAY_A);
                if (!empty($post_data)) {
                    foreach ($post_data as $post_content_data) {
                        $post_content = htmlentities($post_content_data['meta_value']);
                        array_push($post_content_array, $post_content);
                    }
                } else {
                    $module = 'Bricks';
                    $error = 'Bricks page template data not set';
                    $wmh_general = new wmh_general();
                    $wmh_general->fn_wmh_error_log($module, $error);
                }
            } else {
                $module = 'Bricks';
                $error = 'Bricks post ids not set for page template data';
                $wmh_general = new wmh_general();
                $wmh_general->fn_wmh_error_log($module, $error);
            }
        }
        return $post_content_array;
    }

    public function fn_wmh_get_bricks_template_post_ids()
    {

        $sql = 'SELECT ID FROM ' . $this->wp_posts . ' WHERE post_type = "bricks_template"';
        $result = $this->conn->get_results($sql, ARRAY_A);
        $post_ids = array();
        if (isset($result) && !empty($result)) {
            foreach ($result as $b_data) {
                if ($b_data['ID'] != '') {
                    $post_id = $b_data['ID'];
                    array_push($post_ids, $post_id);
                }
            }
        } else {
            $module = 'Bricks';
            $error = 'Bricks post type bricks_template data not set';
            $wmh_general = new wmh_general();
            $wmh_general->fn_wmh_error_log($module, $error);
        }
        return $post_ids;
    }
}
