/**
 * Pickup Fieldset UIComponent for Paypal express review page
 * Nested from Checkout Pickup Fieldset UIComponent
 */
define([
    'jquery',
    'mage/storage',
    'uiRegistry',
    'Magento_Checkout/js/model/quote',
    'Amasty_StorePickupWithLocator/js/view/checkout/pickup/pickup-fieldset',
    'Amasty_StorePickupWithLocator/js/model/pickup',
    'Amasty_StorePickupWithLocator/js/model/pickup/pickup-data-resolver',
    'Amasty_StorePickupWithLocator/js/model/resource-url-manager',
    'Amasty_StorePickupWithLocator/js/action/messages-resolver',
    'mage/translate'
], function (
    $,
    storage,
    registry,
    quote,
    PickupFieldset,
    pickup,
    pickupDataResolver,
    urlManager,
    messagesResolver
) {
    'use strict';

    return PickupFieldset.extend({
        defaults: {
            pickupMethodName: 'amstorepickup_amstorepickup',
            isToggleShippingAllowed: false,
            storeErrorMessage: $.mage.__('Please, choose a store where you would like to pick up your order'),
            selectors: {
                shippingMethodInput: '#shipping-method',
                orderDetails: '#details-reload',
                reviewForm: '#order-review-form',
                orderReviewSubmit: '#review-button',
                waitLoadingContainer: '#review-please-wait'
            }
        },

        initialize: function () {
            this._super();

            // Paypal Express Checkout isn't a UIComponent structure, so here we have to use jquery selection.
            var shippingMethodElement = $(this.selectors.shippingMethodInput);

            pickup.isPickup(shippingMethodElement.val() === this.pickupMethodName);
            shippingMethodElement.on('change', this.onShippingMethodChange.bind(this));
            $(this.selectors.orderReviewSubmit).on('click', this.onPlaceOrder.bind(this));

            return this;
        },

        onPlaceOrder: function () {
            var quoteId = quote.getQuoteId(),
                pickupInfo = registry.get('checkoutProvider').get('amstorepickup'),
                storeId = pickupInfo['am_pickup_store'],
                request = {
                    quote_id: quoteId,
                    locationId: storeId
                },
                date = pickupInfo['am_pickup_date'],
                timePeriod;

            if (pickup.isPickup() && !pickupDataResolver.storeId()) {
                messagesResolver.addMessage({
                    type: 'error',
                    text: this.storeErrorMessage
                });

                return false;
            }

            if (storeId && date) {
                timePeriod = pickupInfo['am_pickup_time'];
                request.date = date;

                if (timePeriod) {
                    request.timePeriod = timePeriod;
                }
            }

            if (storeId) {
                $(this.selectors.waitLoadingContainer).show();

                storage.post(
                    urlManager.getMethodUrl(quoteId, 'saveSelectedPickupValues'),
                    JSON.stringify(request),
                    false
                ).done(function () {
                    $(this.selectors.waitLoadingContainer).hide();
                    $(this.selectors.reviewForm).submit();
                }.bind(this)).fail(function (response) {
                    messagesResolver.addMessage({
                        type: 'error',
                        text: response.responseJSON.message
                    });
                    $(this.selectors.waitLoadingContainer).hide();
                    return false;
                }.bind(this));

                return false;
            }
        },

        onShippingMethodChange: function (event) {
            var storeId = null,
                isPickup = event.target.value === this.pickupMethodName;

            pickup.isPickup(isPickup);

            if (isPickup) {
                storeId = pickupDataResolver.storeId();
            }

            $.ajax({
                url: urlManager.getPathUrl('amstorepickup/paypal/saveShippingAddress'),
                data: {location_id: storeId},
                type: 'post',
                success: function (response) {
                    if (response) {
                        $(this.selectors.orderDetails).html(response);
                    }
                }.bind(this)
            });
        }
    });
});
