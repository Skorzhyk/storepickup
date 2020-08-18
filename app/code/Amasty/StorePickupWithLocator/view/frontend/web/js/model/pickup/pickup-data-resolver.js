define([
    'ko',
    'Magento_Customer/js/customer-data',
    'jquery/jquery-storageapi'
], function (ko, customerData) {
    'use strict';

    const selectedPickupInfoKey = 'amasty-selected-pickup-info',
        locationsDataKey = 'amasty-storepickup-data',
        storeKey = 'am_pickup_store',
        dateKey = 'am_pickup_date',
        timeKey = 'am_pickup_time';

    var pickupInfo = customerData.get(selectedPickupInfoKey)(),
        locationsData = customerData.get(locationsDataKey),
        indexedStores = null,
        storeId = ko.observable(pickupInfo[storeKey]),
        dateData = ko.observable(pickupInfo[dateKey]),
        timeData = ko.observable(pickupInfo[timeKey]),

        /**
         * Set section (customer data) to model
         *
         * @param {Object} data
         */
        setCustomerDataToModel = function (data) {
            if (storeId() !== data[storeKey]) {
                storeId(data[storeKey]);
            }

            if (dateData() !== data[dateKey]) {
                dateData(data[dateKey]);
            }

            if (timeData() !== data[timeKey]) {
                timeData(data[timeKey]);
            }
        },

        /**
         * Set model data to section (customer data)
         */
        setModelToCustomerData = function () {
            var data = getPickupData();

            data[storeKey] = storeId();
            data[dateKey] = dateData();
            data[timeKey] = timeData();

            customerData.set(selectedPickupInfoKey, data);
        },

        /**
         * Get selected store data form section (customer data)
         *
         * @returns {*}
         */
        getPickupData = function () {
            return customerData.get(selectedPickupInfoKey)();
        },

        getStoresData = function () {
            return locationsData().stores;
        },

        updateIndexedStores = function () {
            var stores = getStoresData();

            indexedStores = {};
            _.each(stores, function (storeData) {
                indexedStores[storeData.id] = storeData;
            });
        };

    customerData.get(selectedPickupInfoKey).subscribe(setCustomerDataToModel);
    locationsData.subscribe(updateIndexedStores);

    storeId.subscribe(setModelToCustomerData);
    dateData.subscribe(setModelToCustomerData);
    timeData.subscribe(setModelToCustomerData);

    return {
        /**
         * Get section data (customer data) item by key
         *
         * @param {String} key
         * @returns {string}
         */
        getDataByKey: function (key) {
            var data = getPickupData()[key];

            return data || '';
        },

        /**
         * @returns {object} - current location object details
         */
        getCurrentStoreData: function () {
            return this.getStoreById(this.storeId());
        },

        /**
         * @returns {Array} - array of object stores
         */
        getStores: function () {
            return locationsData().stores;
        },

        /**
         *
         * @param {int} id
         * @returns {object} - location object details
         */
        getStoreById: function (id) {
            id = +id;

            if (!indexedStores || !indexedStores[id]) {
                this._updateIndexedStores();
            }

            return indexedStores[id];
        },

        /**
         * Remove location from locale storage
         *
         * @param {int} id
         */
        removeStore: function (id) {
            var locationsData = this.pickupData();

            locationsData.stores = locationsData.stores.filter(function (element) {
                return element.id !== id;
            });

            this.pickupData(locationsData);
            this.storeId(null);
        },

        _updateIndexedStores: function () {
            updateIndexedStores();
        },

        /**
         * Selected Pickup Store ID
         */
        storeId: storeId,

        /**
         * Selected Date to pickup
         */
        dateData: dateData,

        /**
         * Selected Time to pickup
         */
        timeData: timeData,

        /**
         * Store pickup data Section Observer
         */
        pickupData: locationsData
    };
});
