jQuery(document).ready(function () {
    jQuery('#wmh-feedback-modal').dialog({
        title: 'Media Hygiene - Help Us Improve',
        autoOpen: false,
        modal: true,
        height: 500,
        width: 660,
        draggable: false,
        resizable: false,
        create: function (event, ui) {
            jQuery(this).css('overflow', 'hidden');
        },
        open: function (event, ui) {
            jQuery(this).css('overflow', 'hidden');
        },
        close: function (event, ui) {
            jQuery(this).css('overflow', 'auto');
        },
        buttons: [
            {
                text: "Skip & Deactivate",
                class: "wmh-plugin-deactive-btn",
                id: "wmh-skip-and-deactive",
                click: function () {
                    doDeactiveProcess(1);
                }
            },
            {
                text: "Deactivate",
                class: "wmh-plugin-deactive-btn",
                id: "wmh-deactive",
                click: function () {
                    doDeactiveProcess(2);
                }
            },
        ]
    });
});

/* deactivate link click — open modal */
jQuery(document).on('click', '#deactivate-media-hygiene', function (e) {
    e.preventDefault();
    jQuery('#wmh-feedback-modal').dialog('open');
    jQuery('#wmh-deactive').button('option', 'disabled', true);
});

/* enable Deactivate button once a radio is selected */
jQuery(document).on('change', '.wmh-feedback', function (e) {
    e.preventDefault();
    var chekedVal = jQuery('input[name=wmh_feedback]:checked').val();
    jQuery('#wmh-skip-and-deactive').attr('disabled', 'disabled');
    if (chekedVal == 8) {
        jQuery('#wmh-text-deactivate').css('display', 'block');
        jQuery('#wmh-feedback-modal').dialog('option', 'height', 600);
        var feedbackTextLength = jQuery('#wmh-text-deactivate').val();
        jQuery('#wmh-deactive').button('option', 'disabled', feedbackTextLength ? false : true);
    } else {
        jQuery('#wmh-text-deactivate').css('display', 'none');
        jQuery('#wmh-feedback-modal').dialog('option', 'height', 500);
        jQuery('#wmh-deactive').button('option', 'disabled', false);
    }
});

/* enable Deactivate button when "Other" text has content */
jQuery(document).on('keyup', '#wmh-text-deactivate', function () {
    jQuery('#wmh-deactive').button('option', 'disabled', jQuery(this).val() ? false : true);
});

function doDeactiveProcess(processType) {
    var chekedVal    = jQuery('input[name=wmh_feedback]:checked').val() || '';
    var feedbackText = (chekedVal == 8) ? jQuery('#wmh-text-deactivate').val() : '';

    jQuery('#wmh-skip-and-deactive, #wmh-deactive').button('option', 'disabled', true);
    jQuery('.wmh-deactive-loader-div').css('display', 'block');

    jQuery.ajax({
        type: 'POST',
        url: wmhFeedbackObj.ajaxurl,
        data: {
            action:        'wmh_customer_feedback',
            checked_val:   chekedVal,
            feedback_text: feedbackText,
            process_type:  processType,
            share_contact: jQuery('#wmh-share-contact').is(':checked') ? '1' : '0',
            nonce:         wmhFeedbackObj.nonce
        },
        success: function (raw) {
            var res;
            try {
                res = JSON.parse(raw);
            } catch (e) {
                jQuery('.wmh-deactive-loader-div').css('display', 'none');
                location.reload();
                return;
            }
            jQuery('.wmh-deactive-loader-div').css('display', 'none');
            location.reload();
        },
        error: function () {
            jQuery('.wmh-deactive-loader-div').css('display', 'none');
            location.reload();
        }
    });
}
