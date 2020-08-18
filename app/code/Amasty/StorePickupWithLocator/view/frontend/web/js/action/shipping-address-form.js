define([
    'jquery',
    'underscore',
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/address-list',
    'Magento_Ui/js/lib/knockout/extender/bound-nodes',
    'uiRegistry'
], function ($, _, ko, quote, addressList, boundNodes, registry) {
    'use strict';

    return {
        options: {
            protectedFields : [], // Array of fields protected from hiding. Contain UI indexes of fields
            shippingSectionRegistrySelector: 'index = shippingAddress',
            shippingFieldsetRegistrySelector: 'index = shipping-address-fieldset',
            addressListRegistrySelector: 'index = address-list',
            addressComponentsForToggle: [],
            addressComponentsToggled: []
        },
        currentState: null,

        /**
         * Toggle shipping address form or list depending on Store Pickup shipping method
         *
         * @param {Boolean} state - false for hide shipping fieldset. true for show
         */
        toggle: function (state) {
            var shippingSection = registry.get(this.options.shippingSectionRegistrySelector),
                fieldsForToggle,
                fieldset;

            if (this.currentState === state) {
                return;
            }

            if (addressList().length === 0) {
                try {
                    fieldset = boundNodes.get(shippingSection);
                    $(fieldset).find('#co-shipping-form').toggle(state);
                } catch (e) {
                    console.error(e);
                }
                fieldsForToggle = this.getShippingItemsForToggle();

                _.each(fieldsForToggle, function (item) {
                    try {
                        // eslint-disable-next-line eqeqeq
                        if (state === false && item.visible() != state) {
                            this.options.addressComponentsToggled.push(item);
                        }

                        item.visible(state);
                    } catch (e) {
                        console.error(e);
                    }
                }.bind(this));
            } else {
                shippingSection.isNewAddressAdded(!state);
                registry.get(this.options.addressListRegistrySelector, function (addressListSection) {
                    var shippingListNodes;

                    try {
                        if (ko.isWriteableObservable(addressListSection.visible)) {
                            addressListSection.visible(state);
                        } else {
                            shippingListNodes = boundNodes.get(addressListSection);
                            $(shippingListNodes).toggle(state);
                        }
                    } catch (e) {
                        console.error(e);
                    }
                });
            }

            this.currentState = state;
        },

        /**
         * Find shipping address form fields for toggle
         */
        getShippingItemsForToggle: function () {
            var fieldsForToggle;

            if (this.options.addressComponentsToggled.length) {
                fieldsForToggle = this.options.addressComponentsToggled;
                this.options.addressComponentsToggled = [];

                return fieldsForToggle;
            }

            if (!this.options.addressComponentsForToggle.length) {
                this.filterElements(registry.get(this.options.shippingFieldsetRegistrySelector).elems());
            }

            return this.options.addressComponentsForToggle;
        },

        /**
         * Extract all fields form fieldsets
         *
         * @param {Array} elems
         */
        filterElements: function (elems) {
            if (!elems || !elems.length) {
                return;
            }

            _.each(elems, function (element) {
                if (this._isCollection(element)) {
                    this.filterElements(element.elems());

                    return;// continue
                }

                if (!this.options.protectedFields.includes(element.index)
                    && ko.isObservable(element.visible)
                ) {
                    this.options.addressComponentsForToggle.push(element);
                }
            }.bind(this));
        },

        /**
         * Is component are collection
         *
         * @param {Object} element
         * @returns {Boolean}
         * @private
         */
        _isCollection: function (element) {
            return typeof element.initChildCount === 'number';
        }
    };
});
