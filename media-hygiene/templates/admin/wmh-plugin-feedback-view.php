<!-- feedback modal-->
<div id="wmh-feedback-modal" style="display: none;">
    <h2><?php _e('If you have a moment, please let us know why you are deactivating:', MEDIA_HYGIENE); ?></h2>
    <ul>
        <li>
            <input type="radio" class="wmh-feedback" name="wmh_feedback" value="1" />
            <?php _e('The plugin is not working as expected', MEDIA_HYGIENE); ?>
        </li>
        <li>
            <input type="radio" class="wmh-feedback" name="wmh_feedback" value="2" />
            <?php _e('I found a better plugin', MEDIA_HYGIENE); ?>
        </li>
        <li>
            <input type="radio" class="wmh-feedback" name="wmh_feedback" value="3" />
            <?php _e('It is not what I was looking for', MEDIA_HYGIENE); ?>
        </li>
        <li>
            <input type="radio" class="wmh-feedback" name="wmh_feedback" value="4" />
            <?php _e('The plugin is not working', MEDIA_HYGIENE); ?>
        </li>
        <li>
            <input type="radio" class="wmh-feedback" name="wmh_feedback" value="5" />
            <?php _e('I could not understand how to use it', MEDIA_HYGIENE); ?>
        </li>
        <li>
            <input type="radio" class="wmh-feedback" name="wmh_feedback" value="6" />
            <?php _e('The plugin is great, but I need a specific feature that you do not support', MEDIA_HYGIENE); ?>
        </li>
        <li>
            <input type="radio" class="wmh-feedback" name="wmh_feedback" value="7" />
            <?php _e('It is a temporary deactivation - I am troubleshooting in the issue', MEDIA_HYGIENE); ?>
        </li>
        <li>
            <input type="radio" class="wmh-feedback" name="wmh_feedback" value="8" id="wmh-other-feedback" />
            <?php _e('Other', MEDIA_HYGIENE); ?>
        </li>
    </ul>
    <textarea rows="4" cols="90" id="wmh-text-deactivate" placeholder="<?php esc_attr_e('Tell us more...', MEDIA_HYGIENE); ?>" style="display:none; margin-top:8px;"></textarea>

    <div style="margin-top:14px; padding:10px 12px; background:#f9f9f9; border-left:3px solid #ddd; font-size:12px; color:#555; line-height:1.6;">
        <strong><?php _e('What will be sent to mediahygiene.com:', MEDIA_HYGIENE); ?></strong><br>
        <?php _e('Plugin version &nbsp;&bull;&nbsp; WordPress version &nbsp;&bull;&nbsp; PHP version &nbsp;&bull;&nbsp; Your selected reason &nbsp;&bull;&nbsp; Whether you completed a scan', MEDIA_HYGIENE); ?>
        <br><?php _e('No personal data is included unless you check the box below.', MEDIA_HYGIENE); ?>
        &nbsp;<a href="https://mediahygiene.com/privacy-policy/" target="_blank" rel="noopener noreferrer"><?php _e('Privacy Policy', MEDIA_HYGIENE); ?></a>
    </div>

    <p style="margin-top:10px;">
        <label>
            <input type="checkbox" id="wmh-share-contact" value="1" checked="checked" />
            <?php _e('Also share my name, email, and site URL so you can follow up if needed', MEDIA_HYGIENE); ?>
        </label>
    </p>

    <div class="wmh-deactive-loader-div" style="display: none; margin-top:8px;">
        <?php _e('Processing...', MEDIA_HYGIENE); ?>
    </div>
</div>
