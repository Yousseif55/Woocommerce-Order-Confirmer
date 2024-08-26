jQuery(document).ready(function ($) {
    // Event handler for clicking on the copy message link
    $(document).on('click', '.copying-msg', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var orderId = $(this).data('order-id');
        var orderTotal = $(this).data('order-total');
        var orderAddress = $(this).data('order-address');
        var orderPhone = $(this).data('order-phone');
        var orderItems = $(this).data('order-items');
        var orderPayment = $(this).data('order-payment');
        var orderDate = $(this).data('order-date');
        var $clickedElement = $(this);
        var originalContent = $clickedElement.html();

        $clickedElement.html('<span class="loading-spinner"></span>');

        $.ajax({
            url: copyToClipboardAjax.ajax_url,
            type: 'post',
            data: {
                action: 'get_order_confirmer_template',
                nonce: copyToClipboardAjax.nonce,
                order_id: orderId,
                order_total: orderTotal,
                order_address: orderAddress,
                order_phone: orderPhone,
                order_items: orderItems,
                order_payment: orderPayment,
                order_date: orderDate
            },
            success: function (response) {
                if (response.success) {
                    var customMessage = response.data;

                    // Create a temporary textarea element to hold the text
                    var tempTextarea = document.createElement('textarea');
                    tempTextarea.value = customMessage;
                    document.body.appendChild(tempTextarea);

                    // Select the text and copy it to clipboard
                    tempTextarea.select();
                    tempTextarea.setSelectionRange(0, 99999); // For mobile devices

                    try {
                        document.execCommand('copy');

                    } catch (err) {
                        alert('Failed to copy order details.');
                    }

                    // Remove the temporary textarea
                    document.body.removeChild(tempTextarea);
                }
            },
            error: function (xhr, status, error) {
                alert('AJAX request failed.');
            },
            complete: function () {
                // Hide loading spinner
                $clickedElement.find('.loading-spinner').remove();
                $clickedElement.html(originalContent);
                $clickedElement.css('color', 'red');

            }
        });
    });

    // Event handler for clicking on the billing phone link
    $(document).on('click', '.billing-phone', function (e) {
        e.preventDefault();
        var orderPhone = $(this).data('order-phone');

        // Create a temporary textarea element to hold the text
        var tempTextarea = document.createElement('textarea');
        tempTextarea.value = orderPhone;
        document.body.appendChild(tempTextarea);

        // Select the text and copy it to clipboard
        tempTextarea.select();
        tempTextarea.setSelectionRange(0, 99999); // For mobile devices

        try {
            document.execCommand('copy');

            // Create a span element for the copied indicator
            var copiedIndicator = $('<span class="copied-indicator">&#10003;</span>');

            // Append the copied indicator next to the phone number
            $(this).after(copiedIndicator);

            // Show the copied indicator for 2 seconds
            copiedIndicator.fadeIn(200, function () {
                setTimeout(function () {
                    copiedIndicator.fadeOut(200, function () {
                        copiedIndicator.remove();
                    });
                }, 2000);
            });
        } catch (err) {
            console.error('Failed to copy phone number:', err);
        }

        // Remove the temporary textarea
        document.body.removeChild(tempTextarea);
    });
  
});
