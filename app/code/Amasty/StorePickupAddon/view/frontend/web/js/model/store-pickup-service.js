define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/address-converter',
        'Magento_Checkout/js/action/select-shipping-address',
        'Amasty_Checkout/js/model/shipping-registry',
        'uiRegistry'
    ],
    function (
        $,
        quote,
        addressConverter,
        selectShippingAddress,
        shippingRegistry,
        registry
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

            refreshShippingAddress: function () {
                var address = quote.shippingAddress();
                address.city = null;
                address.countryId = null;
                address.firstname = null;
                address.lastname = null;
                address.middlename = null;
                address.postcode = null;
                address.region = null;
                address.regionCode = null;
                address.regionId = null;
                address.street = [];
                address.telephone = [];

                var addressData = addressConverter.quoteAddressToFormAddressData(address);

                registry.get('checkoutProvider').set(
                    'shippingAddress',
                    addressData
                );
                selectShippingAddress(address);
            }
        }
    }
);
