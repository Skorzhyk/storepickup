define(
    [
        'Amasty_StorePickupAddon/js/model/store-pickup-service'
    ],
    function (
        storePickupService
    ) {
        'use strict';

        return function (Shipping) {
            return Shipping.extend({
                getDisplayClass: function (carrierCode) {
                    if (carrierCode === 'amstorepickup' && !storePickupService.onlyPickup()) {
                        return 'no-display';
                    }

                    return '';
                }
            });
        };
    }
);
