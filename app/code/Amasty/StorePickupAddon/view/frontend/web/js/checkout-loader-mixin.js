define(
    [
        'Amasty_StorePickupAddon/js/model/store-pickup-service',
        'Magento_Checkout/js/view/billing-address',
        'rjsResolver'
    ],
    function (
        storePickupService,
        billingAddress,
        resolver,
        registry
    ) {
        'use strict';

        function hideLoader($loader) {
            storePickupService.processInitial();

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
