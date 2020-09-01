config = {
    "map": {
        "*": {
            'Amasty_Checkout/js/model/checkout-data-resolver-mixin':
                'Amasty_StorePickupAddon/js/model/checkout-data-resolver-mixin'
        }
    },
    config: {
        mixins: {
            'Amasty_Checkout/js/model/one-step-layout': {
                'Amasty_StorePickupAddon/js/model/one-step-layout-mixin': true
            }
            // 'Amasty_Checkout/js/view/shipping-mixin': {
            //     'Amasty_StorePickupAddon/js/view/shipping-mixin': true
            // }
        }
    }
};
