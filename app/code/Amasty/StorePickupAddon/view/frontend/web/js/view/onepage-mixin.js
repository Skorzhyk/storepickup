define(
    [
        'Amasty_StorePickupAddon/js/model/store-pickup-service'
    ],
    function (
        storePickupService
    ) {
        'use strict';

        return function (Onepage) {
            return Onepage.extend({
                shippingAddressObserver: function (address) {
                    if (!address) {
                        return;
                    }

                    this.isAccountLoggedInAmazon();

                    if (!storePickupService.onlyPickup()) {
                        this.setShippingToBilling(address);
                    }
                }
            });
        };
    }
);
