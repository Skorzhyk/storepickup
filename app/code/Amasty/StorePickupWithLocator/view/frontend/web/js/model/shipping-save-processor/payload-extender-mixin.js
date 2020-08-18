define([
    'underscore',
    'uiRegistry',
    'mage/utils/wrapper',
    'Amasty_StorePickupWithLocator/js/model/pickup'
], function (_, registry, wrapper, pickup) {
    'use strict';

    return function (payloadExtender) {
        return wrapper.wrap(payloadExtender, function (original, payload) {
            var payloadOriginal = original(payload),
                payloadWithPickupInfo = payloadOriginal,
                pickupInfo;

            if (pickup.isPickup()) {
                pickupInfo = registry.get('checkoutProvider').get('amstorepickup');

                if (pickupInfo && pickupInfo['am_pickup_store'] && pickupInfo['am_pickup_store'].id) {
                    pickupInfo['am_pickup_store'] = pickupInfo['am_pickup_store'].id;
                }

                if (_.isUndefined(payloadWithPickupInfo.addressInformation.extension_attributes)) {
                    payloadWithPickupInfo.addressInformation.extension_attributes = {};
                }

                if (pickupInfo) {
                    _.extend(payloadWithPickupInfo.addressInformation.extension_attributes, pickupInfo);
                }
            }

            return payloadWithPickupInfo;
        });
    };
});
