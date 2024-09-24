<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class wmh_divi
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

    public function fn_wmh_divi_get_data()
    {

        $sql = 'SELECT post_id FROM ' . $this->wp_postmeta . ' WHERE meta_key = "_et_pb_built_for_post_type"';
        $data = $this->conn->get_results($sql, ARRAY_A);
        $post_ids = array();
        $results = array();
        $results_array = array();
        $post_content_array = array();
        $reg = '/gallery_ids=(".*?"|\'.*?\'|.*?)[ >]/i';
        if (isset($data) && !empty($data)) {
            foreach ($data as $d_post_id) {
                $post_id = $d_post_id['post_id'];
                if ($post_id) {
                    array_push($post_ids, $post_id);
                }
            }
            if (!empty($post_ids)) {
                $post_ids_str = implode(',', $post_ids);
                if ($post_ids_str) {
                    $post_sql = 'SELECT ID, post_content, post_type FROM ' . $this->wp_posts . ' WHERE ID IN(' . $post_ids_str . ') AND post_type != "revision"';
                    $post_data = $this->conn->get_results($post_sql, ARRAY_A);
                    if (!empty($post_data)) {
                        foreach ($post_data as $post_content_data) {
                            $post_content = htmlentities($post_content_data['post_content']);
                            /* get gallery ids */
                            if (preg_match_all($reg, $post_content, $results)) {
                                if (!empty($results)) {
                                    unset($results[0]);
                                    foreach ($results[1] as $val) {
                                        array_push($results_array, $val);
                                    }
                                }
                            }
                            array_push($post_content_array, $post_content);
                        }
                    } else {
                        $module = 'Divi';
                        $error = 'not found divi post data';
                        $wmh_general = new wmh_general();
                        $wmh_general->fn_wmh_error_log($module, $error);
                    }
                }
            }
        } else {
            $module = 'Divi';
            $error = 'Divi data not set';
            $wmh_general = new wmh_general();
            $wmh_general->fn_wmh_error_log($module, $error);
        }

        return array(
            'content' => $post_content_array,
            'gallery_ids' => $results_array
        );
    }
}
