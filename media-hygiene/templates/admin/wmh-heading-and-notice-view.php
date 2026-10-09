<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');


/* get scan status */
$scan_status = get_option('wmh_scan_status');
$wmh_scan_status_new = get_option('wmh_scan_status_new');

/* The list of plugins whose media storage the free version cannot read used
 * to live here, feeding a dashboard notice. That notice is gone (see below),
 * so the list, the get_plugins() call that fed it and the $all_plugins_keys
 * it was matched against were all removed with it: nothing on this screen
 * reads them now, and get_plugins() is not free on every dashboard load.
 *
 * The authoritative version of that list is the compatibility table on the
 * Settings screen, which builds from its own get_plugins() call. Coverage
 * gaps are now reported from a real count, in
 * fn_wmh_count_unreadable_references() in includes/wmh-general.php. */



/* Get data about permission checkbox */
$permission_for_send_data = get_option('wmh_send_data_to_server_permission');

/* permanently close anonymous analytics permission */
$wmh_close_analytics_permission_permanently = get_option('wmh_close_analytics_permission_permanently');

/* get wmh_database_version  */
$wmh_plugin_db_version = get_option('wmh_plugin_db_version');
$wmh_plugin_db_version_upgrade = get_option('wmh_plugin_db_version_upgrade');

?>
<div class="row row-main mt-2">
    <div class="col-md-12 px-0">

        <!-- database version alert -->
        <?php if (isset($wmh_plugin_db_version) && ($wmh_plugin_db_version == '2.0.0' || $wmh_plugin_db_version <= '2000') && $wmh_plugin_db_version_upgrade == false && $wmh_plugin_db_version_upgrade != 1) {  ?>

            <div class="notice notice-alert notice-error mb-0">
                <p>
                    <strong>
                        <?php _e('Media Hygiene database update required.', MEDIA_HYGIENE); ?>
                    </strong>
                </p>
                <p>
                    <?php _e('Media Hygiene has been updated! To keep things running smoothly, we have to update your database to the newest version. The database process will run instantly and may not take time. The database process will only check for plugin tables and will not effect any other tables. If you don\'t see scan button, please upgrade the Media Hygiene Database to enable. So please be patience.', MEDIA_HYGIENE); ?>
                </p>
                <p>
                <form id="wmh-database-update-from">
                    <input type="hidden" name="action" value="database_update_wmh_by_version">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('database_update_wmh_by_version_nonce')); ?>">
                    <button class="button button-primary wmh-update-database-btn-highlight" id="wmh-update-database">
                        <i class="fa-solid fa-spinner fa-spin wmh-update-database-loader" style="display:none;"></i>&nbsp;
                        <?php _e('Update Media Hygiene Database', MEDIA_HYGIENE); ?>
                    </button>
                </form>
                </p>
            </div>

        <?php }  ?>

        <!-- Backup warning. Stays on every load and cannot be dismissed: it is
             the one notice that prevents an irreversible mistake.
             It used to carry a review ask and "Upgrade to Pro to dismiss this
             notice" as well. That made the only way to silence a safety warning
             a purchase, so it read as a paywalled nag and taught people to skip
             the box, warning included. It also ran to two lines and repeated the
             review link from the dismissible notice further down, which showed
             on screen at the same time. Warning only now, so it reads as one. -->
        <div class="notice notice-warning wmh-notice-backup mb-0">
            <p>
                <span class="wmh-notice-icon"><i class="fa-solid fa-shield-halved"></i></span>
                <b><?php _e('Always backup before making changes.', MEDIA_HYGIENE); ?></b>
                <?php esc_html_e('The free version finds media by reading your posts and pages. Files used somewhere else, such as in a theme option, a plugin field or a store product, can still be listed as unused. Review items carefully before deleting.', MEDIA_HYGIENE); ?>
            </p>
        </div>

        <!-- scan status notice -->
        <?php if (isset($scan_status) && ($scan_status == '0' || $scan_status == '')) { ?>
            <div class="notice notice-info wmh-notice-scan mb-0">
                <p>
                    <span class="wmh-notice-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <b><?php _e('No scan has been run yet.', MEDIA_HYGIENE); ?></b>
                    <?php _e('Click the <strong>Scan</strong> button to analyze your media library. Scan time depends on your server and the number of media files. Please be patient and do not close the window until it completes.', MEDIA_HYGIENE); ?>
                </p>
            </div>
        <?php } ?>
        <!-- Upgrade Notice -->
        <?php if (isset($wmh_scan_status_new) && ($wmh_scan_status_new == '0' || $wmh_scan_status_new == '') && get_option('wmh_database_version') == '1000') { ?>
            <div class="notice notice-info wmh-notice-db mb-0">
                <p>
                    <span class="wmh-notice-icon"><i class="fa-solid fa-rotate"></i></span>
                    <b><?php _e('A new scan is recommended after the recent database upgrade.', MEDIA_HYGIENE); ?></b>
                    <?php _e('The plugin database has been updated. Running a fresh scan ensures everything is detected correctly. You can continue using the plugin in the meantime.', MEDIA_HYGIENE); ?>
                </p>
            </div>
        <?php } ?>
        <!-- Unreadable media references: measured, not guessed -->
        <?php
        /* Only after a scan. This warns about files that can show up in the
         * unused list, and before the first scan there is no list to check
         * against: every figure on the page reads 0. Shown then, it is a
         * warning about nothing the user can act on yet, stacked under a
         * notice telling them to run a scan. It also saves the query on a
         * page where the answer cannot be used. */
        $wmh_scan_has_run = !(isset($scan_status) && ($scan_status == '0' || $scan_status == ''));

        $wmh_unreadable = array('sources' => array(), 'total' => 0);
        if ($wmh_scan_has_run) {
            $wmh_notice_general = new wmh_general();
            $wmh_unreadable = $wmh_notice_general->fn_wmh_count_unreadable_references();
        }

        if (!empty($wmh_unreadable['sources'])) {
            /* Two different claims, kept apart on purpose. A counted VALUE is a
             * stored reference we can point at. A counted FIELD is only a place
             * media can be stored, so it is described as a field and never added
             * to the reference total. Merging them would put a confident number
             * on something we did not actually count. */
            $wmh_value_labels = array();
            $wmh_field_labels = array();
            foreach ($wmh_unreadable['sources'] as $wmh_src_label => $wmh_src) {
                $wmh_src_count = (int) $wmh_src['count'];
                $wmh_src_text  = esc_html($wmh_src_label) . ' (' . esc_html(number_format_i18n($wmh_src_count)) . ')';
                if (isset($wmh_src['unit']) && $wmh_src['unit'] === 'fields') {
                    $wmh_field_labels[] = $wmh_src_text;
                } else {
                    $wmh_value_labels[] = $wmh_src_text;
                }
            }
        ?>
            <div class="notice notice-warning wmh-notice-compat mb-0">
                <p>
                    <span class="wmh-notice-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <?php if (!empty($wmh_value_labels)) { ?>
                        <b><?php
                            printf(
                                /* translators: %s: number of references */
                                esc_html(_n(
                                    'This scan could not read %s media reference.',
                                    'This scan could not read %s media references.',
                                    $wmh_unreadable['total'],
                                    MEDIA_HYGIENE
                                )),
                                esc_html(number_format_i18n($wmh_unreadable['total']))
                            );
                        ?></b>
                        <?php
                        printf(
                            /* translators: %s: plugin names with counts */
                            esc_html__('They are stored by %s, where the free scan does not reach. The free version reads posts and pages, so media held as plugin field values, or attached to other content types such as store products, is not counted as in use.', MEDIA_HYGIENE),
                            implode(', ', $wmh_value_labels)
                        );
                        ?>
                    <?php } ?>
                    <?php if (!empty($wmh_field_labels)) { ?>
                        <b><?php
                            esc_html_e('Some media on this site is stored where the free version cannot read it.', MEDIA_HYGIENE);
                        ?></b>
                        <?php
                        printf(
                            /* translators: %s: plugin names with the number of media fields set up */
                            esc_html__('This site has media fields set up in %s, and the free version cannot follow what is saved in them.', MEDIA_HYGIENE),
                            implode(', ', $wmh_field_labels)
                        );
                        ?>
                    <?php } ?>
                    <?php esc_html_e('Files used only in those places appear in the unused list below and look safe to remove. Check them against your site before deleting anything.', MEDIA_HYGIENE); ?>
                    <a href="<?php echo wmh_site_link('features', 'notice-unreadable'); ?>" target="_blank"><?php esc_html_e('See how Media Hygiene Pro reads these.', MEDIA_HYGIENE); ?></a>
                </p>
            </div>
        <?php }
        /* No branch for "a supported plugin is present but we counted nothing".
         *
         * That state used to print "We found no unreadable references on this
         * site, so your results are most likely complete." It was removed
         * deliberately, and it should not be added back.
         *
         * Counting nothing is not the same as there being nothing. The counters
         * above read what is stored in the database: ACF field DEFINITIONS, and
         * saved values for the others. Media can sit outside every one of them.
         * ACF field groups registered in theme or plugin code with
         * acf_add_local_field_group(), and groups kept as acf-json files in the
         * theme, never reach the posts table, so a site built entirely that way
         * counts zero and is not covered at all. Rendered output, shortcodes and
         * dynamic tags are not followed either.
         *
         * So a zero count means "we found no gap we know how to measure", which
         * is a statement about our counters, not about the site. Printing it as
         * reassurance puts a completeness claim directly above a delete list,
         * which is the one place a wrong answer costs the user a live file.
         *
         * Showing nothing is correct here: the gap notices above fire when there
         * is something real to report, and silence carries no claim either way. */
        ?>

        <!-- Scan completeness: report items the scan had to skip -->
        <?php
        $wmh_skipped_posts = (int) get_option('wmh_scan_skipped_posts', 0);
        $wmh_skipped_atts  = (int) get_option('wmh_scan_skipped_attachments', 0);
        if ($wmh_skipped_posts > 0 || $wmh_skipped_atts > 0) { ?>
            <div class="notice notice-warning wmh-notice-skipped mb-0">
                <p>
                    <span class="wmh-notice-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
                    <b><?php _e('Your last scan was not complete.', MEDIA_HYGIENE); ?></b>
                    <?php
                    if ($wmh_skipped_posts > 0) {
                        printf(
                            esc_html(_n(
                                /* "item" rather than "page or post": the scan now also
                                   renders block theme templates and reusable blocks,
                                   and any of those can be the one that failed */
                                '%s item could not be read. ',
                                '%s items could not be read. ',
                                $wmh_skipped_posts,
                                MEDIA_HYGIENE
                            )),
                            esc_html(number_format_i18n($wmh_skipped_posts))
                        );
                    }
                    if ($wmh_skipped_atts > 0) {
                        printf(
                            esc_html(_n(
                                '%s media file could not be checked. ',
                                '%s media files could not be checked. ',
                                $wmh_skipped_atts,
                                MEDIA_HYGIENE
                            )),
                            esc_html(number_format_i18n($wmh_skipped_atts))
                        );
                    }
                    _e('This usually means a plugin or theme error while the scan was running. Media used only on those items may be listed as unused, so review carefully before deleting, then run the scan again.', MEDIA_HYGIENE);
                    ?>
                </p>
            </div>
        <?php } ?>

        <!-- anonymous analytics opt-in / status notice -->
        <?php if ($wmh_close_analytics_permission_permanently != 'Yes') {
            if ($permission_for_send_data === 'pending' || $permission_for_send_data === false || $permission_for_send_data === '') { ?>
                <div class="notice notice-info analyzing-notice is-dismissible wmh-notice-optin mb-0">
                    <p>
                        <span class="wmh-notice-icon"><i class="fa-solid fa-lock"></i></span>
                        <b><?php _e('Help improve Media Hygiene?', MEDIA_HYGIENE); ?></b>
                        <?php _e('Allow Media Hygiene to collect anonymous data about media file types and sizes. No personal data, filenames, or identifying information is ever sent.', MEDIA_HYGIENE); ?>
                    </p>
                    <p>
                        <button id="wmh-optin-allow" class="button button-primary"><?php _e('Yes, allow', MEDIA_HYGIENE); ?></button>
                        &nbsp;
                        <button id="wmh-optin-decline" class="button"><?php _e('No thanks', MEDIA_HYGIENE); ?></button>
                        <i class="fa-solid fa-spinner fa-spin wmh-optin-loader" style="display:none; margin-left:8px;"></i>
                    </p>
                </div>
            <?php }
        } ?>
    </div>
</div>