define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/action/select-shipping-method',
        'Magento_Checkout/js/model/address-converter',
        'Magento_Customer/js/model/address-list',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/action/select-shipping-address',
        'Amasty_Checkout/js/model/payment/vault-payment-resolver',
        'Amasty_StorePickupAddon/js/model/store-pickup-service',
        'uiRegistry',
        'underscore',
        'mage/utils/wrapper'
    ],
    function (
        $,
        quote,
        checkoutData,
        selectShippingMethodAction,
        addressConverter,
        addressList,
        paymentService,
        selectPaymentMethodAction,
        selectShippingAddress,
        vaultResolver,
        storePickupService,
        registry,
        _,
        wrapper
    ) {
        'use strict';

        var defaultShippingMethod = null;

        /**
         * Get default shipping method.
         * Store default shipping method to speedup check.
         *
         * @return {String|false}
         * @private
         */
        function _getDefaultShippingMethod () {
            var provider;

            if (defaultShippingMethod === null) {
                provider = registry.get('checkoutProvider');

                if (provider && provider.defaultShippingMethod) {
                    defaultShippingMethod = provider.defaultShippingMethod;
                } else {
                    defaultShippingMethod = false;
                }
            }

            return defaultShippingMethod;
        }

        return function (target) {
            var mixin = {
                /**
                 * @param {Function} original
                 * @param {Object} ratesData
                 */
                resolveShippingRates: function (original, ratesData) {
                    if (!ratesData || ratesData.length === 0) {
                        selectShippingMethodAction(null);

                        return;
                    }

                    if (ratesData.length === 1) {
                        //set shipping rate if we have only one available shipping rate
                        selectShippingMethodAction(ratesData[0]);

                        return;
                    }

                    var selectedShippingRate = checkoutData.getSelectedShippingRate(),
                        selectedShippingMethod = window.checkoutConfig.selectedShippingMethod,
                        availableRate = false;

                    if (storePickupService.onlyPickup()) {
                        registry
                            .get('checkoutProvider')
                            .set('amstorepickup', {am_pickup_store: storePickupService.getFirstSelectedStore()});

                        availableRate = _.find(ratesData, function (rate) {
                            return rate['carrier_code'] === selectedShippingMethod['carrier_code'] &&
                                rate['method_code'] === selectedShippingMethod['method_code'];
                        });
                    }

                    if (!availableRate && quote.shippingMethod()) {
                        availableRate = _.find(ratesData, function (rate) {
                            return rate['carrier_code'] == quote.shippingMethod()['carrier_code'] && //eslint-disable-line
                                rate['method_code'] == quote.shippingMethod()['method_code']; //eslint-disable-line eqeqeq
                        });
                    }

                    if (!availableRate && selectedShippingRate) {
                        availableRate = _.find(ratesData, function (rate) {
                            return rate['carrier_code'] + '_' + rate['method_code'] === selectedShippingRate;
                        });
                    }

                    if (!availableRate && selectedShippingMethod) {
                        availableRate = _.find(ratesData, function (rate) {
                            return rate['carrier_code'] + '_' + rate['method_code'] === selectedShippingMethod;
                        });
                    }

                    if (!availableRate && _getDefaultShippingMethod()) {
                        availableRate = _.find(ratesData, function (rate) {
                            return rate['carrier_code'] + '_' + rate['method_code'] === _getDefaultShippingMethod();
                        });
                    }

                    var shippingAddressData = window.checkoutConfig['shippingAddressFromData'];

                    if (availableRate && availableRate['carrier_code'] === 'amstorepickup') {
                        checkoutData.setShippingAddressFromData(shippingAddressData);
                        target.resolveShippingAddress();
                    }

                    var onlyCountry = Object.keys(shippingAddressData).length === 1 && shippingAddressData['country_id'];
                    var emptyZip = Object.keys(shippingAddressData).length === 2 && shippingAddressData['postcode'] === '-';
                    var isShippingDataRefreshed = onlyCountry || emptyZip;
                    if (isShippingDataRefreshed) {
                        checkoutData.setShippingAddressFromData(shippingAddressData);
                        target.resolveShippingAddress();

                        checkoutData.setBillingAddressFromData(null);
                        target.resolveBillingAddress();
                    }

                    if (availableRate) {
                        if (!(availableRate['carrier_code'] === 'amstorepickup' && !storePickupService.onlyPickup())) {
                            selectShippingMethodAction(availableRate);
                        }
                    } else {
                        selectShippingMethodAction(null);
                    }
                },

                /**
                 * Resolve payment method. Used local storage
                 * @param {Function} original
                 */
                resolvePaymentMethod: function (original) {
                    original();
                    if (quote.paymentMethod()) {
                        return;
                    }
                    var paymentMethod = checkoutData.getSelectedPaymentMethod();
                    if (vaultResolver.isSavedVaultPayment(paymentMethod) && vaultResolver.resolve(paymentMethod)) {
                        return;
                    }
                    var provider = registry.get('checkoutProvider');
                    if (provider && provider.defaultPaymentMethod) {
                        var availablePaymentMethods = paymentService.getAvailablePaymentMethods();
                        availablePaymentMethods.some(function (payment) {
                            if (payment.method === provider.defaultPaymentMethod) {
                                selectPaymentMethodAction(payment);
                                return true;
                            }
                        });
                    }
                },

                /**
                 * Resolve estimation address. Used local storage
                 * @param {Function} original
                 */
                resolveEstimationAddress: function (original) {
                    original();
                    var shippingAddressData = checkoutData.getShippingAddressFromData(),
                        checkoutProvider = registry.get('checkoutProvider');

                    if (shippingAddressData) {
                        checkoutProvider.set(
                            'shippingAddress',
                            $.extend(true, {}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                        );
                    }
                },

                /**
                 * Apply resolved estimated address to quote
                 *
                 * @param {Function} original
                 * @param {Object} isEstimatedAddress
                 */
                applyShippingAddress: function (original, isEstimatedAddress) {
                    var addressData = addressList()[0];

                    original();

                    if (quote.shippingAddress()) {
                        return;
                    }

                    if (isEstimatedAddress) {
                        addressData = addressConverter.addressToEstimationAddress(addressData);
                    }

                    if (addressList().length > 1) {
                        selectShippingAddress(addressData);
                    }
                }
            };

            wrapper._extend(target, mixin);
            return target;
        };
    }
);
