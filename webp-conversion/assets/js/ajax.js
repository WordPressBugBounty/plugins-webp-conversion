jQuery(document).ready(function ($) {

    $(document).on('click', '.webpc_convert_single', function (e) {
        e.preventDefault();
        const button = $(this);
        const spinner = button.next('.webpc-single-attach-spinner');
        const imageId = button.data('id');

        spinner.show();

        $.ajax({
            type: 'POST',
            url: webp_conversion.ajax_url,
            data: {
                action: 'webpc_convert_single',
                image_id: imageId,
                current_page: window.location.pathname,
                nonce: webp_conversion.nonce
            },
            success: function (response) {
                if (response.success) {
                    if (response.data.url === 'reload') {
                        location.reload();
                    } else {
                        window.location.href = response.data.url;
                    }
                } else {
                    spinner.hide();
                    alert(webp_conversion.message.conversion_failed);
                }
            },
            error: function () {
                spinner.hide();
                alert(webp_conversion.message.server_conversion_error);
            }
        });
    });

    $(document).on('click', '.webpc_restore_single', function (e) {
        e.preventDefault();
        const button = $(this);
        const spinner = button.next('.webpc-single-attach-spinner');
        const imageId = button.data('id');

        spinner.show();

        $.ajax({
            type: 'POST',
            url: webp_conversion.ajax_url,
            data: {
                action: 'webpc_restore_single',
                image_id: imageId,
                current_page: window.location.pathname,
                nonce: webp_conversion.nonce
            },
            success: function (response) {
                if (response.success) {
                    if (response.data.url === 'reload') {
                        location.reload();
                    } else {
                        window.location.href = response.data.url;
                    }
                } else {
                    spinner.hide();
                    alert(webp_conversion.message.restoring_failed);
                }
            },
            error: function () {
                spinner.hide();
                alert(webp_conversion.message.server_restoring_error);
            }
        });
    });

    $(document).on('click', '.webpc_remove_single', function (e) {
        e.preventDefault();
        const button = $(this);
        const spinner = button.next('.webpc-single-attach-spinner');
        const imageId = button.data('id');

        spinner.show();

        $.ajax({
            type: 'POST',
            url: webp_conversion.ajax_url,
            data: {
                action: 'webpc_remove_single',
                image_id: imageId,
                current_page: window.location.pathname,
                nonce: webp_conversion.nonce
            },
            success: function (response) {
                if (response.success) {
                    if (response.data.url === 'reload') {
                        location.reload();
                    } else {
                        window.location.href = response.data.url;
                    }
                } else {
                    spinner.hide();
                    alert(webp_conversion.message.removing_failed);
                }
            },
            error: function () {
                spinner.hide();
                alert(webp_conversion.message.server_removing_error);
            }
        });
    });

    function runBatchAction(actionName) {
        const counterSpinner = $('.webpc-counter-and-spinner');

        let imageIds = [];
        $('.attachments .attachment.selected').each(function () {
            imageIds.push($(this).data('id'));
        });

        if (imageIds.length === 0) {
            alert(webp_conversion.message.no_images_selected);
            return;
        }

        counterSpinner.show();

        let counter = 0;
        let redirectUrl = '';
        let finalRedirectUrl = '';
        let converted = 0;

        function processBatch() {
            const batch = imageIds.slice(counter, counter + 5);

            if (batch.length === 0) {
                finalRedirectUrl = redirectUrl + converted;
                window.location.href = finalRedirectUrl;
                return;
            }

            $.ajax({
                type: 'POST',
                url: webp_conversion.ajax_url,
                data: {
                    action: actionName,
                    image_ids: batch,
                    nonce: webp_conversion.nonce
                },
                success: function (response) {
                    if (response.data.url) {
                        redirectUrl = response.data.url;
                        converted += response.data.converted;
                        counter += 5;

                        $('#webpc-converted-count').text(converted);

                        processBatch();
                    } else {
                        counterSpinner.hide();
                        alert(webp_conversion.message.batch_process_error);
                    }
                },
                error: function () {
                    counterSpinner.hide();

                    const hasNextBatch = imageIds.slice(counter + 5, counter + 10).length > 0;

                    if (hasNextBatch) {
                        let proceed = confirm(getBatchErrorMessage(actionName, batch, true));

                        if (proceed) {
                            counterSpinner.show();
                            counter += 5;
                            processBatch();
                        } else {
                            return;
                        }
                    } else {
                        alert(getBatchErrorMessage(actionName, batch));
                    }
                },
            });
        }

        processBatch();
    }

    function getBatchErrorMessage(actionName, batch, continue_batch = false) {

        let message = webp_conversion.message.server_batch_process_error + batch;

        if (actionName === 'webpc_convert_selected' || actionName === 'webpc_restore_selected') {
            message = webp_conversion.message.server_batch_complex_task_error + batch;
        }

        if (continue_batch) {
            message += "." + "\n" + webp_conversion.message.continue_processing;
        }

        return message;

    }

    $(document).on('click', '.webpc_convert_selected', function (e) {
        e.preventDefault();
        runBatchAction('webpc_convert_selected');
    });

    $(document).on('click', '.webpc_restore_selected', function (e) {
        e.preventDefault();
        runBatchAction('webpc_restore_selected');
    });

    $(document).on('click', '.webpc_remove_originals_selected', function (e) {
        e.preventDefault();
        runBatchAction('webpc_remove_originals_selected');
    });

    $(document).on('click', '#webpc_remove_originals_button', function (e) {
        e.preventDefault();

        if (!confirm(webp_conversion.message.are_you_sure_to_remove_originals)) {
            return;
        }

        $.ajax({
            type: 'POST',
            url: webp_conversion.ajax_url,
            data: {
                action: 'webpc_remove_all_originals',
                nonce: webp_conversion.nonce
            },
            success: function (response) {
                if (response.success) {
                    $('#webpc-notice')
                        .removeClass('notice-error')
                        .addClass('notice-success')
                        .find('p').text(response.data.message || 'Success');

                    $('#webpc-notice').show().delay(3000).fadeOut();
                } else {
                    $('#webpc-notice')
                        .removeClass('notice-success')
                        .addClass('notice-error')
                        .find('p').text(response.data.message || 'Error');

                    $('#webpc-notice').show().delay(3000).fadeOut();
                }

            },
            error: function () {
                alert('Error occurred while deleting original images.');
            }
        });
    });


    $('#webpc-settings-form').on('submit', function (event) {
        event.preventDefault();

        const formData = $(this).serialize();

        $.post(ajaxurl, formData, function (response) {
            if (response.success) {
                $('#webpc-notice')
                    .removeClass('notice-error')
                    .addClass('notice-success')
                    .find('p').text(response.data.message || 'Success');

                $('#webpc-notice').show().delay(3000).fadeOut();
            } else {
                $('#webpc-notice')
                    .removeClass('notice-success')
                    .addClass('notice-error')
                    .find('p').text(response.data.message || 'Error');

                $('#webpc-notice').show().delay(3000).fadeOut();
            }

        });

    });

    const webpcNotice = $('#message.webpc-notice');
    if (webpcNotice.length) {
        const url = new URL(window.location.href);
        url.searchParams.delete('conversion_done');
        url.searchParams.delete('restoring_done');
        url.searchParams.delete('removing_done');
        window.history.replaceState({}, document.title, url.toString());
    }
});
