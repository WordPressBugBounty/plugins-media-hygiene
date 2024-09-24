<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

class my_cron_job
{

    public $conn;
    public $wp_posts;
    public $wp_postmeta;
    public $wmh_unused_media_post_id;
    public $wmh_whitelist_media_post_id;
    public $wp_upload_dir;
    public $basedir;
    public $wmh_temp;
    public $wmh_deleted_media;
    public $wmh_used_media_post_id;
    public $wmh_save_scan_content;

    public function __construct()
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
        $this->wmh_deleted_media = $this->conn->prefix . MH_PREFIX . 'deleted_media';
        $this->wmh_used_media_post_id = $this->conn->prefix . MH_PREFIX . 'used_media_post_id';
        $this->wmh_save_scan_content = $this->conn->prefix . MH_PREFIX . 'save_scan_content';

        add_action('init', array($this, 'fn_mh_schedule_cron_jobs'));
        add_filter('cron_schedules', array($this, 'fn_mh_add_cron_interval'));

    }

    public function fn_mh_schedule_cron_jobs()
    {
        /* get scan status */
        $wmh_scan_status = get_option('wmh_scan_status');

        if (isset($wmh_scan_status) && $wmh_scan_status == '1' && $wmh_scan_status != '' && $wmh_scan_status != '0') {

            /* get scan option data. */
            $wmh_scan_option_data = get_option('wmh_scan_option_data', true);

            if (isset($wmh_scan_option_data['wmh_timeframes']) && $wmh_scan_option_data['wmh_timeframes'] != 'none') {

                if ($wmh_scan_option_data['wmh_timeframes'] == 'daily') {
                    /* daily cron job */
                    if (!wp_next_scheduled('fn_mh_daily_cron_job')) {
                        wp_schedule_event(time(), 'daily', 'fn_mh_daily_cron_job');

                        wp_clear_scheduled_hook('fn_mh_weekly_cron_job');
                        wp_clear_scheduled_hook('fn_mh_biweekly_cron_job');
                        wp_clear_scheduled_hook('fn_mh_monthly_cron_job');
                        wp_clear_scheduled_hook('fn_mh_quarterly_cron_job');
                    }
                    add_action('fn_mh_daily_cron_job', array($this, 'fn_mh_daily_cron_job_callback'));
                }

                if ($wmh_scan_option_data['wmh_timeframes'] == 'weekly') {
                    /* weekly cron job */
                    if (!wp_next_scheduled('fn_mh_weekly_cron_job')) {
                        wp_schedule_event(time(), 'weekly', 'fn_mh_weekly_cron_job');

                        wp_clear_scheduled_hook('fn_mh_daily_cron_job');
                        wp_clear_scheduled_hook('fn_mh_biweekly_cron_job');
                        wp_clear_scheduled_hook('fn_mh_monthly_cron_job');
                        wp_clear_scheduled_hook('fn_mh_quarterly_cron_job');
                    }
                    add_action('fn_mh_weekly_cron_job', array($this, 'fn_mh_weekly_cron_job_callback'));
                }

                if ($wmh_scan_option_data['wmh_timeframes'] == 'biweekly') {
                    /* quarterly cron job */
                    if (!wp_next_scheduled('fn_mh_biweekly_cron_job')) {
                        wp_schedule_event(time(), 'mediahygiene_bikweekly', 'fn_mh_biweekly_cron_job');

                        wp_clear_scheduled_hook('fn_mh_daily_cron_job');
                        wp_clear_scheduled_hook('fn_mh_weekly_cron_job');
                        wp_clear_scheduled_hook('fn_mh_monthly_cron_job');
                        wp_clear_scheduled_hook('fn_mh_quarterly_cron_job');
                    }
                    add_action('fn_mh_biweekly_cron_job', array($this, 'fn_mh_biweekly_cron_job_callback'));
                }

                if ($wmh_scan_option_data['wmh_timeframes'] == 'monthly') {
                    /* monthly cron job */
                    if (!wp_next_scheduled('fn_mh_monthly_cron_job')) {
                        wp_schedule_event(time(), 'mediahygiene_monthly', 'fn_mh_monthly_cron_job');

                        wp_clear_scheduled_hook('fn_mh_daily_cron_job');
                        wp_clear_scheduled_hook('fn_mh_weekly_cron_job');
                        wp_clear_scheduled_hook('fn_mh_biweekly_cron_job');
                        wp_clear_scheduled_hook('fn_mh_quarterly_cron_job');
                    }
                    add_action('fn_mh_monthly_cron_job', array($this, 'fn_mh_monthly_cron_job_callback'));
                }

                if ($wmh_scan_option_data['wmh_timeframes'] == 'quarterly' || $wmh_scan_option_data['wmh_timeframes'] == '') {
                    /* quarterly cron job */
                    if (!wp_next_scheduled('fn_mh_quarterly_cron_job')) {
                        wp_schedule_event(time(), 'mediahygiene_quarterly', 'fn_mh_quarterly_cron_job');

                        wp_clear_scheduled_hook('fn_mh_daily_cron_job');
                        wp_clear_scheduled_hook('fn_mh_weekly_cron_job');
                        wp_clear_scheduled_hook('fn_mh_biweekly_cron_job');
                        wp_clear_scheduled_hook('fn_mh_monthly_cron_job');
                    }
                    add_action('fn_mh_quarterly_cron_job', array($this, 'fn_mh_quarterly_cron_job_callback'));
                }
            } else {

                wp_clear_scheduled_hook('fn_mh_daily_cron_job');
                wp_clear_scheduled_hook('fn_mh_biweekly_cron_job');
                wp_clear_scheduled_hook('fn_mh_weekly_cron_job');
                wp_clear_scheduled_hook('fn_mh_monthly_cron_job');
                wp_clear_scheduled_hook('fn_mh_quarterly_cron_job');
            }
        }
    }

    /* daily cron job */
    public function fn_mh_daily_cron_job_callback()
    {
        $time = '-1 days';
        $attachments = $this->fn_wmh_get_data_by_time($time);
        /* email */
        $this->fn_wmh_cron_mail($attachments);
    }

    /* weekly cron job */
    public function fn_mh_weekly_cron_job_callback()
    {
        $time = '-7 days';
        $attachments = $this->fn_wmh_get_data_by_time($time);

        $this->fn_wmh_cron_mail($attachments);
    }

    public function fn_mh_biweekly_cron_job_callback()
    {
        $time = '-15 days';
        $attachments = $this->fn_wmh_get_data_by_time($time);

        $this->fn_wmh_cron_mail($attachments);
    }

    /* monthly cron job */
    public function fn_mh_monthly_cron_job_callback()
    {
        $time = '-30 days';
        $attachments = $this->fn_wmh_get_data_by_time($time);
        /* email */
        $this->fn_wmh_cron_mail($attachments);
    }

    /* quarterly cron job */
    public function fn_mh_quarterly_cron_job_callback()
    {
        $time = '-90 days';
        $attachments = $this->fn_wmh_get_data_by_time($time);
        /* email */
        $this->fn_wmh_cron_mail($attachments);
    }

    public function fn_wmh_get_data_by_time($time = '')
    {
        /* get scan end time */
        $wmh_end_time = get_option('wmh_end_time');
        $time_period = date('Y-m-d', strtotime($time));
        $attachments = array();
        $query = $this->conn->prepare(
            "
            SELECT ID, post_mime_type 
            FROM $this->wp_posts
            WHERE post_type = %s 
            AND post_status != %s 
            AND post_date >= %s
            AND post_date > %s
            ",
            'attachment',
            'trash',
            $time_period,
            $wmh_end_time
        );


        $attachments = $this->conn->get_results($query, ARRAY_A);
        return $attachments;
    }

    public function fn_wmh_cron_mail($attachments = array())
    {

        $total_count = count($attachments);
        $global_data = get_option('wmh_scan_option_data');
        if(isset($global_data) && isset($global_data['email_notification_send_to']) && $global_data['email_notification_send_to'] != ''){
            $to = explode(',', $global_data['email_notification_send_to']);            
            $to = $this->fn_mh_trim_array($to);            
        }else{
            $to = get_option('admin_email');
        }
        $admin_email = get_option('admin_email');
        
        $subject = 'Media Hygiene: Recent Files Uploaded Notification';
        $site_title = get_bloginfo( 'name' );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: '.$site_title.' <'.$admin_email.'>'
        );
        $message = '';
        $message = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Email Template</title>
            <style>
                body {
                    background-color: red !important; /* Grey background */
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    background-color: #f6f8fc; /* White container */
                    color: #000000; /* Black text */
                    padding: 20px;
                    max-width: 800px;
                    margin: 0 auto; /* Center the container */
                    box-sizing: border-box; /* Ensure padding doesn\'t increase container width */
                }
                h1 {
                    color: #000000; /* Black heading */
                    text-align: center;
                }
                p {
                    text-align: center;
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                th, td {
                    border: 1px solid #dddddd;
                    text-align: left;
                    padding: 8px;
                }
                th {
                    background-color: #f2f2f2; /* Light grey background for table header */
                }
        
                /* Responsive Styles */
                @media screen and (max-width: 600px) {
                    .container {
                        padding: 10px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">';
        $site_url_mh = site_url() . '/wp-admin/admin.php?page=wmh-media-hygiene';
        $message .= '<h1>Recent Files Uploaded ' . date('Y-m-d') . '</h1>';
        $message .= '<p>Media Hygiene has detected that <strong>' . $total_count . '</strong> files have been uploaded to your <a href="' . site_url() . '" target="_blank">' . site_url() . '</a> Media Library since the last scan</p>';
        $message .= '<p>Keep your website\'s media library lean and clean!</p>';
        $message .= '<div style="text-align: center;margin: 20px auto;"><a href="' . $site_url_mh . '" target="_blank"><button type="button" style="background-color: blue;color: white;padding: 10px;border: 1px blue solid;font-weight: 900;cursor:pointer !important;">Access Dashboard!</button></a></div>';
        $message .= '<table>';
        $message .= '<thead>';
        $message .= '<tr>';
        $message .= '<th>No</th>';
        $message .= '<th>URL</th>';
        $message .= '<th>IMAGE</th>';
        $message .= '</tr>';
        $message .= '</thead>';
        $message .= '<tbody>';
        if (!empty($attachments)) {
            $i = 1;
            foreach ($attachments as $key => $at) {
                if ($i <= 20) {
                    $no = $key + 1;
                    $id = $at['ID'];
                    $post_mime_type = $at['post_mime_type'];
                    if (str_contains($post_mime_type, 'image')) {
                        $guid = wp_get_original_image_url($id);
                    } else {
                        $guid = wp_get_attachment_url($id);
                    }
                    $message .= '<tr>';
                    $message .= '<td>' . $no . '</td>';
                    $message .= '<td>' . $guid . '</td>';
                    $message .= '<td><a href="' . $guid . '" target="_blank"><img src="' . $guid . '" width="50" height="50"/></a></td>';
                    $message .= '</tr>';
                    $i++;
                } else {
                    break;
                }
            }
            $message .= '</tbody>';
            $message .= '</table>';
			$message .= '<div style="text-align: center;margin: 20px auto;"><a href="' . $site_url_mh . '" target="_blank"><button type="button" style="background-color: blue;color: white;padding: 10px;border: 1px blue solid;font-weight: 900;cursor:pointer !important;">Scan for Unused Images Now</button></a></div>';
            $message .= '<p>Share this awesome plugin if you find it useful.</p>';
            $message .= '<p>Help us grow this plugin by writing a review <a href="https://wordpress.org/plugins/media-hygiene/#reviews" target="_blank">here</a></p>';
			
            $message .=    '</div>';
            wp_mail($to, $subject, $message, $headers);
        }
    }

    public function log_mailer_errors($wp_error)
    {
        error_log(json_encode($wp_error));
    }


    public function fn_mh_add_cron_interval($schedules)
    {

        /* Media Hygiene Monthly */
        $schedules['mediahygiene_bikweekly'] = array(
            'interval' => 1296000,
            'display'  => esc_html__('Media Hygiene BiWeekly'),
        );

        /* Media Hygiene Monthly */
        $schedules['mediahygiene_monthly'] = array(
            'interval' => 2592000,
            'display'  => esc_html__('Media Hygiene Monthly'),
        );
        /* Media Hygiene Quarterly */
        $schedules['mediahygiene_quarterly'] = array(
            'interval' => 7776000,
            'display'  => esc_html__('Media Hygiene Quarterly'),
        );

        return $schedules;
    }

    public function fn_mh_trim_array($to) {
        $data = array();
        foreach($to as $t){
            $data[] = trim($t);
        }
        return $data;
    }
}

$my_cron_job = new my_cron_job();