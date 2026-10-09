<?php

/**
 * Review request notice.
 *
 * Shown once, only after a scan has completed successfully AND actually found
 * reclaimable space, so the ask lands at the moment the plugin has just proved
 * its value rather than on an arbitrary page load. Dismissed permanently via
 * AJAX (wmh_dismiss_review_request_action).
 */

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

/* already dismissed, or already left a review */
if (get_option('wmh_review_request_dismissed') === 'Yes') {
    return;
}

/* only after a scan that ran to completion */
if (get_option('wmh_scan_complete') !== 'completed') {
    return;
}

/* only when the scan found something worth deleting */
$wmh_review_unused_size = (int) get_option('wmh_unused_media_size');
if ($wmh_review_unused_size <= 0) {
    return;
}

?>
<div class="notice notice-success is-dismissible mb-0 wmh-notice-review" id="wmh-review-request">
    <p>
        <span class="wmh-notice-icon"><i class="fa-solid fa-star"></i></span>
        <b><?php
            printf(
                /* translators: %s: human readable file size, e.g. "1.2 GB" */
                esc_html__('Media Hygiene found %s of unused media on your site.', MEDIA_HYGIENE),
                esc_html(size_format($wmh_review_unused_size))
            );
        ?></b>
        <?php _e('If the plugin saved you time, a quick review helps other people find it and helps us keep the free version going.', MEDIA_HYGIENE); ?>
    </p>
    <p>
        <a href="https://wordpress.org/support/plugin/media-hygiene/reviews/#new-post" target="_blank" rel="noopener noreferrer" class="button button-primary" id="wmh-review-request-leave"><?php _e('Leave a review', MEDIA_HYGIENE); ?></a>
        &nbsp;
        <button type="button" class="button" id="wmh-review-request-dismiss"><?php _e('Maybe later', MEDIA_HYGIENE); ?></button>
    </p>
</div>
<script>
    (function ($) {
        'use strict';
        /* wmhObj is localized on wmh-admin-js; fall back to the core global */
        var wmhReviewAjaxUrl = (typeof wmhObj !== 'undefined' && wmhObj.ajaxurl)
            ? wmhObj.ajaxurl
            : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');

        function wmhDismissReview() {
            if (!wmhReviewAjaxUrl) {
                return;
            }
            $.post(wmhReviewAjaxUrl, {
                action: 'wmh_dismiss_review_request_action',
                nonce: '<?php echo esc_js(wp_create_nonce('wmh_review_request_nonce')); ?>'
            });
        }
        $(document).on('click', '#wmh-review-request-dismiss', function (e) {
            e.preventDefault();
            wmhDismissReview();
            $('#wmh-review-request').fadeOut(200);
        });
        /* clicking through to wp.org also counts as handled */
        $(document).on('click', '#wmh-review-request-leave', function () {
            wmhDismissReview();
        });
        /* the core dismiss X */
        $(document).on('click', '#wmh-review-request .notice-dismiss', function () {
            wmhDismissReview();
        });
    })(jQuery);
</script>
