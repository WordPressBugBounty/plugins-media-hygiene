<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

if (!class_exists('WP_List_Table')) {
    include_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class wmh_media_hygiene_view extends WP_List_Table
{
    public $conn;
    public $wp_posts;
    public $wp_postmeta;
    public $wmh_unused_media_post_id;
    public $wmh_whitelist_media_post_id;
    public $wmh_temp;
    public $wp_upload_dir;
    public $basedir;

    public function fn_wmh_first_load()
    {
        global $wpdb;
        $this->conn = $wpdb;
        $this->wp_upload_dir = wp_upload_dir();
        $this->basedir = $this->wp_upload_dir['basedir'];
        $this->wp_posts = $this->conn->prefix . 'posts';
        $this->wp_postmeta = $this->conn->prefix . 'postmeta';
        $this->wmh_unused_media_post_id = $this->conn->prefix . MH_PREFIX . 'unused_media_post_id';
        $this->wmh_whitelist_media_post_id = $this->conn->prefix . MH_PREFIX . 'whitelist_media_post_id';
        $this->wmh_temp = $this->conn->prefix . MH_PREFIX . 'temp';
    }

    /* prepare function for WP List table. */
    public function prepare_items()
    {
        $data = array();
        $total_items = 0;
        $total_media_result = 0;

        /* search box code. */
        $search_term = isset($_REQUEST['s']) ? sanitize_text_field(trim($_REQUEST['s'])) : '';

        if (isset($_GET['page']) && sanitize_text_field($_GET['page']) == 'wmh-media-hygiene') {

            /* get scan option data. */
            $wmh_scan_option_data = get_option('wmh_scan_option_data', true);
            $media_per_page_input = !empty($wmh_scan_option_data['media_per_page_input']) ? $wmh_scan_option_data['media_per_page_input'] : 10;

            $limit = $per_page = $media_per_page_input;
            $current_page = $this->get_pagenum();
            $offset = $limit * ($current_page - 1);

            $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'blacklist';

            /* get data */
            if ($type === 'blacklist' || $type === 'whitelist') {
                $data = $this->fn_wmh_get_unused_media_data($search_term, $limit, $offset);
                /* get pagination. */
                if (is_array($data) && !empty($data)) {
                    if ($type === 'blacklist') {
                        $table_exists_prepare = $this->conn->get_var("SHOW TABLES LIKE '$this->wmh_unused_media_post_id'") == $this->wmh_unused_media_post_id;
                        if ($table_exists_prepare) {
                            $total_media_result = $this->conn->get_row('SELECT count(post_id) as total_media FROM ' . $this->wmh_unused_media_post_id . '');
                            $total_media_result = $total_media_result->total_media;
                        }
                    } elseif ($type === 'whitelist') {
                        $table_exists_prepare_2 = $this->conn->get_var("SHOW TABLES LIKE '$this->wmh_whitelist_media_post_id'") == $this->wmh_whitelist_media_post_id;
                        if ($table_exists_prepare_2) {
                            $total_media_result = $this->conn->get_row('SELECT count(post_id) as total_media FROM ' . $this->wmh_whitelist_media_post_id . '');
                            $total_media_result = $total_media_result->total_media;
                        }
                    }
                }
            } else if ($type == 'trash') {
                $data = $this->fn_get_trash_media_data($search_term, $limit, $offset);
                $total_media_result = $this->conn->get_row('SELECT COUNT(ID) as total_media FROM ' . $this->conn->prefix . 'posts WHERE post_type = "attachment" AND post_status = "trash"');
                $total_media_result = $total_media_result->total_media;
            }


            /* filter data here */
            if (isset($_GET['attachment_cat']) || isset($_GET['date'])) {
                $data = $this->fn_wmh_filter_data($limit, $offset);
            }

            if (isset($data['count'])) {
                $total_media_result = $data['count'];
                unset($data['count']);
            }

            $total_items = $total_media_result;
            if ($total_items != '') {
                $this->set_pagination_args(array(
                    'total_items' => ceil($total_items),
                    'per_page' => ceil($per_page)
                ));
            }

            if (is_array($data) && !empty($data)) {

                /* item, which appear in list table. */
                $this->items = $data;
            }
        }

        /* columns. */
        $columns = $this->get_columns();
        $this->_column_headers = array($columns);

        /* view */
        $this->views();
    }

    /* Function to get trash media data */
    public function fn_get_trash_media_data($search_term = '', $limit = '', $offset = '')
    {

        $table_data = array();
        $posts_data = array();
        $count = 0;

        $sql_count = "SELECT COUNT(ID) FROM {$this->wp_posts} WHERE post_type = 'attachment' AND post_status = 'trash'";
        if ($search_term) {
            $sql_count .= " AND (ID LIKE '%$search_term%' OR post_title LIKE '%$search_term%' OR post_mime_type LIKE '%$search_term%' OR guid LIKE '%$search_term%')";
        }
        $count = $this->conn->get_var($sql_count);

        $sql = "SELECT ID, post_title, post_date, post_mime_type FROM {$this->wp_posts} WHERE post_type = 'attachment' AND post_status = 'trash'";
        if ($search_term) {
            $sql .= " AND (ID LIKE '%$search_term%' OR post_title LIKE '%$search_term%' OR post_mime_type LIKE '%$search_term%' OR guid LIKE '%$search_term%')";
        }
        $sql .= " LIMIT $limit OFFSET $offset";


        $posts_data = $this->conn->get_results($sql, ARRAY_A);

        if (!empty($posts_data)) {
            foreach ($posts_data as $pd) {
                $pd_id = $pd['ID'];
                $pd_title = $pd['post_title'];
                $pd_date = $pd['post_date'];
                $post_mime_type = $pd['post_mime_type'];
                $pd_size = $this->fn_wmh_calculate_size($pd_id, $post_mime_type);
                $table_data[] = $this->fn_wmh_display_data($pd_id, $pd_title, $pd_date, $post_mime_type, $pd_size);
            }
        }

        if ($count) {
            $table_data['count'] = $count;
        }

        return $table_data;
    }

    public function fn_wmh_calculate_size($attachment_id = '', $post_mime_type = '', $media_size_array = array())
    {
        /* declare array */
        $register_size_by_post = array();

        /* get original image path */
        $original_image_path = wp_get_original_image_path($attachment_id);
        $original_image_path = substr($original_image_path, 0, strrpos($original_image_path, "/"));

        if (str_contains($post_mime_type, 'image')) {
            /* get attachment size data by id */
            $attachment_size = wp_get_attachment_metadata($attachment_id);
            if (isset($attachment_size['sizes']) && !empty($attachment_size['sizes'])) {
                $register_size_by_post = array_keys($attachment_size['sizes']);
            }
            /* main file */
            if (isset($attachment_size['file']) && $attachment_size['file'] != '') {
                $main_file_path = $this->basedir . '/' . $attachment_size['file'];
                $main_file_path_size = wp_filesize($main_file_path);
                array_push($media_size_array, $main_file_path_size);
            }
            if (isset($register_size_by_post) && !empty($register_size_by_post)) {
                foreach ($register_size_by_post as $rsp) {
                    /* check multiple size */
                    if (isset($attachment_size['sizes'][$rsp]['file'])) {
                        if ($attachment_size['sizes'][$rsp]['file'] != '') {
                            $multi_file_name = $attachment_size['sizes'][$rsp]['file'];
                            $make_new_file_name = $original_image_path . '/' . $multi_file_name;
                            $make_new_file_name_size = wp_filesize($make_new_file_name);
                            array_push($media_size_array, $make_new_file_name_size);
                        }
                    }
                }
            }
            /* check original image */
            if (isset($attachment_size['original_image']) && $attachment_size['original_image'] != '') {
                $original_image_path = wp_get_original_image_path($attachment_id);
                $original_image_size = wp_filesize($original_image_path);
                array_push($media_size_array, $original_image_size);
            }
            /* check file size for svg */
            if (str_contains($post_mime_type, 'svg')) {
                if (isset($attachment_size['filesize']) && $attachment_size['filesize'] != '') {
                    $svg_file_size = $attachment_size['filesize'];
                    array_push($media_size_array, $svg_file_size);
                }
            }
        } else {
            /* get media size. */
            $attachment_filesize = wp_filesize(get_attached_file($attachment_id));
            array_push($media_size_array, $attachment_filesize);
        }
        return array_sum($media_size_array);
    }


    /* default header view */
    public function fn_wmh_header_view()
    {

        $wmh_general = new wmh_general();
        $wmh_general->fn_wmh_get_template('wmh-header-view.php');
    }

    /* new media added information. */
    public function fn_wmh_new_media_added_info()
    {
        $wmh_general = new wmh_general();
        $wmh_general->fn_wmh_get_template('wmh-new-media-info-view.php');
    }

    /* important notice */
    public function fn_wmh_heading_and_notice()
    {
        $wmh_general = new wmh_general();
        $wmh_general->fn_wmh_get_template('wmh-heading-and-notice-view.php');
    }

    /* dashboard html, including general summary and media breakdown and notice. */
    public function fn_wmh_dashboard_html()
    {
        $wmh_general = new wmh_general();
        $wmh_general->fn_wmh_get_template('wmh-dashboard-view.php');
    }

    /* scan button and delete all media button html. */
    public function fn_wmh_scan_button_html()
    {
        $wmh_general = new wmh_general();
        $wmh_general->fn_wmh_get_template('wmh-button-view.php');
    }

    public function fn_wmh_trash_action_view()
    {
        $wmh_general = new wmh_general();
        $wmh_general->fn_wmh_get_template('wmh-trash-view.php');
    }

    /* filter select box */
    public function extra_tablenav($which)
    {
        if ($which == 'top') {
            $wmh_general = new wmh_general();
            $wmh_general->fn_wmh_get_template('wmh-filter-view.php');
        }
    }


    public function get_views()
    {
        $types = ['blacklist', 'whitelist', 'trash'];
        $styles = [];
        $admin_url = admin_url();

        $current_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'blacklist';

        foreach ($types as $type) {
            $styles[$type] = ($current_type === $type) ? 'style="color:green;font-weight:600"' : '';
        }

        $blacklist_count = $this->fn_wmh_count_blacklist_media();
        $whitelist_count = $this->fn_wmh_count_whitelist_media();
        $trash_count =  wp_count_posts('attachment')->trash;

        $status_links = [
            'blacklist' => '<a href="' . esc_url($admin_url) . 'admin.php?page=wmh-media-hygiene&type=blacklist" ' . $styles['blacklist'] . '>' . __("Unused", MEDIA_HYGIENE) . '</a>&nbsp;<span>(' . esc_html($blacklist_count) . ')</span>',
            'whitelist' => '<a href="' . esc_url($admin_url) . 'admin.php?page=wmh-media-hygiene&type=whitelist" ' . $styles['whitelist'] . '>' . __("Whitelist", MEDIA_HYGIENE) . '</a>&nbsp;<span>(' . esc_html($whitelist_count) . ')</span>',
            'trash'     => '<a href="' . esc_url($admin_url) . 'admin.php?page=wmh-media-hygiene&type=trash" ' . $styles['trash'] . '>' . __("Trash", MEDIA_HYGIENE) . '</a>&nbsp;<span>(' . esc_html($trash_count) . ')</span>'
        ];

        return $status_links;
    }



    /* default column function. */
    public function get_columns()
    {

        $cb = '<input type="checkbox"/>';
        $columns = array(

            'cb' => $cb,
            'image' => esc_html(__('File', MEDIA_HYGIENE)),
            'name' => esc_html(__('Name', MEDIA_HYGIENE)),
            'file_size' => esc_html(__('File Size', MEDIA_HYGIENE)),
            'file_path' => esc_html(__('File Path', MEDIA_HYGIENE)),
            'image_type' => esc_html(__('File Type', MEDIA_HYGIENE)),
            'date' => esc_html(__('Date', MEDIA_HYGIENE))

        );
        return $columns;
    }

    /* checkbox column. */
    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="post[]" value="%s" class="single-check-image" data-size="%u"/>', $item['id'], $item['file_size']);
    }

    /* image column. */
    public function column_image($item)
    {
        $post_mime_type = sanitize_mime_type(get_post_mime_type($item['id']));

        $column_image_html = '';
        if ($post_mime_type == 'application/pdf') {
            $column_image_html = '<span class="pdf-alt-icon"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i></span>';
        } else if ($post_mime_type == 'application/zip' || $post_mime_type == 'application/x-7z-compressed') {
            $column_image_html = '<span class="zip-alt-icon"><i class="fa-solid fa-file-zipper" aria-hidden="true"></i></span>';
        } else if ($post_mime_type == 'text/html') {
            $column_image_html = '<span class="text-html-alt-icon"><i class="fa-solid fa-file-code"></i></span>';
        } else if ($post_mime_type == 'video/mp4' ||  $post_mime_type == 'video/avi' || $post_mime_type == 'video/x-ms-wmv' || $post_mime_type == 'video/quicktime') {
            $column_image_html = '<span class="video-alt-icon"><i class="fa-solid fa-file-video"></i></span>';
        } else if ($post_mime_type == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            $column_image_html = '<span class="doc-alt-icon"><i class="fa-solid fa-file-word"></i></span>';
        } else if ($post_mime_type == 'application/vnd.openxmlformats-officedocument.presentationml.presentation' || $post_mime_type == 'application/vnd.ms-powerpoint') {
            $column_image_html = '<span class="ppt-alt-icon"><i class="fa-solid fa-file-powerpoint"></i></span>';
        } else if ($post_mime_type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            $column_image_html = '<span class="xlsx-alt-icon"><i class="fa-solid fa-file-excel"></i></span>';
        } else if ($post_mime_type == 'audio/mpeg' || $post_mime_type == 'audio/wav') {
            $column_image_html = '<span class="audio-alt-icon"><i class="fa-solid fa-file-audio"></i></span>';
        } else if ($post_mime_type == 'application/octet-stream') {
            $column_image_html = '<span class="octet-stream-alt-icon"><i class="fa-solid fa-file-lines"></i></span>';
        } else if ($post_mime_type == 'text/plain') {
            $column_image_html = '<span class="text-alt-icon"><i class="fa-solid fa-file-lines"></i></span>';
        } else if ($post_mime_type == 'image/jpg' || $post_mime_type == 'image/jpeg' || $post_mime_type == 'image/png' || $post_mime_type == 'image/webp' || $post_mime_type == 'image/gif') {
            $url =  wp_get_attachment_image_url($item['id']);
            $column_image_html = '<img src="' . esc_url($url) . '" width="60" height="60"/>';
        } else if ($post_mime_type == '') {
            $column_image_html = '<span class="null-alt-icon"><i class="fa-solid fa-file-circle-question"></i></span>';
        } else {
            $column_image_html = '<span class="null-alt-icon"><i class="fa-solid fa-file-circle-question"></i></span>';
        }
        return $column_image_html;
    }

    /* name column. */
    public function column_name($item)
    {


        $attachments = wp_get_attachment_metadata($item['id']);
        $count = 0;
        if (isset($attachments) && !empty($attachments)) {
            if (isset($attachments['sizes']) && !empty($attachments['sizes'])) {
                $count = count($attachments['sizes']);
            }
            if (isset($attachments['original_image']) && $attachments['original_image'] != '') {
                $count = $count + 1;
            }
        }
        $files_count = '';
        if ($count != 0) {
            $files_count = ' + ' . $count . ' thumbnails';
        }

        /* get original guid url from post id */
        $guid = sanitize_url($item['image']);

        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'blacklist';

        if ($type == 'blacklist') {
            $action = array(
                'edit' => "<a href='post.php?post=" . esc_attr($item['id']) . "&action=edit' target='_blank'>" . esc_html(__('Edit', MEDIA_HYGIENE)) . "</a>",
                'trash' => "<a href='javascript:void(0)' role='button' onclick='fn_wmh_row_action_trash( " . esc_js($item['id']) . ", " . esc_js($item['file_size']) . ")'>" . esc_html(__('Trash', MEDIA_HYGIENE)) . " <i class='fa-solid fa-spinner fa-spin row-action-trash-loader-" . esc_attr($item['id']) . "' style='display:none'></i></a>",
                'view' => '<a href=' . esc_url($guid) . ' target="_blank">' . esc_html(__('View', MEDIA_HYGIENE)) . '</a>',
                'copy' => "<a href='javascript:void(0)' role='button' onclick='fn_wmh_copy_clipbord( \" " . esc_js($guid) . " \", " . esc_js($item['id']) . "  )' class='copy-class-" . esc_attr($item['id']) . "'>" . esc_html(__('Copy URL To Clipboard', MEDIA_HYGIENE)) . "<i class='fa-solid fa-check copied-done-" . esc_attr($item['id']) . "' aria-hidden='true' style='color:green;display:none;'></i></a>",
                'whitelist' => "<a href='javascript:void(0)' role='button' onclick='fn_wmh_whitelist_single_image( " . esc_js($item['id']) . ")'>" . esc_html(__('Add To Whitelist', MEDIA_HYGIENE)) . " <i class='fa-solid fa-spinner fa-spin whitelist-loader-" . esc_attr($item['id']) . "' style='display:none'></i></a>",
            );
            return sprintf('%1$s %2$s', '<span>' . esc_html($item['name']) . '</span></br><span>' . esc_html($files_count) . '</span>', $this->row_actions($action));
        } elseif ($type == 'whitelist') {
            $action = array(
                'edit' => "<a href='post.php?post=" . esc_attr($item['id']) . "&action=edit' target='_blank'>" . esc_html(__('Edit', MEDIA_HYGIENE)) . "</a>",
                'view' => '<a href=' . esc_url($guid) . ' target="_blank">' . esc_html(__('View', MEDIA_HYGIENE)) . '</a>',
                'copy' => "<a href='javascript:void(0)' role='button' onclick='fn_wmh_copy_clipbord( \" " . esc_js($guid) . " \", " . esc_js($item['id']) . "  )' class='copy-class-" . esc_attr($item['id']) . "'>" . esc_html(__('Copy URL To Clipboard', MEDIA_HYGIENE)) . "<i class='fa-solid fa-check copied-done-" . esc_attr($item['id']) . "' aria-hidden='true' style='color:green;display:none;'></i></a>",
                'blacklist' => "<a href='javascript:void(0)' role='button' onclick='fn_wmh_blacklist_single_image( " . esc_js($item['id']) . ")'>" . esc_html(__('Remove From Whitelist', MEDIA_HYGIENE)) . " <i class='fa-solid fa-spinner fa-spin blacklist-loader-" . esc_attr($item['id']) . "' style='display:none'></i></a>",
            );
            return sprintf('%1$s %2$s', '<span style="color:green;font-weight:600;">' . esc_html($item['name']) . '</span><br><span>' . esc_html($files_count) . '</span>', $this->row_actions($action));
        } elseif ($type == 'trash') {
            $action = array(
                'restore' => "<a href='javascript:void(0)' role='button' onclick='fn_wmh_restore_single_image( " . esc_js($item['id']) . ", " . esc_js($item['file_size']) . ")'>" . esc_html(__('Restore', MEDIA_HYGIENE)) . " <i class='fa-solid fa-spinner fa-spin restore-loader-" . esc_attr($item['id']) . "' style='display:none'></i></a>",
                'delete' => "<a href='javascript:void(0)' role='button' onclick='fn_wmh_delete_permanently_single_image( " . esc_js($item['id']) . ", " . esc_js($item['file_size']) . ")'>" . esc_html(__('Delete Permanently', MEDIA_HYGIENE)) . " <i class='fa-solid fa-spinner fa-spin delete-loader-" . esc_attr($item['id']) . "' style='display:none'></i></a>",
                'view' => '<a href=' . esc_url($guid) . ' target="_blank">' . esc_html(__('View', MEDIA_HYGIENE)) . '</a>',
                'copy' => "<a href='javascript:void(0)' role='button' onclick='fn_wmh_copy_clipbord( \" " . esc_js($guid) . " \", " . esc_js($item['id']) . "  )' class='copy-class-" . esc_attr($item['id']) . "'>" . esc_html(__('Copy URL To Clipboard', MEDIA_HYGIENE)) . "<i class='fa-solid fa-check copied-done-" . esc_attr($item['id']) . "' aria-hidden='true' style='color:green;display:none;'></i></a>"
            );
            return sprintf('%1$s %2$s', '<span>' . esc_html($item['name']) . '</span></br><span>' . esc_html($files_count) . '</span>', $this->row_actions($action));
        }
    }

    /* file size column. */
    public function column_file_size($item)
    {

        return size_format($item['file_size']);
    }

    /* file path column */
    public function column_file_path($item)
    {
        /* get original guid url from post id */
        $guid = sanitize_url($item['image']);

        /* get media url. */
        return "<p class='media-url media-url-" . esc_attr($item['id']) . "' onclick='fn_wmh_media_url_copy( \"" . esc_js($guid) . "\", " . esc_js($item['id']) . " )' >" . esc_url($guid) . "</p>";
    }

    /* Date column. */
    public function column_date($item)
    {
        /* change date formate. */
        $original_date = $item['date'];
        $new_date = date('Y/m/d \a\t h:i a', strtotime($original_date));
        return $new_date;
    }

    /* image type column. */
    public function column_image_type($item)
    {
        $media_type = $item['image_type'];
        if ($media_type) {
            return $media_type;
        } else {
            return '-------';
        }
    }

    /* default column for all column. */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'name':
            case 'image':
            case 'file_size':
            case 'file_path':
            case 'image_type':
            case 'date':
                return $item[$column_name];
            default:
                return esc_html(__('No Value', MEDIA_HYGIENE));
        }
    }

    /* bulk actions */
    public function get_bulk_actions()
    {
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'blacklist';
        $actions = array();
        if ($type === 'blacklist') {
            $actions = array(
                'whitelist' => esc_html(__('Whitelist', MEDIA_HYGIENE)),
                'trash' => esc_html(__('Trash', MEDIA_HYGIENE))
            );
        } elseif ($type === 'whitelist') {
            $actions = array(
                'blacklist' => esc_html(__('Blacklist', MEDIA_HYGIENE))
            );
        } elseif ($type === 'trash') {
            $actions = array(
                'restore' => esc_html(__('Restore', MEDIA_HYGIENE)),
                'delete' => esc_html(__('Delete Permanently', MEDIA_HYGIENE))
            );
        }
        return $actions;
    }

    /* search box. */
    public function fn_wmh_search_box_html()
    {
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'blacklist';
        if (!in_array($type, ['blacklist', 'whitelist', 'trash'])) {
            $type = 'blacklist';
        }
        echo '<form method="get" id="form_search_post" name="form_search_post">';
        echo '<input type="hidden" name="page" value="wmh-media-hygiene">';
        echo '<input type="hidden" name="type" value="' . esc_attr($type) . '">';
        $this->search_box('Search', 'search_post_id');
        echo '</form>';
    }

    /* get unused media data from custome table wmh_unused_media_post_id. */
    public function fn_wmh_get_unused_media_data($search_term = '', $limit = '', $offset = '')
    {
        $table_data = array();
        $posts_data = array();
        $count = 0;

        $wmh_scan_complete = get_option('wmh_scan_complete');
        if (isset($wmh_scan_complete) && $wmh_scan_complete == 'interrupted') {
            $this->conn->query(' TRUNCATE TABLE ' . $this->wmh_unused_media_post_id . ' ');
            $this->conn->query('INSERT INTO ' . $this->wmh_unused_media_post_id . ' SELECT * FROM ' . $this->wmh_temp . ' ');
        }

        if ((isset($_GET['type'])  && sanitize_text_field($_GET['type']) == 'blacklist') || (!isset($_GET['type']))) {
            $sql = 'SELECT post_id, size FROM ' . $this->wmh_unused_media_post_id . ' ';
            $sql1 = "SELECT p.ID, p.post_title, p.post_date, p.post_mime_type, u.size FROM " . $this->wp_posts . " AS p INNER JOIN " . $this->wmh_unused_media_post_id . " AS u ON p.ID = u.post_id   WHERE p.post_type = 'attachment' AND ( p.ID LIKE '%$search_term%' OR p.post_title LIKE '%$search_term%' OR p.post_mime_type LIKE '%$search_term%' OR p.guid LIKE '%$search_term%' ) ";
        } else {
            $sql = 'SELECT post_id, size FROM ' . $this->wmh_whitelist_media_post_id . ' ';
            $sql1 = "SELECT p.ID, p.post_title, p.post_date, p.post_mime_type, u.size FROM " . $this->wp_posts . " AS p INNER JOIN " . $this->wmh_whitelist_media_post_id . " AS u ON p.ID = u.post_id   WHERE p.post_type = 'attachment' AND ( p.ID LIKE '%$search_term%' OR p.post_title LIKE '%$search_term%' OR p.post_mime_type LIKE '%$search_term%' OR p.guid LIKE '%$search_term%' ) ";
        }
        if (!$search_term) {
            $sql .= 'LIMIT ' . $limit . ' OFFSET ' . $offset . '';
        }
        $table_exists = $this->conn->get_var("SHOW TABLES LIKE '$this->wmh_unused_media_post_id'") == $this->wmh_unused_media_post_id;
        $table_exists2 = $this->conn->get_var("SHOW TABLES LIKE '$this->wmh_whitelist_media_post_id'") == $this->wmh_whitelist_media_post_id;
        if ($table_exists && $table_exists2) {
            $result = $this->conn->get_results($sql, ARRAY_A);
        }
        if ($search_term) {
            $posts_data_count = $this->conn->get_results($sql1, ARRAY_A);
            $count = count($posts_data_count);
            $sql1 .= 'LIMIT ' . $limit . ' OFFSET ' . $offset . '';
            $posts_data = $this->conn->get_results($sql1, ARRAY_A);
            if (isset($posts_data) && !empty($posts_data)) {
                foreach ($posts_data as $pd) {
                    $pd_id = $pd['ID'];
                    $pd_title = $pd['post_title'];
                    $pd_date = $pd['post_date'];
                    $post_mime_type = $pd['post_mime_type'];
                    $pd_size = $pd['size'];
                    $table_data[] = $this->fn_wmh_display_data($pd_id, $pd_title, $pd_date, $post_mime_type, $pd_size);
                }
            }
        } else {
            if (isset($result) && !empty($result)) {

                foreach ($result as $r) {
                    if ($r['post_id']) {
                        $p_id = $r['post_id'];
                        $p_size = $r['size'];
                        $sql1 = " SELECT post_title, post_date, post_mime_type from " . $this->wp_posts . " WHERE post_type = 'attachment' AND ID = '" . $p_id . "' ";
                        $posts_data = $this->conn->get_row($sql1, ARRAY_A);
                        if (!empty($posts_data)) {
                            $p_title = $posts_data['post_title'];
                            $p_date = $posts_data['post_date'];
                            $post_mime_type = $posts_data['post_mime_type'];
                            $table_data[] = $this->fn_wmh_display_data($p_id, $p_title, $p_date, $post_mime_type, $p_size);
                        }
                    }
                }
            }
        }
        if (isset($count) && $count != '' && $count != 0) {
            $table_data['count'] = $count;
        }
        return $table_data;
    }

    public function fn_wmh_display_data($post_id = '', $post_title = '', $post_date = '', $post_mime_type = '', $file_size = '')
    {
        if ($post_id) {
            /* guid */
            if (str_contains($post_mime_type, 'image')) {
                $guid = wp_get_original_image_url($post_id);
            } else {
                $guid = wp_get_attachment_url($post_id);
            }
            $guid_url = sanitize_url($guid);
            /* get original post or media type */
            $media_ext = '';
            $media_type_ext = wp_check_filetype($guid_url);
            if (isset($media_type_ext['ext'])) {
                if ($media_type_ext['ext'] != '') {
                    $media_ext = $media_type_ext['ext'];
                }
            }
            /* display data. */
            $table_data = array(
                'id' => $post_id,
                'image' => $guid_url,
                'name' => $post_title,
                'image_type' => $media_ext,
                'file_size' =>  $file_size,
                'date' => $post_date,
            );
        } else {
            /* display data. */
            $table_data = array(
                'id' => '',
                'image' => '',
                'name' => '',
                'image_type' => '',
                'file_size' =>  '',
                'date' => '',
            );
        }

        return $table_data;
    }

    public function fn_wmh_filter_data($limit, $offset)
    {
        $table_data = array();
        $posts_data = array();
        $attachment_cat = '';
        $date = '';
        $count = 0;

        if (isset($_GET['attachment_cat'])) {
            $attachment_cat = sanitize_text_field($_GET['attachment_cat']);
        }
        if (isset($_GET['date'])) {
            $date = sanitize_text_field($_GET['date']);
        }

        /* filter data here by blacklist, whitelist, or trash. */
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'blacklist';

        if ($type === 'blacklist') {
            $sql1 = "SELECT p.ID, p.post_title, p.post_date, p.post_mime_type, u.size FROM " . $this->wp_posts . " AS p INNER JOIN " . $this->wmh_unused_media_post_id . " AS u ON p.ID = u.post_id";
        } elseif ($type === 'whitelist') {
            $sql1 = "SELECT p.ID, p.post_title, p.post_date, p.post_mime_type, u.size FROM " . $this->wp_posts . " AS p INNER JOIN " . $this->wmh_whitelist_media_post_id . " AS u ON p.ID = u.post_id";
        } elseif ($type === 'trash') {
            $sql1 = "SELECT ID, post_title, post_date, post_mime_type FROM " . $this->wp_posts . "";
        }

        if ($attachment_cat == 'images') {
            if ($date) {
                if ($type == 'trash') {
                    $sql1 .= " WHERE post_status = 'trash' AND post_type = 'attachment' AND post_mime_type LIKE '%image%' AND post_date LIKE '%" . $date . "%'";
                } else {
                    $sql1 .= " WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE '%image%' AND post_date LIKE '%" . $date . "%'";
                }
            } else {
                if ($type == 'trash') {
                    $sql1 .= " WHERE post_status = 'trash' AND post_type = 'attachment' AND post_mime_type LIKE '%image%'";
                } else {
                    $sql1 .= " WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE '%image%'";
                }
            }
        }

        if ($attachment_cat == 'documents') {
            if ($date) {
                if ($type == 'trash') {
                    $sql1 .= " WHERE post_status = 'trash' AND post_type = 'attachment' AND post_mime_type LIKE '%application%' AND post_date LIKE '%" . $date . "%'";
                } else {
                    $sql1 .= " WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE '%application%' AND post_date LIKE '%" . $date . "%'";
                }
            } else {
                if ($type == 'trash') {
                    $sql1 .= " WHERE post_status = 'trash' AND post_type = 'attachment' AND post_mime_type LIKE '%application%'";
                } else {
                    $sql1 .= " WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE '%application%'";
                }
            }
        }

        if ($attachment_cat == 'audio') {
            if ($date) {
                if ($type == 'trash') {
                    $sql1 .= " WHERE post_status = 'trash' AND post_type = 'attachment' AND post_mime_type LIKE '%audio%' AND post_date LIKE '%" . $date . "%'";
                } else {
                    $sql1 .= " WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE '%audio%' AND post_date LIKE '%" . $date . "%'";
                }
            } else {
                if ($type == 'trash') {
                    $sql1 .= " WHERE post_status = 'trash' AND post_type = 'attachment' AND post_mime_type LIKE '%audio%'";
                } else {
                    $sql1 .= " WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE '%audio%'";
                }
            }
        }

        if ($attachment_cat == 'video') {
            if ($date) {
                if ($type == 'trash') {
                    $sql1 .= " WHERE post_status = 'trash' AND post_type = 'attachment' AND post_mime_type LIKE '%video%' AND post_date LIKE '%" . $date . "%'";
                } else {
                    $sql1 .= " WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE '%video%' AND post_date LIKE '%" . $date . "%'";
                }
            } else {
                if ($type == 'trash') {
                    $sql1 .= " WHERE post_status = 'trash' AND post_type = 'attachment' AND post_mime_type LIKE '%video%'";
                } else {
                    $sql1 .= " WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE '%video%'";
                }
            }
        }

        if ($attachment_cat == 'others') {
            if ($date) {
                if ($type == 'trash') {
                    $sql1 .= " WHERE post_status = 'trash' AND post_type = 'attachment' AND post_mime_type NOT LIKE '%image%' AND post_mime_type NOT LIKE '%application%' AND post_mime_type NOT LIKE '%audio%' AND post_mime_type NOT LIKE '%video%' AND post_date LIKE '%" . $date . "%'";
                } else {
                    $sql1 .= " WHERE p.post_type = 'attachment' AND p.post_mime_type NOT LIKE '%image%' AND post_mime_type NOT LIKE '%application%' AND post_mime_type NOT LIKE '%audio%' AND post_mime_type NOT LIKE '%video%' AND post_date LIKE '%" . $date . "%'";
                }
            } else {
                if ($type == 'trash') {
                    $sql1 .= " WHERE post_status = 'trash' AND post_type = 'attachment' AND post_mime_type NOT LIKE '%image%' AND post_mime_type NOT LIKE '%application%' AND post_mime_type NOT LIKE '%audio%' AND post_mime_type NOT LIKE '%video%'";
                } else {
                    $sql1 .= " WHERE p.post_type = 'attachment' AND p.post_mime_type NOT LIKE '%image%' AND post_mime_type NOT LIKE '%application%' AND post_mime_type NOT LIKE '%audio%' AND post_mime_type NOT LIKE '%video%'";
                }
            }
        }

        if ($attachment_cat == 'all' || $attachment_cat == '') {
            if ($date) {
                if ($type == 'trash') {
                    $sql1 .= " WHERE post_status = 'trash' AND post_type = 'attachment' AND post_date LIKE '%" . $date . "%'";
                } else {
                    $sql1 .= " WHERE p.post_type = 'attachment' AND post_date LIKE '%" . $date . "%'";
                }
            } else {
                if ($type == 'trash') {
                    $sql1 .= " WHERE post_status = 'trash' AND post_type = 'attachment'";
                } else {
                    $sql1 .= " WHERE p.post_type = 'attachment'";
                }
            }
        }

        $posts_data_count = $this->conn->get_results($sql1, ARRAY_A);
        $count = count($posts_data_count);

        $sql1 .= 'LIMIT ' . $limit . ' OFFSET ' . $offset . '';
        $posts_data = $this->conn->get_results($sql1, ARRAY_A);


        if (!empty($posts_data)) {
            foreach ($posts_data as $post) {
                $p_id = $post['ID'];
                $p_title = $post['post_title'];
                $p_date = $post['post_date'];
                $post_mime_type = $post['post_mime_type'];
                if ($type == 'trash') {
                    $p_size = '-';
                } else {
                    $p_size = $post['size'];
                }
                $table_data[] = $this->fn_wmh_display_data($p_id, $p_title, $p_date, $post_mime_type, $p_size);
            }
        }

        if (isset($count) && $count != '' && $count != 0) {
            $table_data['count'] = $count;
        }

        return $table_data;
    }


    public function fn_wmh_count_blacklist_media()
    {

        $count = 0;
        $count = get_option('wmh_total_unused_media_count');
        return $count;
    }

    public function fn_wmh_count_whitelist_media()
    {
        $count = 0;
        $table_exists = $this->conn->get_var("SHOW TABLES LIKE '$this->wmh_whitelist_media_post_id'") == $this->wmh_whitelist_media_post_id;
        if ($table_exists) {
            $sql = 'SELECT count(id) as count FROM ' . $this->wmh_whitelist_media_post_id . '';
            $data = $this->conn->get_row($sql, ARRAY_A);
            if (isset($data['count'])) {
                if ($data['count'] != '' && $data['count'] != 0) {
                    $count = $data['count'];
                } else {
                    $module = 'Whitelist media';
                    $error = 'Whitelist media count not set';
                    $wmh_general = new wmh_general();
                    $wmh_general->fn_wmh_error_log($module, $error);
                }
            } else {
                $module = 'Whitelist media';
                $error = 'Whitelist media count not set';
                $wmh_general = new wmh_general();
                $wmh_general->fn_wmh_error_log($module, $error);
            }
        }
        return $count;
    }

    /* get register size for media. */
    public function fn_wmh_get_register_media_size()
    {
        $image_thumbnail_size = get_intermediate_image_sizes();
        return $image_thumbnail_size;
    }

    public function display()
    {
        $wmh_plugin_db_version_upgrade = get_option('wmh_plugin_db_version_upgrade');
        if (isset($wmh_plugin_db_version_upgrade) && $wmh_plugin_db_version_upgrade == '1') {
            parent::display();
        } else {
            $this->display_custom_message();
        }
    }

    public function display_custom_message()
    {

?>
        <div class="notice notice-warning wp-list-table-update-database-msg">
            <p><?php _e('Perform a database update within the WordPress environment to render and visualize tabular data, thereby ensuring the seamless integration of the most recent information into the database schema.', MEDIA_HYGIENE); ?></p>
        </div>
<?php

    }
}

$type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'blacklist';
$wmh_media_hygiene_view = new wmh_media_hygiene_view();
$wmh_media_hygiene_view->fn_wmh_header_view();
$wmh_media_hygiene_view->fn_wmh_first_load();
$wmh_media_hygiene_view->fn_wmh_heading_and_notice();
if ($type === 'blacklist') {
    $wmh_media_hygiene_view->fn_wmh_new_media_added_info();
    $wmh_media_hygiene_view->fn_wmh_dashboard_html();
    $wmh_media_hygiene_view->fn_wmh_scan_button_html();
} elseif ($type === 'trash') {
    $wmh_media_hygiene_view->fn_wmh_trash_action_view();
}
$wmh_media_hygiene_view->prepare_items();
$wmh_media_hygiene_view->fn_wmh_search_box_html();
$wmh_media_hygiene_view->display();
