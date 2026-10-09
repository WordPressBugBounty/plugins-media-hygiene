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

                /* monthly is also the default: an unset/empty timeframe lands here
                 * so sites that never touched Settings still get the summary email */
                if ($wmh_scan_option_data['wmh_timeframes'] == 'monthly' || $wmh_scan_option_data['wmh_timeframes'] == '') {
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

                if ($wmh_scan_option_data['wmh_timeframes'] == 'quarterly') {
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

        $sql = "SELECT ID, post_mime_type FROM $this->wp_posts WHERE post_type = %s AND post_status != %s AND post_date >= %s";
        $params = array('attachment', 'trash', $time_period);

        if (!empty($wmh_end_time)) {
            $sql .= ' AND post_date > %s';
            $params[] = $wmh_end_time;
        }

        $query = $this->conn->prepare($sql, $params);

        $attachments = $this->conn->get_results($query, ARRAY_A);
        return $attachments;
    }

    public function fn_wmh_cron_mail($attachments = array())
    {
        if (empty($attachments)) {
            return;
        }

        $total_count = count($attachments);
        $global_data = get_option('wmh_scan_option_data');
        if (isset($global_data) && isset($global_data['email_notification_send_to']) && $global_data['email_notification_send_to'] != '') {
            $to_raw = explode(',', $global_data['email_notification_send_to']);
            $to_raw = $this->fn_mh_trim_array($to_raw);
            $to = array_filter($to_raw, 'is_email');
            $to = array_values($to);
        } else {
            $to = get_option('admin_email');
        }
        $admin_email = get_option('admin_email');
        $site_title = get_bloginfo('name');

        /* The host, not the whole URL. A subject line is read in a narrow
         * column, often truncated, so the useful parts go first: how many, and
         * which site. The old subject said none of that. */
        $site_host = wp_parse_url(site_url(), PHP_URL_HOST);
        if (!$site_host) {
            $site_host = $site_title;
        }

        $subject = sprintf(
            /* translators: 1: number of files, 2: site host */
            _n(
                '%1$s new file on %2$s since your last scan',
                '%1$s new files on %2$s since your last scan',
                $total_count,
                MEDIA_HYGIENE
            ),
            number_format_i18n($total_count),
            $site_host
        );

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_title . ' <' . $admin_email . '>',
        );

        $dashboard_url = admin_url('admin.php?page=wmh-media-hygiene');
        $settings_url  = admin_url('admin.php?page=wmh-settings');

        /* Every style is inline. Gmail, Outlook and Yahoo strip or mangle a
         * <style> block, which is why this mail arrived unstyled. Layout is
         * tables for the same reason: Outlook renders through Word and has no
         * flexbox or grid. The old template also set body{background:red}
         * behind that stripped block, so it showed red wherever the block
         * survived. */
        $c_text   = '#1d2327';
        $c_muted  = '#646970';
        $c_line   = '#e0e0e0';
        $c_accent = '#2271b1';
        $c_bg     = '#f6f8fc';

        $rows = '';
        $shown = 0;
        /* Eight, not twenty. Rendering the old list showed twenty near
         * identical rows reads as a wall and buries the button below the fold,
         * and the point of the mail is the count and the action, not a
         * catalogue. The rest are one click away in the dashboard. */
        $limit = 8;
        foreach ($attachments as $at) {
            if ($shown >= $limit) {
                break;
            }
            $id = isset($at['ID']) ? (int) $at['ID'] : 0;
            if (!$id) {
                continue;
            }
            $post_mime_type = isset($at['post_mime_type']) ? $at['post_mime_type'] : '';
            $is_image = (strpos($post_mime_type, 'image') !== false);
            $guid = $is_image ? wp_get_original_image_url($id) : wp_get_attachment_url($id);
            if (!$guid) {
                continue;
            }

            $filename = basename(parse_url($guid, PHP_URL_PATH));
            $edit_url = get_edit_post_link($id, '');
            if (!$edit_url) {
                $edit_url = $guid;
            }

            /* A thumbnail only for images. Linking a PDF or a zip into an
             * <img> just renders a broken icon in every client. */
            if ($is_image) {
                /* The image is shown at 44px, so send the 150px thumbnail
                 * rather than the original. Pointing src at the full-size
                 * file made a mail with eight 4MB photos download about
                 * 32MB to paint eight 44px squares, which is slow on mobile
                 * data and counts against a sending quota.
                 *
                 * image_downsize falls back to the full-size URL when the
                 * size does not exist, so an attachment with no generated
                 * thumbnail behaves as it does now rather than rendering a
                 * broken image. The LINK stays on $guid: a reader clicking
                 * the picture wants the real file. */
                $thumb_src = wp_get_attachment_image_url($id, 'thumbnail');
                if (!$thumb_src) {
                    $thumb_src = $guid;
                }
                $thumb = '<a href="' . esc_url($guid) . '" target="_blank" style="text-decoration:none;">'
                    . '<img src="' . esc_url($thumb_src) . '" width="44" height="44" alt="' . esc_attr($filename) . '"'
                    . ' style="display:block;width:44px;height:44px;object-fit:cover;border-radius:4px;border:1px solid ' . $c_line . ';" /></a>';
            } else {
                $ext = strtoupper(pathinfo($filename, PATHINFO_EXTENSION));
                $thumb = '<span style="display:inline-block;width:44px;height:44px;line-height:44px;text-align:center;'
                    . 'background:' . $c_bg . ';border:1px solid ' . $c_line . ';border-radius:4px;'
                    . 'font-size:10px;font-weight:bold;color:' . $c_muted . ';">' . esc_html($ext) . '</span>';
            }

            $rows .= '<tr>'
                . '<td style="padding:10px 12px;border-bottom:1px solid ' . $c_line . ';vertical-align:middle;width:44px;">'
                . $thumb . '</td>'
                . '<td style="padding:10px 12px;border-bottom:1px solid ' . $c_line . ';vertical-align:middle;'
                . 'font-family:Arial,Helvetica,sans-serif;font-size:14px;color:' . $c_text . ';word-break:break-all;">'
                . '<a href="' . esc_url($edit_url) . '" target="_blank" style="color:' . $c_accent . ';text-decoration:none;">'
                . esc_html($filename) . '</a>'
                . '</td>'
                . '</tr>';
            $shown++;
        }

        if ($rows === '') {
            return;
        }

        $remaining = $total_count - $shown;

        /* a bulletproof-ish button: a padded table cell, not a <button> inside
         * an <a>, which Outlook does not render as a control at all */
        $button = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">'
            . '<tr><td style="background:' . $c_accent . ';border-radius:4px;">'
            . '<a href="' . esc_url($dashboard_url) . '" target="_blank"'
            . ' style="display:inline-block;padding:12px 28px;font-family:Arial,Helvetica,sans-serif;'
            . 'font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;">'
            . esc_html__('Scan for unused files', MEDIA_HYGIENE)
            . '</a></td></tr></table>';

        /* The heading already says how many files on which site, so repeating
         * that as a sentence only pushes the list further down. What the
         * heading cannot carry is WHEN: without a date, "since your last scan"
         * could mean yesterday or last year. */
        $last_scan_label = '';
        $last_scan_raw = get_option('wmh_end_time');
        if (!empty($last_scan_raw)) {
            $last_scan_time = is_numeric($last_scan_raw) ? (int) $last_scan_raw : strtotime($last_scan_raw);
            if ($last_scan_time) {
                $last_scan_label = sprintf(
                    /* translators: %s: date of the last scan */
                    esc_html__('Last scan: %s', MEDIA_HYGIENE),
                    esc_html(date_i18n(get_option('date_format'), $last_scan_time))
                );
            }
        }
        $intro = $last_scan_label;

        $message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
            . '<html xmlns="http://www.w3.org/1999/xhtml"><head>'
            . '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0" />'
            . '<title>' . esc_html($subject) . '</title>'
            . '</head>'
            . '<body style="margin:0;padding:0;background:' . $c_bg . ';">'

            /* preheader: the grey line a client shows next to the subject.
               Hidden in the body itself. */
            . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">'
            . esc_html__('A quick look at what is new, so nothing piles up unnoticed.', MEDIA_HYGIENE)
            . '</div>'

            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:' . $c_bg . ';">'
            . '<tr><td align="center" style="padding:24px 12px;">'

            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"'
            . ' style="width:100%;max-width:600px;background:#ffffff;border:1px solid ' . $c_line . ';border-radius:6px;">'

            /* header */
            . '<tr><td style="padding:28px 28px 8px 28px;font-family:Arial,Helvetica,sans-serif;">'
            . '<div style="font-size:12px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase;color:' . $c_muted . ';">'
            . esc_html__('Media Hygiene', MEDIA_HYGIENE) . '</div>'
            . '<h1 style="margin:8px 0 0 0;font-size:22px;line-height:1.3;color:' . $c_text . ';font-weight:bold;">'
            . esc_html($subject) . '</h1>'
            . '</td></tr>'

            /* last scan date, small and grey: context, not a headline */
            . ($intro !== ''
                ? '<tr><td style="padding:6px 28px 0 28px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:' . $c_muted . ';">'
                    . $intro . '</td></tr>'
                : '')

            /* file list */
            . '<tr><td style="padding:20px 28px 0 28px;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"'
            . ' style="border:1px solid ' . $c_line . ';border-radius:4px;border-collapse:separate;">'
            . $rows
            . '</table>';

        if ($remaining > 0) {
            $message .= '<p style="margin:12px 0 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:' . $c_muted . ';">'
                . sprintf(
                    /* translators: %s: number of additional files */
                    esc_html(_n('and %s more file', 'and %s more files', $remaining, MEDIA_HYGIENE)),
                    esc_html(number_format_i18n($remaining))
                )
                . '</p>';
        }

        $message .= '</td></tr>'

            /* action */
            . '<tr><td style="padding:24px 28px 8px 28px;" align="center">' . $button . '</td></tr>'
            . '<tr><td style="padding:0 28px 24px 28px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;color:' . $c_muted . ';" align="center">'
            . esc_html__('New files are not unused files. A scan checks where each one is actually referenced.', MEDIA_HYGIENE)
            . '</td></tr>'

            /* footer */
            . '<tr><td style="padding:16px 28px;border-top:1px solid ' . $c_line . ';'
            . 'font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:' . $c_muted . ';">'
            . sprintf(
                /* translators: 1: site name, 2: opening link tag, 3: closing link tag */
                esc_html__('Sent by Media Hygiene on %1$s. %2$sChange how often you get this%3$s.', MEDIA_HYGIENE),
                esc_html($site_title),
                '<a href="' . esc_url($settings_url) . '" style="color:' . $c_accent . ';">',
                '</a>'
            )
            . '</td></tr>'

            . '</table>'
            . '</td></tr></table>'
            . '</body></html>';

        wp_mail($to, $subject, $message, $headers);
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
            'display'  => esc_html__('Media Hygiene BiWeekly', MEDIA_HYGIENE),
        );

        /* Media Hygiene Monthly */
        $schedules['mediahygiene_monthly'] = array(
            'interval' => 2592000,
            'display'  => esc_html__('Media Hygiene Monthly', MEDIA_HYGIENE),
        );
        /* Media Hygiene Quarterly */
        $schedules['mediahygiene_quarterly'] = array(
            'interval' => 7776000,
            'display'  => esc_html__('Media Hygiene Quarterly', MEDIA_HYGIENE),
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