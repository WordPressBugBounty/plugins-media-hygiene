<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class wmh_download_unused_media
{

    public $conn;
    public $wp_posts;
    public $wmh_unused_media_post_id;
    public $wmh_whitelist_media_post_id;

    public function __construct()
    {
        global $wpdb;
        $this->conn = $wpdb;
        $this->wp_posts = $this->conn->prefix . 'posts';
        $this->wmh_unused_media_post_id = $this->conn->prefix . MH_PREFIX . 'unused_media_post_id';
        $this->wmh_whitelist_media_post_id = $this->conn->prefix . MH_PREFIX . 'whitelist_media_post_id';
        /* dowanload page media action */
        add_action('admin_post_create_page_unused_media_zip_action', array($this, 'fn_wmh_create_page_unused_media_zip_action'));
    }

    public function fn_wmh_create_page_unused_media_zip_action()
    {   
        if (!current_user_can('manage_options')) {
			return false;
		}

        /* check nonce */
        $nonce = sanitize_text_field($_POST['nonce']);
        if (!wp_verify_nonce($nonce, 'create_page_unused_media_zip_nonce')) {
            die(esc_html(__('Security check. Hacking not allowed', MEDIA_HYGIENE)));
        }

        if (!extension_loaded('zip')) {
            $redirect_url = admin_url() . 'admin.php?page=wmh-media-hygiene';
            $display_message = esc_html(__('Apologies, but your server does not have the necessary ZIP extension installed, resulting in the unavailability of the required functionality. Please ask your system administrator to install zip extension in your server to use this feature.', MEDIA_HYGIENE));
?>
            <script type="text/javascript">
                let redirectUrl = '<?php echo esc_js($redirect_url) ?>';
                let displayMessage = '<?php echo esc_js($display_message); ?>';
                alert(displayMessage);
                window.location.href = redirectUrl;
            </script>
<?php
            die();
        }

        /* get scan option data. */
        $wmh_scan_option_data = get_option('wmh_scan_option_data', true);
        $media_per_page_input = 10;
        if (isset($wmh_scan_option_data['media_per_page_input']) && ($wmh_scan_option_data['media_per_page_input'] != '' || $wmh_scan_option_data['media_per_page_input'] != 0)) {
            $media_per_page_input = $wmh_scan_option_data['media_per_page_input'];
        }

        $per_post = $media_per_page_input;
        $current_page = sanitize_text_field($_POST['paged']);
        $offset = $per_post * ($current_page - 1);

        $paged = sanitize_text_field($_POST['paged']);

        /* get unused media result */
        $unused_media_result = $this->fn_wmh_get_unused_media_result($offset, $per_post);
        /* creating zip */
        $zip = new ZipArchive;
        /* file name */
        $filename = 'wmh-unused-media-' . date('Y-m-d') . '-paged-' . $paged . '.zip';
        /* get basedir */
        $wp_get_upload_dir = wp_get_upload_dir();
        $basedir = $wp_get_upload_dir['basedir'];
        $media_hygiene_dir = 'media-hygiene-page-media';
        $dir = $basedir . '/' . $media_hygiene_dir;
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        $dowanload_unused_media_file = $dir . '/' . $filename;
        $zip->open($dowanload_unused_media_file, ZipArchive::CREATE);
        if (isset($unused_media_result) && !empty($unused_media_result)) {
            foreach ($unused_media_result as $file) {
                $fileContent = file_get_contents($file);
                if ($fileContent) {
                    $zip->addFromString(basename($file), $fileContent);
                }
            }
        } else {
            $module = 'Download unused media';
            $error = 'unused deleted data not set for creatind zip for download page media';
            $wmh_general = new wmh_general();
            $wmh_general->fn_wmh_error_log($module, $error);
        }
        $zip->close();

        $checked_file_path = $basedir . '/' . $media_hygiene_dir . '/' . $filename . '';
        if (file_exists($checked_file_path)) {
            if (function_exists('disk_free_space')) {
                    $dir_space = disk_free_space($basedir);
                } else {
                    $goback_url = sanitize_url(admin_url('admin.php?page=wmh-media-hygiene'));
                    echo '<a href="' . esc_url($goback_url) . '">' . esc_html(__('Go back', MEDIA_HYGIENE)) . '</a><br><br>';
                    die(esc_html__('The disk space check function is not available on this server. This may be due to hosting restrictions or disabled system functions. Please contact your hosting provider for assistance.', MEDIA_HYGIENE));
                }
            $filesize = filesize($checked_file_path);
            if ($filesize < $dir_space) {
                header('Content-type: application/zip');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                readfile($checked_file_path);
                unlink($checked_file_path);
            } else {
                $goback_url = sanitize_url(admin_url('admin.php?page=wmh-media-hygiene'));
                echo '<a href="' . esc_url($goback_url) . '">' . esc_html(__('Go back', MEDIA_HYGIENE)) . '</a><br><br>';
                die(esc_html(__('Not enough space available to download page media, please go back.', MEDIA_HYGIENE)));
            }
        }
    }

    public function fn_wmh_get_unused_media_result($offset = '', $per_post = '')
    {

        /* get all unused delete media post id from wmh_unused_media_post_id table for download media */
        $unused_media_sql = 'SELECT post_id FROM ' . $this->wmh_unused_media_post_id . ' LIMIT ' . $per_post . ' OFFSET ' . $offset . ' ';
        $unused_media_data = $this->conn->get_results($unused_media_sql, ARRAY_A);
        $unused_media_post_id = array();
        if (isset($unused_media_data) && !empty($unused_media_data)) {
            foreach ($unused_media_data as $unused_media) {
                if ($unused_media['post_id']) {
                    $post_id = $unused_media['post_id'];
                    array_push($unused_media_post_id,  $post_id);
                }
            }
        } else {
            $module = 'Download unused media';
            $error = 'unused deleted post id not set for download page media';
            $wmh_general = new wmh_general();
            $wmh_general->fn_wmh_error_log($module, $error);
        }

        $original_url_array = array();
        if (!empty($unused_media_post_id)) {
            $id = implode(',', $unused_media_post_id);
            $get_original_url_sql = 'SELECT ID, guid, post_mime_type FROM ' . $this->wp_posts . ' WHERE ID IN(' . $id . ')';
            $get_original_url_data = $this->conn->get_results($get_original_url_sql, ARRAY_A);
            if (isset($get_original_url_data) && !empty($get_original_url_data)) {
                foreach ($get_original_url_data as $guid) {
                    /* get post mime type */
                    $post_mime_type = sanitize_mime_type($guid['post_mime_type']);
                    if (str_contains($post_mime_type, 'image')) {
                        $guid_url = wp_get_original_image_url($guid['ID']);
                    } else {
                        $guid_url = wp_get_attachment_url($guid['ID']);
                    }
                    if (isset($guid_url) && $guid_url != '') {
                        $guid_url = sanitize_url($guid_url);
                        array_push($original_url_array,  $guid_url);
                    }
                }
            } else {
                $module = 'Download unused media';
                $error = 'unused deleted data not set for download page media';
                $wmh_general = new wmh_general();
                $wmh_general->fn_wmh_error_log($module, $error);
            }
        } else {
            $module = 'Download unused media';
            $error = 'unused deleted post id not set for download page media';
            $wmh_general = new wmh_general();
            $wmh_general->fn_wmh_error_log($module, $error);
        }
        return $original_url_array;
    }
}

$wmh_download_unused_media = new wmh_download_unused_media();
