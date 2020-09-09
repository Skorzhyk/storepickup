define([
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Amasty_StorePickupAddon/js/model/store-pickup-service',
    'Magento_Checkout/js/action/select-shipping-method',
    'Amasty_StorePickupWithLocator/js/model/pickup/pickup-data-resolver',
    'Amasty_StorePickupWithLocator/js/model/store/address',
    'Amasty_Checkout/js/model/shipping-registry',
    'rjsResolver',
    'uiRegistry'
],
function(
    ko,
    Component,
    quote,
    storePickupService,
    selectShippingMethodAction,
    pickupDataResolver,
    storeAddress,
    shippingRegistry,
    onLoad,
    registry
) {
    'use strict';

    return Component.extend({
        initialize: function() {
            this._super();
            self = this;

            onLoad(function () {
                self.processInitial();
            });
        },

        processInitial: function () {
            if (storePickupService.onlyPickup()) {
                var pickupShippingMethod = window.checkoutConfig.quoteData['pickup_shipping_method'];
                if (pickupShippingMethod) {
                    registry
                        .get('checkoutProvider')
                        .set('amstorepickup', {am_pickup_store: storePickupService.getFirstSelectedStore()});

                    selectShippingMethodAction(pickupShippingMethod);
                }
            }

            var pickupData = pickupDataResolver.pickupData;
            var stores = pickupData().stores;
            var storeAddressModel = storeAddress;
            stores.forEach(function (store) {
                var storeAddress = new storeAddressModel(store);
                if (shippingRegistry._compareObjectsData(quote.shippingAddress(), storeAddress)) {
                    storePickupService.refreshShippingAddress();
                }
            });
        }
    });
});
