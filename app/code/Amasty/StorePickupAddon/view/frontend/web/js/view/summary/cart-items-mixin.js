define([
    'Amasty_StorePickupWithLocator/js/model/pickup/pickup-data-resolver'
], function (pickupDataResolver) {
    'use strict';

    return function (CartItems) {
        return CartItems.extend({
            defaults: {
                template: 'Amasty_StorePickupAddon/summary/cart-items'
            },
            itemsWithDelivery: null,

            setItems: function (items) {
                if (this.itemsWithDelivery === null) {
                    this.itemsWithDelivery = items;
                }

                this._super();
            },

            // Update in phase 3. Use custom options.
            getGroupedItems: function () {
                var pickupData, stores, indexedStores;
                pickupData = pickupDataResolver.pickupData;
                stores = pickupData().stores;
                indexedStores = {};
                for (var key in stores) {
                    indexedStores[stores[key].id] = stores[key];
                }

                var items = this.itemsWithDelivery;
                var groups = {};

                items.forEach(function (item) {
                    if (!groups.hasOwnProperty(item.delivery)) {
                        var delivery = 'Shipping delivery';
                        if (indexedStores.hasOwnProperty(item.delivery)) {
                            delivery = 'Pickup from ' + indexedStores[item.delivery].name;
                        }
                        groups[item.delivery] = {
                            'delivery': delivery,
                            'items': []
                        };
                    }
                    groups[item.delivery].items.push(item);
                });

                var groupsArray = [];
                for (var deliveryKey in groups) {
                    groupsArray.push(groups[deliveryKey]);
                }

                return groupsArray;
            }
        });
    }
});
