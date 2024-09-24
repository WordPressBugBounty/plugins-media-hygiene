<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class wmh_vc
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

    public function fn_get_visual_composer_data()
    {

        /* get page and post id, which is edited by visual composer */
        $post_ids_sql = 'SELECT post_id FROM ' . $this->wp_postmeta . ' WHERE meta_key = "vcv-be-editor"';
        $post_ids_result = $this->conn->get_results($post_ids_sql, ARRAY_A);
        $vc_post_content = array();
        if (isset($post_ids_result) && !empty($post_ids_result)) {
            foreach ($post_ids_result as $results) {
                if ($results['post_id'] != '') {
                    $post_id = $results['post_id'];
                    if ($post_id) {
                        $vc_post_content_sql = 'SELECT post_content FROM ' . $this->wp_posts . ' WHERE ID = "' . $post_id . '" AND ( post_type = "page" OR post_type = "post" )';
                        $vc_post_content_result = $this->conn->get_results($vc_post_content_sql, ARRAY_A);
                        if (isset($vc_post_content_result) && !empty($vc_post_content_result)) {
                            foreach ($vc_post_content_result as $content) {
                                if ($content['post_content'] != '') {
                                    array_push($vc_post_content, htmlentities($content['post_content']));
                                }
                            }
                        }
                    } else {
                        $module = 'Visual composer';
                        $error = 'visual composer page and post id not set';
                        $wmh_general = new wmh_general();
                        $wmh_general->fn_wmh_error_log($module, $error);
                    }
                }
            }
        } else {
            $module = 'Visual composer';
            $error = 'not found visual composer data from meta key called vcv-be-editor';
            $wmh_general = new wmh_general();
            $wmh_general->fn_wmh_error_log($module, $error);
        }
        return $vc_post_content;
    }

    public function fn_get_visual_composer_template_data()
    {

        /* get all template post id from wp_posts */
        $get_template_post_id_sql = 'SELECT ID from ' . $this->wp_posts . ' where post_type = "vcv_templates"';
        $get_template_post_id_result = $this->conn->get_results($get_template_post_id_sql, ARRAY_A);
        $temp_content  = array();
        if (isset($get_template_post_id_result) && !empty($get_template_post_id_result)) {
            foreach ($get_template_post_id_result as $post_id) {
                if ($post_id['ID'] != '') {
                    $id = $post_id['ID'];
                    $template_post_content = get_post_meta($id, 'vcvEditorTemplateElements');
                    array_push($temp_content, $template_post_content);
                }
            }
        } else {
            $module = 'Visual composer';
            $error = 'visual composer template data not set';
            $wmh_general = new wmh_general();
            $wmh_general->fn_wmh_error_log($module, $error);
        }
        return $temp_content;
    }
}
