/**
 * Main Pickup Fieldset UIComponent
 */
define([
    'uiCollection',
    'Magento_Checkout/js/model/quote',
    'Amasty_StorePickupWithLocator/js/model/pickup'
], function (Component, quote, pickup) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Amasty_StorePickupWithLocator/pickup/pickup-fieldset'
        },

        initObservable: function () {
            this._super();

            quote.shippingMethod.subscribe(this.onShippingMethodChange, this);

            return this;
        },

        onShippingMethodChange: function (method) {
            var isStorePickup = method
                && method['carrier_code'] === 'amstorepickup';

            pickup.isPickup(Boolean(isStorePickup));
        }
    });
});
