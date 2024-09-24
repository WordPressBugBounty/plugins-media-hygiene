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
    <textarea rows="5" cols="90" id="wmh-text-deactivate" placeholder="Enter your feedback" style="display:none"></textarea>
    <div class="wmh-deactive-loader-div" style="display: none;">
        <?php _e('Loading ...', MEDIA_HYGIENE); ?>
    </div>
</div>