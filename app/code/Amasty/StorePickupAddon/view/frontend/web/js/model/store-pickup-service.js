define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote'
    ],
    function (
        $,
        quote
    ) {
        'use strict';

        return {
            onlyPickup: function () {
                // Переделать под опции!

                // var onlyPickup = true;
                //
                // quote.getItems().forEach(function (item) {
                //     var options = item['options'];
                //     if (options.length > 0) {
                //         options.forEach(function (option) {
                //             if (option['label'] === 'Delivery' && option['value'] == 0) {
                //                 onlyPickup = false;
                //             }
                //         });
                //     }
                // });

                // return onlyPickup;

                return window.checkoutConfig.quoteData['only_pickup'];
            },

            getFirstSelectedStore: function () {
                // Переделать под опции!

                // var pickupStore = false;
                // quote.getItems().forEach(function (item) {
                //     item['options'].forEach(function (option) {
                //         if (option['label'] === 'Delivery') {
                //             pickupStore = option['value'];
                //         }
                //     });
                // });
                //
                // return pickupStore;

                return window.checkoutConfig.quoteData['delivery'];
            },

            setShippingAddressDataAsConfig: function () {
                // Переделать средствами Мадженты!
                var shippingAddressData = window.checkoutConfig['shippingAddressFromData'];

                $('#shipping-new-address-form input').each(function (key, input) {
                    $(input).val('');
                    $(input).change();
                });

                var input;
                for (var field in shippingAddressData) {
                    input = $('#shipping-new-address-form input[name=' + field + ']');
                    input.val(shippingAddressData[field]);
                    input.trigger('change');
                }
            },

            isPickupDataCleared: function () {
                var isDataCleared = window.checkoutConfig.quoteData['is_pickup_data_cleared'];
                window.checkoutConfig.quoteData['is_pickup_data_cleared'] = false;

                return isDataCleared;
            }
        }
    }
);
