jQuery(document).ready(function () {

    /* ── Trash success notice — shown after page reload ── */
    var wmhTrashNotice = sessionStorage.getItem('wmh_trash_notice');
    if (wmhTrashNotice) {
        sessionStorage.removeItem('wmh_trash_notice');
        try {
            var nd = JSON.parse(wmhTrashNotice);
            var banner = jQuery(
                '<div class="wmh-trash-success-notice" role="alert">' +
                '<span class="wmh-tsn-icon"><i class="fa-solid fa-trash-can-arrow-up"></i></span>' +
                '<div class="wmh-tsn-body">' +
                '<strong>' + nd.message + '</strong>' +
                '<p>These files are now in the <strong>WordPress Trash</strong> — nothing has been permanently deleted from your site yet. ' +
                'Please review your site to confirm everything looks correct. ' +
                'If any image was moved to trash by mistake, restore it from <a href="?page=wmh-media-hygiene&type=trash" target="_blank">Media Library &rsaquo; Trash</a>. ' +
                'Once you are satisfied, empty the trash to permanently free up the space.</p>' +
                '</div>' +
                '<button class="wmh-tsn-close" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button>' +
                '</div>'
            );
            banner.find('.wmh-tsn-close').on('click', function () {
                banner.fadeOut(200, function () { banner.remove(); });
            });
            jQuery('.wmh-container').first().prepend(banner);
        } catch (e) { }
    }

    jQuery('[data-toggle="tooltip"]').tooltip();

    /* Bootstrap datepicker for select year and month. */
    jQuery('#date').datepicker({
        format: "yyyy-mm",
        startView: "months",
        minViewMode: "months",
        autoclose: true,
        todayHighlight: true,
        clearBtn: true
    });

    /* Clear date on delete key and backspace key. */
    jQuery("#date").keyup(function (e) {
        if (e.keyCode == 8 || e.keyCode == 46) {
            jQuery("#date").datepicker('update', "");
        }
    });

    /* Error log datatable */
    jQuery('#error-log-list').DataTable();

    /* Add placeholder attr in wp list table search */
    jQuery('.search-box #search_post_id-search-input').attr('placeholder', 'Search by id/name/type');

    /* go pro link target blank */
    jQuery('#go-pro-link').parent().attr('target', '_blank');

    /* delete media list */
    fnWmhDeletedMEdiaList();

});

/* scan button click event. */
jQuery(document).on('click', '#scan-button', function (e) {
    e.preventDefault();
    /* scan notice */
    jQuery(".scan-nt-warn").css('display', 'block');
    jQuery(".scan-estimated-time").css('display', 'block');
    /* disable and unclickable scan button. */
    jQuery('#scan-button').attr('disabled', 'disabled');
    jQuery('#scan-button').css('cursor', 'not-allowed');
    /* disable and unclickable delete all media button. */
    jQuery('#delete-all-media-button').attr('disabled', 'disabled');
    jQuery('#delete-all-media-button').css('cursor', 'not-allowed');
    /* disable and unclickable download all media button */
    jQuery('#create-zip-button').attr('disabled', 'disabled');
    jQuery('#create-zip-button').css('cursor', 'not-allowed');
    /* disable and unclickable delete-page-media-button  */
    jQuery('#trash-page-media-button').attr('disabled', 'disabled');
    jQuery('#trash-page-media-button').css('cursor', 'not-allowed');
    /* disable and unclickable download page media button */
    jQuery('#create-page-zip-button').attr('disabled', 'disabled');
    jQuery('#create-page-zip-button').css('cursor', 'not-allowed');
    var data = jQuery('#wmh-scan-form').serialize();
    fnWmhScanAttachment(data);
});

/* update database message on scan */
jQuery(document).on("click", ".update-database-msg-btn", function (e) {
    e.preventDefault();
    alert('Please update the media hygiene database before proceeding with any action.');
    jQuery("html, body").animate({ scrollTop: 0 }, "fast");
    //jQuery("#wmh-update-database").addClass("wmh-update-database-btn-highlight");
});

/* Re-enable all scan-related buttons after scan ends (success or error). */
function fnWmhScanResetButtons() {
    jQuery('#scan-button').removeAttr('disabled').css('cursor', '');
    jQuery('#delete-all-media-button').removeAttr('disabled').css('cursor', '');
    jQuery('#create-zip-button').removeAttr('disabled').css('cursor', '');
    jQuery('#trash-page-media-button').removeAttr('disabled').css('cursor', '');
    jQuery('#create-page-zip-button').removeAttr('disabled').css('cursor', '');
    jQuery('.scan-loader').css('display', 'none');
    jQuery('.scan-nt-warn').css('display', 'none');
}

/* Show a scan-phase error notice and reset buttons. */
function fnWmhScanError(message) {
    fnWmhScanResetButtons();
    jQuery('.progress').css('display', 'none');
    jQuery('.scan-data-status').css('display', 'none');
    jQuery('.notice.notice-scan').css('display', 'block')
        .removeClass('notice-success').addClass('notice-error')
        .find('p').text(message || 'Scan failed — please try again or check the server error log.');
}

/* scan ajax call function. */
function fnWmhScanAttachment(data) {
    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: data,
        timeout: 120000,
        beforeSend: function () {
            jQuery('.scan-loader').css('display', 'inline-block');
            jQuery('.progress .bar').addClass('bg-success');
            jQuery('.progress').css('display', 'inline-block');
            jQuery('.total-file-size').css('display', 'none');
            jQuery('.scan-data-status').css('display', 'block');
            jQuery('.scan-data-status .scan-data-title').text('Retrieving the data ... ');
            jQuery('.scan-data-status .scan-data-steps b').text('(1/4)');
        },
        success: function (raw) {
            var data;
            try { data = JSON.parse(raw); } catch (e) {
                console.error('[Media Hygiene] Scan step 1 raw response:', raw);
                fnWmhScanError('Scan error (step 1): unexpected server response. Please check the server error log.');
                return;
            }
            var ajax_call = data.ajax_call;
            var progress_bar_width = data.progress_bar_width;
            if (progress_bar_width <= 100) {
                jQuery('.progress .bar').css('width', progress_bar_width + '%');
                jQuery('.progress .percent').text(progress_bar_width + '%');
            }
            if (data.flg == 1) {
                ajax_call++;
                jQuery('#ajax_call').val(ajax_call);
                jQuery('#progress_bar').val(progress_bar_width);
                fnWmhScanAttachment(jQuery('#wmh-scan-form').serialize());
            } else if (data.flg == 2) {
                jQuery('.progress .bar').css('width', '100%');
                jQuery('.progress .percent').text('100%');
                fnFetchDataFromDatabase(1, 0);
            } else if (data.flg == 0) {
                fnWmhScanResetButtons();
                alert(data.message);
                location.reload();
            } else {
                fnWmhScanError(data.message || 'Unknown scan state returned from server.');
            }
        },
        error: function (xhr, status, error) {
            fnWmhScanError('Scan connection error (step 1).\nStatus: ' + status + '\n' + error);
        }
    });
}

/* fetch data from database */
function fnFetchDataFromDatabase(fetchDataAjaxCall = '', fetchDataProgressBar = '') {

    let data = {
        action: 'fetch_data_from_database',
        ajax_call: fetchDataAjaxCall,
        progress_bar: fetchDataProgressBar,
        nonce: wmhObj.nonce
    }

    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: data,
        timeout: 120000,
        beforeSend: function () {
            jQuery('.scan-data-status .scan-data-title').text('Retrieving the data ... ');
            jQuery('.scan-data-status .scan-data-steps b').text('(2/4)');
        },
        success: function (raw) {
            var res;
            try { res = JSON.parse(raw); } catch (e) {
                console.error('[Media Hygiene] Scan step 2 raw response:', raw);
                fnWmhScanError('Scan error (step 2): unexpected server response. Please check the server error log.');
                return;
            }
            var ajax_call = res.ajax_call;
            var progress_bar_width = res.progress_bar_width;
            if (progress_bar_width <= 100) {
                jQuery('.progress .bar').css('width', progress_bar_width + '%');
                jQuery('.progress .percent').text(progress_bar_width + '%');
            }
            if (res.flg == 1) {
                ajax_call++;
                fnFetchDataFromDatabase(ajax_call, progress_bar_width);
            } else if (res.flg == 2) {
                jQuery('.progress .bar').css('width', '100%');
                jQuery('.progress .percent').text('100%');
                fnScanningDataAjaxCall(1, 0);
            } else if (res.flg == 0) {
                fnWmhScanResetButtons();
                alert(res.message);
                location.reload();
            } else if (res.flg == 3) {
                fnSpecialProcessForElementor(ajax_call, 0, progress_bar_width);
            } else {
                fnWmhScanError(res.message || 'Unknown state in step 2.');
            }
        },
        error: function (xhr, status, error) {
            fnWmhScanError('Scan connection error (step 2).\nStatus: ' + status + '\n' + error);
        }
    });

}

function fnSpecialProcessForElementor(ajax_call = '', elementor_ajax_call = '', progress_bar_width = '') {
    let data = {
        action: 'fetch_data_from_elementor',
        ajax_call: elementor_ajax_call,
        progress_bar: progress_bar_width,
        nonce: wmhObj.nonce
    }
    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: data,
        timeout: 120000,
        success: function (raw) {
            var res;
            try { res = JSON.parse(raw); } catch (e) {
                console.error('[Media Hygiene] Scan Elementor step raw response:', raw);
                fnWmhScanError('Scan error (Elementor step): unexpected server response. Please check the server error log.');
                return;
            }
            if (res.progress_bar_width <= 100) {
                jQuery('.progress .bar').css('width', res.progress_bar_width + '%');
                jQuery('.progress .percent').text(res.progress_bar_width + '%');
            }
            if (res.flg == 0) {
                ajax_call++;
                fnFetchDataFromDatabase(ajax_call, progress_bar_width);
            } else if (res.flg == 1) {
                fnSpecialProcessForElementor(ajax_call, res.ajax_call, res.progress_bar_width);
            } else {
                fnWmhScanError(res.message || 'Unknown state in Elementor step.');
            }
        },
        error: function (xhr, status, error) {
            fnWmhScanError('Scan connection error (Elementor step).\nStatus: ' + status + '\n' + error);
        }
    });
}

/* scanning data */
function fnScanningDataAjaxCall(scanningDataAjaxCall = '', progressBar = '') {
    let data = {
        action: 'scanning_data',
        ajax_call: scanningDataAjaxCall,
        progress_bar: progressBar,
        nonce: wmhObj.nonce
    }
    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: data,
        timeout: 120000,
        beforeSend: function () {
            jQuery('.scan-data-status .scan-data-title').text('Scanning the data ... ');
            jQuery('.scan-data-status .scan-data-steps b').text('(3/4)');
        },
        success: function (raw) {
            var res;
            try { res = JSON.parse(raw); } catch (e) {
                console.error('[Media Hygiene] Scan step 3 raw response:', raw);
                fnWmhScanError('Scan error (step 3): unexpected server response. Please check the server error log.');
                return;
            }
            var ajax_call = res.ajax_call;
            var progress_bar_width = res.progress_bar_width;
            if (progress_bar_width <= 100) {
                jQuery('.progress .bar').css('width', progress_bar_width + '%');
                jQuery('.progress .percent').text(progress_bar_width + '%');
            }
            if (res.flg == 1) {
                ajax_call++;
                fnScanningDataAjaxCall(ajax_call, progress_bar_width);
            } else if (res.flg == 2) {
                jQuery('.progress .bar').css('width', '100%');
                jQuery('.progress .percent').text('100%');
                fnFetchStatisticsData(1);
            } else if (res.flg == 0) {
                fnWmhScanResetButtons();
                alert(res.message);
                location.reload();
            } else {
                fnWmhScanError(res.message || 'Unknown state in step 3.');
            }
        },
        error: function (xhr, status, error) {
            fnWmhScanError('Scan connection error (step 3).\nStatus: ' + status + '\n' + error);
        }
    });
}

function fnFetchStatisticsData(statisticsAjaxCall = '') {
    let data = {
        action: 'fetch_statistics_data',
        statistics_ajax_call: statisticsAjaxCall,
        nonce: wmhObj.nonce
    }
    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: data,
        timeout: 120000,
        beforeSend: function () {
            jQuery('.scan-data-status .scan-data-title').text('Analyzing the data ... ');
            jQuery('.scan-data-status .scan-data-steps b').text('(4/4)');
        },
        success: function (raw) {
            var res;
            try { res = JSON.parse(raw); } catch (e) {
                console.error('[Media Hygiene] Scan step 4 raw response:', raw);
                fnWmhScanError('Scan error (step 4): unexpected server response. Please check the server error log.');
                return;
            }
            var ajax_call = res.ajax_call;
            var progress_bar_width = res.progress_bar_width;
            if (progress_bar_width <= 100) {
                jQuery('.progress .bar').css('width', progress_bar_width + '%');
                jQuery('.progress .percent').text(progress_bar_width + '%');
            }
            if (res.flg == '1') {
                ajax_call++;
                fnFetchStatisticsData(ajax_call);
            } else if (res.flg == '2') {
                fnWmhScanResetButtons();
                location.reload();
            } else if (res.flg == '0') {
                fnWmhScanError(res.message || 'Scan completed but statistics could not be saved.');
            } else {
                fnWmhScanError(res.message || 'Unknown state in step 4.');
            }
        },
        error: function (xhr, status, error) {
            fnWmhScanError('Scan connection error (step 4).\nStatus: ' + status + '\n' + error);
        }
    });
}



/* row action - trash */
function fn_wmh_row_action_trash(post_id = '', file_size = '') {
    var data = {
        action: 'row_action_trash',
        post_id: post_id,
        file_size: file_size,
        nonce: wmhObj.nonce
    };
    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: data,
        timeout: 60000,
        beforeSend: function () {
            jQuery('.row-action-trash-loader-' + post_id).css('display', 'inline-block');
        },
        success: function (raw) {
            var res;
            try { res = JSON.parse(raw); } catch (e) {
                alert('Unexpected server response. Please check the error log.\n\n' + raw.substring(0, 400));
                return;
            }
            if (res.flg == 1) {
                alert(res.message);
                location.reload();
            } else if (res.flg == -1) {
                alert('Error: ' + (res.message || 'Unknown error') + '\n\nPlease check the debug log.');
            } else {
                alert(res.message || 'An unknown error occurred.');
            }
        },
        error: function (xhr, status, error) {
            alert('Network or server error trashing media.\nStatus: ' + status + '\nError: ' + error
                + (xhr.responseText ? '\n\n' + xhr.responseText.substring(0, 400) : ''));
        },
        complete: function () {
            jQuery('.row-action-trash-loader-' + post_id).css('display', 'none');
        }
    });
}

/* restore single image from trash page */
function fn_wmh_restore_single_image(post_id = '', file_size = '') {
    var data = {
        action: 'restore_single_image_call',
        post_id: post_id,
        file_size: file_size,
        nonce: wmhObj.nonce
    };
    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: data,
        beforeSend: function () {
            jQuery('.restore-loader-' + post_id + '').css('display', 'inline-block');
        },
        success: function (data) {
            var data = JSON.parse(data);
            alert(data.message);
            if (data.flg == '1') {
                location.reload();
            }
        },
        complete: function (data) {
            jQuery('.restore-loader-' + post_id + '').css('display', 'none');
        }
    });
}

/* Whitelist media, action row */
function fn_wmh_whitelist_single_image(post_id) {
    var data = {
        action: 'whitelist_single_image_call',
        post_id: post_id,
        nonce: wmhObj.nonce
    };
    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: data,
        beforeSend: function () {
            jQuery('.whitelist-loader-' + post_id + '').css('display', 'inline-block');
        },
        success: function (data) {
            var data = JSON.parse(data);
            if (data.flg == '1') {
                jQuery('.notice-scan').css('display', 'block');
                jQuery('.notice-scan').addClass('notice-success');
                jQuery('.notice-scan p').text(data.message);
                setTimeout(function () {
                    location.reload();
                }, 1000);
            } else if (data.flg == '0') {
                jQuery('.notice-scan').css('display', 'block');
                jQuery('.notice-scan').addClass('notice-error');
                jQuery('.notice-scan p').text(data.message);
                setTimeout(function () {
                    location.reload();
                }, 1000);
            }

        },
        complete: function (data) {
            jQuery('.whitelist-loader-' + post_id + '').css('display', 'none');
        }
    });

}

/* Blacklist media, action row */
function fn_wmh_blacklist_single_image(post_id) {
    var data = {
        action: 'blacklist_single_image_call',
        post_id: post_id,
        nonce: wmhObj.nonce
    };
    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: data,
        beforeSend: function () {
            jQuery('.blacklist-loader-' + post_id + '').css('display', 'inline-block');
        },
        success: function (data) {
            var data = JSON.parse(data);
            if (data.flg == '1') {
                jQuery('.notice-scan').css('display', 'block');
                jQuery('.notice-scan').addClass('notice-success');
                jQuery('.notice-scan p').text(data.message);
                setTimeout(function () {
                    location.reload();
                }, 1000);
            } else if (data.flg == '0') {
                jQuery('.notice-scan').css('display', 'block');
                jQuery('.notice-scan').addClass('notice-error');
                jQuery('.notice-scan p').text(data.message);
                setTimeout(function () {
                    location.reload();
                }, 1000);
            }
        },
        complete: function (data) {
            jQuery('.blacklist-loader-' + post_id + '').css('display', 'none');
        }
    });

}

/* Copy to clipboard for row action. */
function fn_wmh_copy_clipbord(image_url, post_id) {
    var copied = fn_wmh_copy_text(image_url);
    if (copied) {
        jQuery('.copied-done-' + post_id + '').css('display', 'inline-block');
        jQuery('.copy-class-' + post_id + '').css('color', 'green');
        setTimeout(function () {
            jQuery('.copied-done-' + post_id + '').css('display', 'none');
            jQuery('.copy-class-' + post_id + '').css('color', '');
        }, 1000);
    }
}

/* Media url copy click on file path column value. */
function fn_wmh_media_url_copy(media_url, post_id) {
    var copied = fn_wmh_copy_text(media_url);
    if (copied) {
        var $p = jQuery('<p style="color:green;"></p>');
        $p.text(wmhObj.msg_array.msg_1 + '\u00a0');
        $p.append('<i class="fa-solid fa-check" aria-hidden="true"></i>');
        jQuery('.media-url-' + post_id).empty().append($p);
        setTimeout(function () {
            jQuery('.media-url-' + post_id).text(media_url);
        }, 1000);
    }
}

/* Function about to copy text. */
function fn_wmh_copy_text(text) {
    var copyText = text.trim();
    let input = document.createElement('input');
    input.setAttribute('type', 'text');
    input.value = copyText;
    document.body.appendChild(input);
    input.select();
    document.execCommand("copy");
    return document.body.removeChild(input);
}

/* Function about to copy text in block. */
function copyFunctionBlock(clipboard_text) {
    const copyText = clipboard_text;
    const textArea = document.createElement('textarea');
    textArea.id = "copy_textarea";
    textArea.textContent = copyText;
    document.body.append(textArea);
    textArea.select();
    return document.execCommand("copy");
}

/* Change event on switch. */
var switch_status = '';
jQuery("#page-content-switch, #post-content-switch, #page-feature-image-switch, #post-feature-image-switch, #site-logo-switch, #site-icon-switch, #elementor-data-switch, #divi-switch, #bricks-switch, #vc-switch, #delete-data-on-uninstall-switch, #error-log-switch").on('change', function (e) {
    e.preventDefault();
    if (jQuery(this).is(':checked')) {
        switch_status = 'on';
        jQuery(this).val(switch_status);
        jQuery(this).attr('checked');
    } else {
        var switch_status = 'off';
        jQuery(this).val(switch_status);
        jQuery(this).removeAttr('checked');

    }
});

/* save_scan_option_button button click even to save data to option. */
jQuery(document).on('click', '#save-scan-option-button', function (e) {
    e.preventDefault();
    var data = jQuery('#wmh-save-scan-option-form').serialize();
    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: data,
        beforeSend: function () {
            jQuery('.save-settings-loader').css('display', 'inline-block');
        },
        success: function (data) {
            var data = JSON.parse(data);
            if (data.flg == '1') {
                jQuery('.notice-settings').css('display', 'block');
                jQuery('.notice-settings').addClass('notice-success');
                jQuery('.notice-settings p').text(data.message);
                jQuery(window).scrollTop(0);
                setTimeout(function () {
                    jQuery('.notice-settings').css('display', 'none');
                    jQuery('.notice-settings').removeClass('notice-success');
                    jQuery('.notice-settings p').text('');
                    location.reload();
                }, 2000);
            }
        },
        complete: function (data) {
            jQuery('.save-settings-loader').css('display', 'none');
        }
    });
});

/* System report click event. */
jQuery(document).on('click', '#system-report-button', function (e) {
    e.preventDefault();
    jQuery('.system-report').toggle();
});

/* Filter data on click */
jQuery(document).on('click', '#filter-submit', function (e) {
    e.preventDefault();
    var attachment_cat = jQuery('#wmh-filter-select').val();
    var date = jQuery('#date').val();
    var list_element = jQuery('#list_element').val();
    var data = {
        action: 'filter_data_ajax_call',
        attachment_cat: attachment_cat,
        date: date,
        list_element: list_element,
        nonce: wmhObj.nonce
    }
    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: data,
        beforeSend: function () {
            jQuery('.filter-media-loader').css('display', 'inline-block');
        },
        success: function (data) {
            var data = JSON.parse(data);
            window.location.href = data.url
        },
    });

});


/* Bulk media top selector delete. */
jQuery(document).on('click', '#doaction', function () {
    var bulk_action_val = jQuery('#bulk-action-selector-top').val().trim();
    if (bulk_action_val === '' || bulk_action_val === '-1') {
        return;
    }
    if (jQuery('.single-check-image:checked').length === 0) {
        alert('Please select at least one item before applying a bulk action.');
        return;
    }
    if (bulk_action_val != '' && bulk_action_val == 'trash') {
        var chek_box_val = jQuery.map(jQuery('.single-check-image:checked'), function (n, i) {
            return n.value;
        });
        var sizes = []
        /* get size*/
        jQuery('.single-check-image:checked').each(function () {
            var size = jQuery(this).data('size');
            sizes.push(size);
        });
        jQuery.ajax({
            type: 'POST',
            url: wmhObj.ajaxurl,
            data: {
                action: 'bulk_action_trash',
                bulk_action_val: bulk_action_val,
                chek_box_val: chek_box_val,
                size: sizes,
                nonce: wmhObj.nonce
            },
            timeout: 120000,
            success: function (raw) {
                var res;
                try { res = JSON.parse(raw); } catch (e) {
                    alert('Unexpected server response. Please check the error log.\n\n' + raw.substring(0, 400));
                    return;
                }
                if (res.flg == 1) {
                    alert(res.message);
                    location.reload();
                } else if (res.flg == -1) {
                    alert('Error: ' + (res.message || 'Unknown error') + '\n\nPlease check the debug log.');
                } else {
                    alert(res.message || 'An unknown error occurred.');
                }
            },
            error: function (xhr, status, error) {
                alert('Network or server error during bulk trash.\nStatus: ' + status + '\nError: ' + error
                    + (xhr.responseText ? '\n\n' + xhr.responseText.substring(0, 400) : ''));
            }
        });
    } else if (bulk_action_val != '' && bulk_action_val == 'whitelist') {
        var chek_box_val = jQuery.map(jQuery('.single-check-image:checked'), function (n, i) {
            return n.value;
        });
        jQuery.ajax({
            type: 'POST',
            url: wmhObj.ajaxurl,
            data: {
                action: 'bulk_action_to_whitelist',
                bulk_action_val: bulk_action_val,
                chek_box_val: chek_box_val,
                nonce: wmhObj.nonce
            },
            success: function (data) {
                var data = JSON.parse(data);
                if (data.flg == '1') {
                    alert(data.message);
                    location.reload();
                }
            }
        });
    } else if (bulk_action_val != '' && bulk_action_val == 'blacklist') {
        var chek_box_val = jQuery.map(jQuery('.single-check-image:checked'), function (n, i) {
            return n.value;
        });
        jQuery.ajax({
            type: 'POST',
            url: wmhObj.ajaxurl,
            data: {
                action: 'bulk_action_to_blacklist',
                bulk_action_val: bulk_action_val,
                chek_box_val: chek_box_val,
                nonce: wmhObj.nonce
            },
            success: function (data) {
                var data = JSON.parse(data);
                if (data.flg == '1') {
                    alert(data.message);
                    location.reload();
                }
            }
        });
    } else if (bulk_action_val != '' && bulk_action_val == 'restore') {
        var chek_box_val = jQuery.map(jQuery('.single-check-image:checked'), function (n, i) {
            return n.value;
        });
        var sizes = []
        /* get size*/
        jQuery('.single-check-image:checked').each(function () {
            var size = jQuery(this).data('size');
            sizes.push(size);
        });
        jQuery.ajax({
            type: 'POST',
            url: wmhObj.ajaxurl,
            data: {
                action: 'bulk_action_trash_to_restore',
                bulk_action_val: bulk_action_val,
                chek_box_val: chek_box_val,
                nonce: wmhObj.nonce,
                size: sizes
            },
            timeout: 120000,
            success: function (raw) {
                var res;
                try { res = JSON.parse(raw); } catch (e) {
                    alert('Unexpected server response during restore. Please check the error log.\n\n' + raw.substring(0, 400));
                    return;
                }
                if (res.flg == '1') {
                    alert(res.message);
                    window.location.href = '?page=wmh-media-hygiene';
                } else if (res.flg == '-1' || res.flg == -1) {
                    alert('Restore error: ' + (res.message || 'Unknown error') + '\n\nPlease check the debug log.');
                } else {
                    alert(res.message || 'An unknown error occurred during restore.');
                }
            },
            error: function (xhr, status, error) {
                alert('Network or server error during bulk restore.\nStatus: ' + status + '\nError: ' + error
                    + (xhr.responseText ? '\n\n' + xhr.responseText.substring(0, 400) : ''));
            }
        });
    } else if (bulk_action_val != '' && bulk_action_val == 'delete') {
        var chek_box_val = jQuery.map(jQuery('.single-check-image:checked'), function (n, i) {
            return n.value;
        });
        var sizes = []
        /* get size*/
        jQuery('.single-check-image:checked').each(function () {
            var size = jQuery(this).data('size');
            sizes.push(size);
        });
        jQuery.ajax({
            type: 'POST',
            url: wmhObj.ajaxurl,
            data: {
                action: 'bulk_action_delete',
                bulk_action_val: bulk_action_val,
                chek_box_val: chek_box_val,
                size: sizes,
                nonce: wmhObj.nonce
            },
            timeout: 120000,
            success: function (raw) {
                var res;
                try { res = JSON.parse(raw); } catch (e) {
                    alert('Unexpected server response. Please check the error log.\n\n' + raw.substring(0, 400));
                    return;
                }
                if (res.flg == 1) {
                    alert(res.message);
                    window.location.href = '?page=wmh-media-hygiene';
                } else if (res.flg == -1) {
                    alert('Error: ' + (res.message || 'Unknown error') + '\n\nPlease check the debug log.');
                } else {
                    alert(res.message || 'An unknown error occurred.');
                }
            },
            error: function (xhr, status, error) {
                alert('Network or server error during bulk delete.\nStatus: ' + status + '\nError: ' + error
                    + (xhr.responseText ? '\n\n' + xhr.responseText.substring(0, 400) : ''));
            }
        });
    } else {
        location.reload();
    }
});

/* Copy system report data to clipboard*/
jQuery('#copy-site-info-data-button').on('click', function (e) {
    e.preventDefault();
    var clipboard_text = jQuery('#copy-site-info-data-button').data('clipboard-text');
    var clipboard_text_copied = copyFunctionBlock(clipboard_text);
    if (clipboard_text_copied) {
        jQuery('.copied-success').css('display', 'inline-block');
        setTimeout(function () {
            jQuery('#copy_textarea').css('display', 'none');
            jQuery('.copied-success').css('display', 'none');
        }, 2000);
    }

});

var switch_status = '';
jQuery("#mh-analytics-switch").on('change', function (e) {
    e.preventDefault();
    if (jQuery(this).is(':checked')) {
        switch_status = 'on';
        jQuery(this).val(switch_status);
        jQuery(this).attr('checked');

    } else {
        var switch_status = 'off';
        jQuery(this).val(switch_status);
        jQuery(this).removeAttr('checked');
    }
    var data = {
        action: 'send_data_to_server_action',
        switch_status: switch_status,
        nonce: wmhObj.nonce
    };
    jQuery.ajax({
        type: "POST",
        url: wmhObj.ajaxurl,
        data: data,
        beforeSend: function () {
            jQuery(".analytics-loader").css(
                "display",
                "inline-block"
            );
        },
        success: function (res) {
            var res = JSON.parse(res);
            alert(res.message);
        },
        complete: function (res) {
            jQuery(".analytics-loader").css("display", "none");
        },
    });
});

/* opt-in notice — Allow button */
jQuery(document).on('click', '#wmh-optin-allow', function (e) {
    e.preventDefault();
    jQuery('.wmh-optin-loader').css('display', 'inline-block');
    jQuery('#wmh-optin-allow, #wmh-optin-decline').attr('disabled', true);
    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: { action: 'send_data_to_server_action', switch_status: 'on', nonce: wmhObj.nonce },
        complete: function () { location.reload(); }
    });
});

/* opt-in notice — No Thanks button */
jQuery(document).on('click', '#wmh-optin-decline', function (e) {
    e.preventDefault();
    jQuery('.wmh-optin-loader').css('display', 'inline-block');
    jQuery('#wmh-optin-allow, #wmh-optin-decline').attr('disabled', true);
    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: { action: 'send_data_to_server_action', switch_status: 'off', nonce: wmhObj.nonce },
        complete: function () { location.reload(); }
    });
});

/* Delete page media button */
jQuery(document).on("click", "#trash-page-media-button", function (e) {
    e.preventDefault();
    if (confirm(wmhObj.msg_array.trash_page_confirm_1)) {
        if (confirm(wmhObj.msg_array.trash_page_confirm_2)) {
            /* Disable and unclickable delete all media button. */
            jQuery('#delete-all-media-button').attr('disabled', 'disabled');
            jQuery('#delete-all-media-button').css('cursor', 'not-allowed')
            /* Disable and unclickable scan button. */
            jQuery('#scan-button').attr('disabled', 'disabled');
            jQuery('#scan-button').css('cursor', 'not-allowed');
            /* Disable and unclickable delete-page-media-button  */
            jQuery('#trash-page-media-button').attr('disabled', 'disabled');
            jQuery('#trash-page-media-button').css('cursor', 'not-allowed');
            /* Disable and unclickable download page media button */
            jQuery('#create-page-zip-button').attr('disabled', 'disabled');
            jQuery('#create-page-zip-button').css('cursor', 'not-allowed');
            var data = jQuery("#wmh-trash-page-media-form").serialize();
            jQuery.ajax({
                type: "POST",
                url: wmhObj.ajaxurl,
                data: data,
                timeout: 120000,
                beforeSend: function () {
                    jQuery(".trash-page-media-loader").css("display", "inline-block");
                },
                success: function (raw) {
                    var res;
                    try { res = JSON.parse(raw); } catch (e) {
                        alert('Unexpected server response. Please check the error log.\n\n' + raw.substring(0, 400));
                        return;
                    }
                    if (res.flg == 1) {
                        sessionStorage.setItem('wmh_trash_notice', JSON.stringify({
                            message: res.message,
                            count: res.trashed_count || 0
                        }));
                        location.reload();
                    } else if (res.flg == -1) {
                        alert('Error: ' + (res.message || 'Unknown error') + '\n\nPlease check the debug log.');
                    } else {
                        alert(res.message || 'An unknown error occurred.');
                    }
                },
                error: function (xhr, status, error) {
                    alert('Network or server error trashing page media.\nStatus: ' + status + '\nError: ' + error
                        + (xhr.responseText ? '\n\n' + xhr.responseText.substring(0, 400) : ''));
                },
                complete: function () {
                    jQuery('#scan-button').removeAttr('disabled').css('cursor', '');
                    jQuery('#delete-all-media-button').removeAttr('disabled').css('cursor', '');
                    jQuery('#trash-page-media-button').removeAttr('disabled').css('cursor', '');
                    jQuery('#create-page-zip-button').removeAttr('disabled').css('cursor', '');
                    jQuery(".trash-page-media-loader").css("display", "none");
                },
            });
        }
    }
});

/* Clear error log from datatable */
jQuery(document).on('click', '#clear-error-log', function (e) {
    e.preventDefault();
    var data = jQuery("#clear-error-log-form").serialize();
    jQuery.ajax({
        type: "POST",
        url: wmhObj.ajaxurl,
        data: data,
        beforeSend: function () {
            jQuery('.error-log-btn-loader').css('display', 'inline-block');
        },
        success: function (res) {
            var res = JSON.parse(res);
            if (res.flg == 1) {
                alert(res.message);
                location.reload();
            }
        },
        complete: function (res) {
            jQuery('.error-log-btn-loader').css('display', 'none');
        },
    });
});

/* Restore default file exe for media scan */
jQuery(document).on('click', '#restore-default-file-exe', function (e) {
    e.preventDefault();
    if (confirm(wmhObj.msg_array.restore_default_file_exe_msg_1)) {
        var string_exe = 'ttf, otf, woff, woff2, cff, cff2, eot, css';
        $string_exe = jQuery('#ex-file-ex').val(string_exe);
        if ($string_exe) {
            alert('Default File Exclusion extension restored. Make sure to Save Settings.');
        }
    }
});


function fnWmhDeletedMEdiaList() {
    jQuery('#wmh-deleted-media-list').DataTable({
        bDestroy: true,
        bJQueryUI: true,
        paging: true,
        order: [[1, 'desc']],
        ajax: {
            type: 'POST',
            url: wmhObj.ajaxurl,
            data: {
                action: 'get_deleted_media_list',
                nonce: wmhObj.nonce
            },
        },
        columns: [
            { data: 'id' },
            { data: 'post_id' },
            { data: 'url' },
            { data: 'date_created' },
            { data: 'date_updated' }
        ],
        columnDefs: [
            { "targets": [0, 1, 2, 3, 4], "orderable": false }
        ]
    });
}

/* deleted media list from datatable */
jQuery(document).on('click', '#deleted-list-btn', function (e) {
    e.preventDefault();
    var data = jQuery("#deleted-media-list-form").serialize();
    jQuery.ajax({
        type: "POST",
        url: wmhObj.ajaxurl,
        data: data,
        beforeSend: function () {
            jQuery('.deleted-list-btn-loader').css('display', 'inline-block');
        },
        success: function (res) {
            var res = JSON.parse(res);
            if (res.flg == 1) {
                alert(res.message);
                location.reload();
            }
        },
        complete: function (res) {
            jQuery('.deleted-list-btn-loader').css('display', 'none');
        },
    });
});

/* anonymous analytics permission */
jQuery(document).on('click', '#wmh-aap-btn', function (e) {
    e.preventDefault();
    jQuery.ajax({
        type: "POST",
        url: wmhObj.ajaxurl,
        data: {
            action: 'wmh_aap_action',
            nonce: wmhObj.nonce
        },
        success: function (res) {
            var res = JSON.parse(res);
            alert(res.message);
            location.reload();
        }
    });
});

/* permanently close anonymous analytics permission */
jQuery(document).on("click", ".analyzing-notice .notice-dismiss", function (e) {
    e.preventDefault();
    jQuery.ajax({
        type: "POST",
        url: wmhObj.ajaxurl,
        data: {
            action: 'wmh_aap_close_notice_permanently_action',
            nonce: wmhObj.nonce
        },
        success: function (res) {
            //var res = JSON.parse(res);
        }
    });
});

/* update database by version */
jQuery(document).on('click', '#wmh-update-database', function (e) {
    e.preventDefault();
    var data = jQuery('#wmh-database-update-from').serialize();
    jQuery.ajax({
        type: "POST",
        url: wmhObj.ajaxurl,
        data: data,
        beforeSend: function () {
            jQuery('.wmh-update-database-loader').css('display', 'inline-block');
        },
        success: function (res) {
            var res = JSON.parse(res);
            location.reload();
        },
        complete: function (res) {
            jQuery('.wmh-update-database-loader').css('display', 'none');
        },
    });
});

/* Bulk restore media */
jQuery(document).on('click', '#wmh-bulk-restore-btn', function (e) {
    e.preventDefault();
    var data = jQuery('#wmh-bulk-restore-form').serialize();
    fnWmhBulkTrashRestore(data);
});

function fnWmhBulkTrashRestore(data) {
    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: data,
        beforeSend: function () {
            jQuery('.bulk-restore-loader').css('display', 'inline-block');
            jQuery('.wmh-bulk-restore-progress-bar').css('display', 'flex');
            jQuery('.wmh-bulk-restore-message').css('display', 'inline-block').text('Restoring ... (1/2)');
        },
        success: function (res) {
            var res = JSON.parse(res);
            var progressBar = res.progress_bar;
            jQuery('.wmh-bulk-restore-progress-bar div').text(progressBar + '%').css('width', progressBar + '%');
            if (res.flg == 0) {
                alert(res.message);
                location.reload();
            } else if (res.flg == 1) {
                var ajax_call = res.ajax_call;
                jQuery('#wmh-bulk-restore-ajax-call').val(ajax_call);
                var data = jQuery('#wmh-bulk-restore-form').serialize();
                fnWmhBulkTrashRestore(data);
            } else if (res.flg == 2) {
                alert(res.message);
                window.location.href = '?page=wmh-media-hygiene';
            }
        },
    });
}

function fn_wmh_delete_permanently_single_image(post_id = '', file_size = '') {
    var data = {
        action: 'delete_permanently_single_image_call',
        post_id: post_id,
        file_size: file_size,
        nonce: wmhObj.nonce
    };
    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: data,
        timeout: 60000,
        beforeSend: function () {
            jQuery('.delete-loader-' + post_id).css('display', 'inline-block');
        },
        success: function (raw) {
            var res;
            try { res = JSON.parse(raw); } catch (e) {
                alert('Unexpected server response. Please check the error log.\n\n' + raw.substring(0, 400));
                return;
            }
            if (res.flg == 1) {
                alert(res.message);
                location.reload();
            } else if (res.flg == -1) {
                alert('Error: ' + (res.message || 'Unknown error') + '\n\nPlease check the debug log.');
            } else {
                alert(res.message || 'An unknown error occurred.');
            }
        },
        error: function (xhr, status, error) {
            alert('Network or server error deleting media.\nStatus: ' + status + '\nError: ' + error
                + (xhr.responseText ? '\n\n' + xhr.responseText.substring(0, 400) : ''));
        },
        complete: function () {
            jQuery('.delete-loader-' + post_id).css('display', 'none');
        }
    });
}

/* delete permanently */
jQuery(document).on('click', '#wmh-delete-permanently-btn', function (e) {
    e.preventDefault();
    var data = jQuery('#wmh-delete-permanently-form').serialize();
    fnWmhBulkDeletePermanently(data);
});

function fnWmhBulkDeletePermanently(data) {
    jQuery.ajax({
        type: 'POST',
        url: wmhObj.ajaxurl,
        data: data,
        timeout: 120000,
        beforeSend: function () {
            jQuery('.bulk-delete-permanently-loader').css('display', 'inline-block');
            jQuery('.wmh-delete-permanently-progress-bar').css('display', 'flex');
            jQuery('.wmh-delete-permanently-message').css('display', 'inline-block').text('Deleting ... (1/2)');
        },
        success: function (raw) {
            var res;
            try { res = JSON.parse(raw); } catch (e) {
                jQuery('.bulk-delete-permanently-loader').css('display', 'none');
                jQuery('#wmh-delete-permanently-btn').prop('disabled', false);
                alert('Unexpected server response. Please check the error log.\n\n' + raw.substring(0, 400));
                return;
            }
            var progressBar = res.progress_bar || 0;
            jQuery('.wmh-delete-permanently-progress-bar div').text(progressBar + '%').css('width', progressBar + '%');

            if (res.flg == 0) {
                jQuery('.bulk-delete-permanently-loader').css('display', 'none');
                alert(res.message);
                location.reload();
            } else if (res.flg == 1) {
                jQuery('#wmh-delete-permanently-ajax-call').val(res.ajax_call);
                var next = jQuery('#wmh-delete-permanently-form').serialize();
                fnWmhBulkDeletePermanently(next);
            } else if (res.flg == 2) {
                alert(res.message);
                window.location.href = '?page=wmh-media-hygiene';
            } else if (res.flg == -1) {
                jQuery('.bulk-delete-permanently-loader').css('display', 'none');
                jQuery('#wmh-delete-permanently-btn').prop('disabled', false);
                alert('Error: ' + (res.message || 'Unknown error') + '\n\nPlease check the debug log.');
            }
        },
        error: function (xhr, status, error) {
            jQuery('.bulk-delete-permanently-loader').css('display', 'none');
            jQuery('#wmh-delete-permanently-btn').prop('disabled', false);
            alert('Network or server error during bulk delete permanently.\nStatus: ' + status + '\nError: ' + error
                + (xhr.responseText ? '\n\n' + xhr.responseText.substring(0, 400) : ''));
        }
    });
}