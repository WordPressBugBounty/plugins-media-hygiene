<?php defined('ABSPATH') or die('Plugin file cannot be accessed directly.'); ?>
<!-- feedback modal-->
<div id="wmh-feedback-modal" style="display: none;">
    <h2><?php _e('If you have a moment, please let us know why you are deactivating:', MEDIA_HYGIENE); ?></h2>
    <?php
    /* Each reason is wrapped in its own <label>. Without it only the 16px radio
     * circle was clickable: clicking the words focused the radio but never
     * selected it, so the form looked unresponsive and the answer was lost. */
    $wmh_reasons = array(
        1 => __('The plugin is not working as expected', MEDIA_HYGIENE),
        2 => __('I found a better plugin', MEDIA_HYGIENE),
        3 => __('It is not what I was looking for', MEDIA_HYGIENE),
        4 => __('The plugin is not working', MEDIA_HYGIENE),
        5 => __('I could not understand how to use it', MEDIA_HYGIENE),
        6 => __('The plugin is great, but I need a specific feature that you do not support', MEDIA_HYGIENE),
        7 => __('It is a temporary deactivation - I am troubleshooting in the issue', MEDIA_HYGIENE),
        8 => __('Other', MEDIA_HYGIENE),
    );
    ?>
    <ul>
        <?php foreach ($wmh_reasons as $wmh_reason_id => $wmh_reason_label) : ?>
        <li>
            <label for="wmh-feedback-<?php echo (int) $wmh_reason_id; ?>" style="display:block; cursor:pointer; padding:2px 0;">
                <input type="radio" class="wmh-feedback" name="wmh_feedback" id="wmh-feedback-<?php echo (int) $wmh_reason_id; ?>" value="<?php echo (int) $wmh_reason_id; ?>" />
                <?php echo esc_html($wmh_reason_label); ?>
            </label>
        </li>
        <?php endforeach; ?>
    </ul>
    <textarea rows="3" cols="90" id="wmh-text-deactivate" placeholder="<?php esc_attr_e('Anything you can tell us helps. Optional.', MEDIA_HYGIENE); ?>" style="margin-top:8px; width:100%; box-sizing:border-box;"></textarea>

    <?php
    /* Each item is its own string so it can be translated and reordered.
     * The old version was one run-on line with bullet characters and
     * &nbsp; inside the translatable text, which read as a wall and gave
     * translators nothing to work with. */
    $wmh_sent_items = array(
        __('Plugin version', MEDIA_HYGIENE),
        __('WordPress version', MEDIA_HYGIENE),
        __('PHP version', MEDIA_HYGIENE),
        __('The reason you select above', MEDIA_HYGIENE),
        __('Anything you type in the box', MEDIA_HYGIENE),
        __('Whether you completed a scan', MEDIA_HYGIENE),
    );
    ?>
    <div style="margin-top:14px; padding:12px 14px; background:#f6f7f7; border-left:3px solid #c3c4c7; font-size:12px; color:#50575e; line-height:1.5;">
        <strong style="display:block; margin-bottom:6px; color:#1d2327;"><?php _e('What will be sent to mediahygiene.com', MEDIA_HYGIENE); ?></strong>
        <ul style="margin:0 0 8px; padding:0; list-style:none; display:flex; flex-wrap:wrap;">
            <?php foreach ($wmh_sent_items as $wmh_sent_item) : ?>
                <li style="flex:0 0 50%; box-sizing:border-box; padding:1px 0;">
                    <span style="color:#8c8f94;">&bull;</span>&nbsp;<?php echo esc_html($wmh_sent_item); ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <div style="padding-top:8px; border-top:1px solid #e0e0e0;">
            <strong style="color:#1d2327;"><?php _e('Nothing that identifies you or your site is sent', MEDIA_HYGIENE); ?></strong>
            <?php _e('unless you tick the box below.', MEDIA_HYGIENE); ?>
            <a href="<?php echo wmh_site_link('privacy-policy', 'deactivate-modal', '', false, 'transparency'); ?>" target="_blank" rel="noopener noreferrer"><?php _e('Privacy Policy', MEDIA_HYGIENE); ?></a>
        </div>
    </div>

    <?php
    /* Unticked by default. It used to ship checked, which is not valid consent
     * under GDPR and meant a share of the contact details we held were from
     * people who simply had not noticed the box. Fewer, genuine opt-ins are
     * worth more than a higher number we cannot rely on. The label says what
     * it is for, since an unticked box needs a reason to tick it. */
    ?>
    <p style="margin-top:10px;">
        <label style="cursor:pointer;">
            <input type="checkbox" id="wmh-share-contact" value="1" />
            <?php _e('Share my name, email and site URL so you can reply about this', MEDIA_HYGIENE); ?>
        </label>
    </p>

    <div class="wmh-deactive-loader-div" style="display: none; margin-top:8px;">
        <?php _e('Processing...', MEDIA_HYGIENE); ?>
    </div>
</div>
