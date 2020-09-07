define(
    [
        'Amasty_StorePickupAddon/js/model/store-pickup-service',
        'Magento_Checkout/js/action/select-shipping-method',
        'Magento_Checkout/js/view/billing-address',
        'rjsResolver',
        'uiRegistry'
    ],
    function (
        storePickupService,
        selectShippingMethodAction,
        billingAddress,
        resolver,
        registry
    ) {
        'use strict';

        function hideLoader($loader) {
            if (storePickupService.onlyPickup()) {
                registry
                    .get('checkoutProvider')
                    .set('amstorepickup', {am_pickup_store: storePickupService.getFirstSelectedStore()});

                selectShippingMethodAction(window.checkoutConfig.quoteData['pickup_shipping_method']);
            }

            $loader.parentNode.removeChild($loader);
        }

        function init(config, $loader) {
            resolver(hideLoader.bind(null, $loader));
        }

        return function (CheckoutLoader) {
            return init;
        };
    }
);
