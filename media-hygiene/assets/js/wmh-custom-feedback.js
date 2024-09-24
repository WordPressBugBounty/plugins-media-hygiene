jQuery(document).ready(function () {
    jQuery('#wmh-feedback-modal').dialog({
        title: 'Media Hygiene - Help Us Improve',
        autoOpen: false,
        modal: true,
        height: 390,
        width: 650,
        draggable: false,
        resizable: false,
        create: function (event, ui) {
            // Apply CSS to disable scrollbars
            jQuery(this).css('overflow', 'hidden');
        },
        open: function (event, ui) {
            // Apply CSS to disable scrollbars
            jQuery(this).css('overflow', 'hidden');
        },
        close: function (event, ui) {
            // Reset CSS after the modal is closed
            jQuery(this).css('overflow', 'auto');
        },
        buttons: [
            {
                text: "Skip & Deactivate",
                class: "wmh-plugin-deactive-btn",
                id: "wmh-skip-and-deactive",
                click: function () {
                    let processType = 1;
                    doDeactiveProcess(processType);
                }
            },
            {
                text: "Deactivate",
                class: "wmh-plugin-deactive-btn",
                id: "wmh-deactive",
                click: function () {
                    let processType = 2;
                    doDeactiveProcess(processType);
                }
            },
        ]
    });
});

/* deactive button click */
jQuery(document).on('click', '#deactivate-media-hygiene', function (e) {
    e.preventDefault();
    /* opem modal */
    jQuery('#wmh-feedback-modal').dialog('open');
    /* by default isable deactivate button */
    jQuery('#wmh-deactive').button('option', 'disabled', true);
});

/* keypress in feedback text area */
jQuery(document).on('keyup', '#wmh-text-deactivate', function (e) {
    e.preventDefault();
    let feedbackTextLength = jQuery(this).val();
    if (feedbackTextLength) {
        jQuery('#wmh-deactive').button('option', 'disabled', false);
    } else {
        jQuery('#wmh-deactive').button('option', 'disabled', true);
    }
});

jQuery(document).on('change', '.wmh-feedback', function (e) {
    e.preventDefault();
    let chekedVal = jQuery('input[name=wmh_feedback]:checked').val();
    jQuery('#wmh-skip-and-deactive').attr('disabled', 'disabled');
    if (chekedVal == 8) {
        jQuery('#wmh-text-deactivate').css('display', 'block');
        jQuery("#wmh-feedback-modal").dialog("option", "height", 500);
        let feedbackTextLength = jQuery("#wmh-text-deactivate").val();
        if (feedbackTextLength) {
            jQuery('#wmh-deactive').button('option', 'disabled', false);
        } else {
            jQuery('#wmh-deactive').button('option', 'disabled', true);
        }
    } else {
        jQuery('#wmh-text-deactivate').css('display', 'none');
        jQuery("#wmh-feedback-modal").dialog("option", "height", 390);
        jQuery('#wmh-deactive').button('option', 'disabled', false);
    }
});

function doDeactiveProcess(processType = '') {

    var feedbackText = '';
    let chekedVal = jQuery('input[name=wmh_feedback]:checked').val()
    if (chekedVal == 8) {
        var feedbackText = jQuery('#wmh-text-deactivate').val();
    }
    jQuery.ajax({
        type: "POST",
        url: wmhFeedbackObj.ajaxurl,
        data: {
            action: 'wmh_customer_feedback',
            checked_val: chekedVal,
            feedback_text: feedbackText,
            process_type: processType,
            nonce: wmhFeedbackObj.nonce
        },
        beforeSend: function () {
            jQuery('.wmh-deactive-loader-div').css('display', 'block');
        },
        success: function (res) {
            var res = JSON.parse(res);
            alert(res.msg);
            jQuery('.wmh-deactive-loader-div').css('display', 'none');
            location.reload();
        },
    });
}