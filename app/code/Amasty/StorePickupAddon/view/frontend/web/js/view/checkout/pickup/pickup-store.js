/**
 * Pickup Store UIElement for Checkout page
 * Nested from Main Pickup Store UIElement
 */
define([
    'Amasty_StorePickupWithLocator/js/view/pickup/pickup-store',
    'Amasty_StorePickupWithLocator/js/model/pickup/pickup-data-resolver',
    'Amasty_StorePickupWithLocator/js/model/shipping-address-service',
    'Amasty_StorePickupAddon/js/model/store-pickup-processor'
], function (PickupStore, pickupDataResolver, addressService, storePickupProcessor) {
    'use strict';

    return PickupStore.extend({
        defaults: {
            visible: false,
            required: true,
            template: 'Amasty_StorePickupAddon/checkout/pickup/pickup-store',
        },

        storeObserver: function () {
            this._super();

            addressService.selectStoreAddress(pickupDataResolver.getCurrentStoreData());
        },

        pickupStateObserver: function (isActive) {
            this._super();

            if (!isActive) {
                addressService.resetAddress();
            }

            this.visible(isActive);
        },

        selectStore: function () {
            var pickupStore = storePickupProcessor.getFirstSelectedStore();

            if (pickupStore) {
                // jQuery('.ampickup-wrapper select option[value=2]').click();
            }
        }
    });
});
