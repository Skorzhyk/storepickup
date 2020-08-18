/**
 * Pickup Fieldset UIComponent for Checkout page
 * Nested from Main Pickup Fieldset UIComponent
 */
define([
    'Amasty_StorePickupWithLocator/js/view/pickup/pickup-fieldset',
    'Amasty_StorePickupWithLocator/js/action/shipping-address-form',
    'Amasty_StorePickupWithLocator/js/model/pickup'
], function (PickupFieldset, shippingAddressFormActions, pickup) {
    'use strict';

    return PickupFieldset.extend({
        defaults: {
            visible: false,
            isToggleShippingAllowed: true,
            template: 'Amasty_StorePickupWithLocator/checkout/pickup/pickup-fieldset',
            listens: {
                '${ $.provider }:amStorepickup.data.validate': 'validate'
            }
        },

        validate: function () {
            this._delegate(['validate']);
        },

        initObservable: function () {
            this._super()
                .observe('visible');

            pickup.isPickup.subscribe(this.pickupStateObserver, this);

            return this;
        },

        /**
         * @param {Boolean} isActive
         */
        pickupStateObserver: function (isActive) {
            this.visible(isActive);

            if (!this.isToggleShippingAllowed) {
                return;
            }

            try {
                shippingAddressFormActions.toggle(!isActive);
            } catch (e) {
                console.error(e);
            }
        }
    });
});
