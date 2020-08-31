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
                var onlyPickup = true;

                quote.getItems().forEach(function (item) {
                    var options = item['options'];
                    if (options.length > 0) {
                        options.forEach(function (option) {
                            if (option['label'] === 'Delivery' && option['value'] == 0) {
                                onlyPickup = false;
                            }
                        });
                    }
                });

                return onlyPickup;
            },

            getFirstSelectedStore: function () {
                var pickupStore = false;
                quote.getItems().forEach(function (item) {
                    item['options'].forEach(function (option) {
                        if (option['label'] === 'Delivery') {
                            pickupStore = option['value'];
                        }
                    });
                });

                return pickupStore;
            },

            setShippingAddressDataAsConfig: function () {
                var shippingAddressData = window.checkoutConfig['shippingAddressFromData'];

                var input;
                for (var field in shippingAddressData) {
                    input = $('#checkout-step-shipping input[name=' + field + ']');
                    input.val(shippingAddressData[field]);
                    input.trigger('change');
                }
            }
        }
    }
);
