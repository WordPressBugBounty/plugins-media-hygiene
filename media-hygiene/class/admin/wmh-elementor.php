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
    public function fn_wmh_get_elementor_data($limit, $offset)
    {
        $elementor_data = $this->conn->get_results(
            $this->conn->prepare(
                "SELECT p.post_type, pm.post_id, pm.meta_value 
                FROM {$this->conn->postmeta} pm 
                JOIN {$this->conn->posts} p 
                ON p.ID = pm.post_id 
                WHERE pm.meta_key = %s 
                AND p.post_type != %s 
                LIMIT %d OFFSET %d",
                '_elementor_data',
                'revision',
                $limit,
                $offset
            ),
            ARRAY_A
        );

        $elementor_urls = array();
        if (isset($elementor_data) && !empty($elementor_data)) {
            foreach ($elementor_data as $row) {
                $decoded_data = json_decode($row['meta_value'], true);
                if (!empty($decoded_data)) {
                    $elementor_urls = array_merge(
                        $elementor_urls,
                        $this->fn_wmh_find_url_from_elementor_responce($decoded_data)
                    );
                }
            }
        } else {
            $module = 'Elementor';
            $error = 'elementor data not found';
            $wmh_general = new wmh_general();
            $wmh_general->fn_wmh_error_log($module, $error);
        }

        return array_values(array_unique($elementor_urls));
    }

    public function fn_wmh_find_url_from_elementor_responce($array = [])
    {
        $urls = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $urls = array_merge($urls, $this->fn_wmh_find_url_from_elementor_responce($value));
            } else {
                if ($key === 'url') {
                    if ($value && str_contains($value, 'wp-content/uploads')) {
                        $urls[] = $value;
                    }
                }
            }
        }
        return $urls;
    }
}
