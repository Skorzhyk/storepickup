define(
    [
        'Amasty_StorePickupAddon/js/model/store-pickup-service',
        'mage/utils/wrapper'
    ],
    function (
        storePickupService,
        wrapper
    ) {
        'use strict';

        return function (OneStepLayout) {
            var mixin = {
                getBlockClassNames: function (originalAction, blockName) {
                    var classNames = originalAction();

                    if (blockName === 'shipping_method' && storePickupService.onlyPickup()) {
                        classNames += ' no-display';
                    }

                    return classNames;
                }
            }

            wrapper._extend(OneStepLayout, mixin);
            return OneStepLayout;
        };
    }
);
