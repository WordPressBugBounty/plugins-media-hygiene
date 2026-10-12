/* Leave room for the title bar, the button pane and a margin from the window
   edges. A fixed dialog height cannot account for those, which is how the
   consent checkbox ended up cut in half. */
function wmhMaxDialogHeight() {
    return Math.max(320, jQuery(window).height() - 120);
}

jQuery(document).ready(function () {
    jQuery('#wmh-feedback-modal').dialog({
        title: 'Media Hygiene - Help Us Improve',
        autoOpen: false,
        modal: true,
        /* 'auto' lets jQuery UI measure the form and size the content pane
           around the title and button panes itself. A fixed height made the
           content pane taller than the visible area, so the last row was
           clipped whatever number was used. maxHeight caps it on short
           windows and the content area scrolls from there. */
        height: 'auto',
        maxHeight: wmhMaxDialogHeight(),
        width: 660,
        draggable: false,
        resizable: false,
        open: function (event, ui) {
            /* recompute on open: the window may have been resized since load */
            jQuery(this).dialog('option', 'maxHeight', wmhMaxDialogHeight());
        },
        buttons: [
            {
                /* One button, and its label says whether anything will be sent.
                   Two buttons that both submitted the form left the user with
                   no way to tell which one shared their answer. */
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
    /* No popup on the page means let the link deactivate as normal. A missing
       feedback form must never stop someone deactivating the plugin. */
    if (!jQuery('#wmh-feedback-modal').length) {
        return;
    }
    e.preventDefault();
    jQuery('#wmh-feedback-modal').dialog('open');
    /* reset: closing and reopening the dialog used to keep the state from last
       time, leaving Skip disabled with nothing selected and no way out but the X */
    jQuery('input[name=wmh_feedback]').prop('checked', false);
    jQuery('#wmh-text-deactivate').val('').attr('placeholder', wmhFeedbackObj.promptDefault);
    wmhSyncDeactivateButton();
});

/* The comment box is always visible. It used to appear only for "Other", which
 * is why the most common reason, "not working as expected", arrived with no
 * detail at all. Only the prompt changes with the reason: a specific question
 * gets a usable answer where a generic one gets silence. */
var wmhTextPrompts = {
    1: 'promptNotAsExpected',
    2: 'promptBetterPlugin',
    4: 'promptNotWorking',
    5: 'promptConfusing',
    6: 'promptMissingFeature',
    8: 'promptOther'
};

/* Only "Other" requires text, because it carries no meaning on its own.
 * Requiring it elsewhere just produces junk from people who want to get out. */
function wmhReasonNeedsText(reason) {
    return parseInt(reason, 10) === 8;
}

function wmhPromptFor(reason) {
    var key = wmhTextPrompts[parseInt(reason, 10)];
    return (key && wmhFeedbackObj[key]) ? wmhFeedbackObj[key] : wmhFeedbackObj.promptDefault;
}

/* Keep the button label matching what pressing it will do. The disclosure
   box above lists exactly what gets sent, so "Send" is a promise the user
   can already check. Clearing the form is how someone opts out. */
function wmhSyncDeactivateButton() {
    var reason = jQuery('input[name=wmh_feedback]:checked').val();
    var text   = jQuery('#wmh-text-deactivate').val();
    var hasText = (text && text.trim() !== '');
    var willSend = !!reason || hasText;

    jQuery('#wmh-deactive').button('option', 'label',
        willSend ? wmhFeedbackObj.btnSend : wmhFeedbackObj.btnPlain);

    /* "Other" says nothing on its own, so it waits for the text. Every
       other state can be submitted, including an empty form, which sends
       nothing and simply deactivates. */
    var blocked = (wmhReasonNeedsText(reason) && !hasText);
    jQuery('#wmh-deactive').button('option', 'disabled', blocked);
}

/* enable Deactivate button once a radio is selected */
jQuery(document).on('change', '.wmh-feedback', function (e) {
    e.preventDefault();
    var chekedVal = jQuery('input[name=wmh_feedback]:checked').val();
    /* Skip closes off once a reason is picked, so the answer is not lost on a
       stray click. Use the widget's own API: .attr('disabled') sets the
       attribute but leaves jQuery UI's state class and handlers in place, so
       the button still looked and behaved as enabled.

       "Other" is the exception. It needs text before Deactivate enables, so
       disabling Skip there too would leave someone who picked Other and typed
       nothing with no working button at all, unable to deactivate the plugin
       they came here to deactivate. Skip stays open until Deactivate is. */
    jQuery('#wmh-text-deactivate').attr('placeholder', wmhPromptFor(chekedVal));
    wmhSyncDeactivateButton();
});

/* "Other" still requires text before the user can continue. Skip follows
   Deactivate: it stays available until Deactivate becomes available, so the
   user is never left with two dead buttons. */
jQuery(document).on('keyup', '#wmh-text-deactivate', function () {
    wmhSyncDeactivateButton();
});

function doDeactiveProcess(processType) {
    var chekedVal = jQuery('input[name=wmh_feedback]:checked').val() || '';

    /* send whatever they typed, whichever reason they picked. The old code
     * only kept the text for "Other" and threw it away otherwise. */
    var feedbackText = jQuery('#wmh-text-deactivate').val() || '';

    jQuery('#wmh-deactive').button('option', 'disabled', true);
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
