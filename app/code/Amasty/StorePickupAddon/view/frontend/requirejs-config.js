config = {
    "map": {
        "*": {
            'Amasty_Checkout/js/model/checkout-data-resolver-mixin':
                'Amasty_StorePickupAddon/js/model/checkout-data-resolver-mixin',
            'Amasty_Checkout/template/onepage/shipping/methods':
                'Amasty_StorePickupAddon/template/onepage/shipping/methods'
        }
    },
    config: {
        mixins: {
            'Amasty_Checkout/js/model/one-step-layout': {
                'Amasty_StorePickupAddon/js/model/one-step-layout-mixin': true
            },
            'Magento_Checkout/js/view/shipping': {
                'Amasty_StorePickupAddon/js/view/shipping-mixin': true
            },
            'Magento_Checkout/js/view/summary/cart-items': {
                'Amasty_StorePickupAddon/js/view/summary/cart-items-mixin': true
            }
        }
    }
};
