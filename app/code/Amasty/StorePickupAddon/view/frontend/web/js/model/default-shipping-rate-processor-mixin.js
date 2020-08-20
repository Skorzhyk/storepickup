define(
    [
        'mage/utils/wrapper',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'Magento_Checkout/js/model/shipping-service',
        'Amasty_Checkout/js/action/get-address-cache-key',
        'Magento_Checkout/js/model/resource-url-manager',
        'Magento_Checkout/js/model/quote',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor'
    ],
    function (
        wrapper,
        rateRegistry,
        shippingService,
        getAddressCacheKey,
        resourceUrlManager,
        quote,
        storage,
        errorProcessor
    ) {
        'use strict';

        function getRates(address) {
            var cache, serviceUrl, payload;

            // var shippingItems = getShippingItems(quote);

            shippingService.isLoading(true);
            cache = rateRegistry.get(address.getCacheKey());
            serviceUrl = resourceUrlManager.getUrlForEstimationShippingMethodsForNewAddress(quote);
            payload = JSON.stringify({
                    address: {
                        'street': address.street,
                        'city': address.city,
                        'region_id': address.regionId,
                        'region': address.region,
                        'country_id': address.countryId,
                        'postcode': address.postcode,
                        'email': address.email,
                        'customer_id': address.customerId,
                        'firstname': address.firstname,
                        'lastname': address.lastname,
                        'middlename': address.middlename,
                        'prefix': address.prefix,
                        'suffix': address.suffix,
                        'vat_id': address.vatId,
                        'company': address.company,
                        'telephone': address.telephone,
                        'fax': address.fax,
                        'custom_attributes': address.customAttributes,
                        'save_in_address_book': address.saveInAddressBook
                    }
                }
            );

            if (cache) {
                shippingService.setShippingRates(cache);
                shippingService.isLoading(false);
            } else {
                storage.post(
                    serviceUrl, payload, false
                ).done(function (result) {
                    rateRegistry.set(address.getCacheKey(), result);
                    shippingService.setShippingRates(result);
                }).fail(function (response) {
                    shippingService.setShippingRates([]);
                    errorProcessor.process(response);
                }).always(function () {
                    shippingService.isLoading(false);
                });
            }
        }

        // function getShippingItems(quote) {
        //     var shippingItems = [];
        //
        //     quote.getItems().forEach(function (item) {
        //         var options = item['options'];
        //         if (options.length > 0) {
        //             options.forEach(function (option) {
        //                 if (option[])
        //             });
        //         }
        //     });
        // }

        /**
         * Modify shippingRegistry guest cache.
         * Reduce quantity of requests to server.
         * @since 3.0.0
         * @since 3.0.5 fixed
         */
        return function (target) {
            target.getRates = wrapper.wrapSuper(target.getRates, function (address) {
                var cacheKey, cache;

                if (address.getType() !== 'new-address' && address.getType() !== 'new-customer-address') {
                    return getRates(address);
                }

                cacheKey = getAddressCacheKey(address);
                cache = rateRegistry.get(cacheKey);
                if (cache) {
                    rateRegistry.set(address.getCacheKey(), cache);
                } else if (!rateRegistry.get(address.getCacheKey())) {
                    shippingService.getShippingRates().subscribe(function (rates) {
                        rateRegistry.set(cacheKey, rates);
                        this.dispose();
                    });
                }

                return getRates(address);
            });

            return target;
        };
    }
);
