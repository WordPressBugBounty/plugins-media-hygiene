<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class wmh_enfold
{

    public $conn;
    public $wp_posts;
    public $wp_postmeta;
    public $wp_layerslider;

    public function __construct()
    {
        global $wpdb;
        $this->conn = $wpdb;
        $this->wp_posts = $this->conn->prefix . 'posts';
        $this->wp_postmeta = $this->conn->prefix . 'postmeta';
        $this->wp_layerslider = $this->conn->prefix . 'layerslider';
    }

    public function fn_wmh_enfold_get_layerslider_data() {

        /* get layerslider data */
        $sql = 'SELECT data FROM '.$this->wp_layerslider.'';
        $data = $this->conn->get_results($sql, ARRAY_A);
        $layerslider_datas = array();
        if(isset($data) && !empty($data)) {
            foreach( $data as $d ) {
                if( $d['data'] ){
                    array_push($layerslider_datas, htmlentities($d['data']));
                }
            }
        } else {
            $module = 'Enfold';
            $error = 'Enfold data not found';
            $wmh_general = new wmh_general();
            $wmh_general->fn_wmh_error_log($module, $error);
        }
        return $layerslider_datas;
    }

}

