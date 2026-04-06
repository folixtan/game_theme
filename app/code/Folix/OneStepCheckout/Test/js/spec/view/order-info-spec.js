/**
 * Copyright © Folix. All rights reserved.
 * Unit tests for order-info.js component
 */

describe('OrderInfo Component', function () {
    'use strict';

    var component, quoteMock, priceUtilsMock, injector;
    var mockQuoteData, mockPriceFormat;

    beforeEach(function (done) {
        // Mock window.checkoutConfig
        window.checkoutConfig = {
            priceFormat: {
                pattern: '$%s',
                precision: 2,
                requiredPrecision: 2,
                decimalSymbol: '.',
                groupSymbol: ',',
                groupLength: 3,
                integerRequired: 1
            }
        };

        // Create Squire injector for dependency mocking
        injector = new Squire();

        // Mock quote object
        quoteMock = {
            getItems: jasmine.createSpy('getItems').and.returnValue([]),
            getTotals: jasmine.createSpy('getTotals').and.returnValue(ko.observable())
        };

        // Mock priceUtils
        priceUtilsMock = {
            formatPrice: jasmine.createSpy('formatPrice').and.callFake(function (price, format) {
                if (!price && price !== 0) {
                    return '';
                }
                return format.pattern.replace('%s', price.toFixed(format.precision));
            })
        };

        // Load the component with mocked dependencies
        injector.mock('Magento_Checkout/js/model/quote', quoteMock)
               .mock('Magento_Catalog/js/price-utils', priceUtilsMock)
               .require(['Folix_VirtualCheckout/js/view/order-info'], function (OrderInfo) {
                   // Create component instance
                   component = new OrderInfo({
                   });
                   done();
               });
    });

    afterEach(function () {
        injector.remove();
        component = null;
    });

    describe('subtotal computed property', function () {
        it('should return formatted subtotal when totals are available', function () {
            // Arrange
            mockQuoteData = {
                subtotal: 100.50,
                grand_total: 110.55
            };
            quoteMock.getTotals.and.returnValue(ko.observable(mockQuoteData));

            // Re-initialize to pick up the mocked quote
            injector.clean();
            injector.mock('Magento_Checkout/js/model/quote', quoteMock)
                   .mock('Magento_Catalog/js/price-utils', priceUtilsMock)
                   .require(['Folix_VirtualCheckout/js/view/order-info'], function (OrderInfo) {
                       component = new OrderInfo({});
                       component.initObservable();
                   });

            // Act
            var result = component.subtotal();

            // Assert
            expect(result).toBe('$100.50');
            expect(priceUtilsMock.formatPrice).toHaveBeenCalledWith(100.50, window.checkoutConfig.priceFormat);
        });

        it('should return empty string when totals are null', function () {
            // Arrange
            quoteMock.getTotals.and.returnValue(ko.observable(null));

            // Re-initialize to pick up the mocked quote
            injector.clean();
            injector.mock('Magento_Checkout/js/model/quote', quoteMock)
                   .mock('Magento_Catalog/js/price-utils', priceUtilsMock)
                   .require(['Folix_VirtualCheckout/js/view/order-info'], function (OrderInfo) {
                       component = new OrderInfo({});
                       component.initObservable();
                   });

            // Act
            var result = component.subtotal();

            // Assert
            expect(result).toBe('');
            expect(priceUtilsMock.formatPrice).not.toHaveBeenCalled();
        });

        it('should return empty string when totals are undefined', function () {
            // Arrange
            quoteMock.getTotals.and.returnValue(ko.observable(undefined));

            // Re-initialize to pick up the mocked quote
            injector.clean();
            injector.mock('Magento_Checkout/js/model/quote', quoteMock)
                   .mock('Magento_Catalog/js/price-utils', priceUtilsMock)
                   .require(['Folix_VirtualCheckout/js/view/order-info'], function (OrderInfo) {
                       component = new OrderInfo({});
                       component.initObservable();
                   });

            // Act
            var result = component.subtotal();

            // Assert
            expect(result).toBe('');
            expect(priceUtilsMock.formatPrice).not.toHaveBeenCalled();
        });

        it('should handle zero subtotal correctly', function () {
            // Arrange
            mockQuoteData = {
                subtotal: 0,
                grand_total: 0
            };
            quoteMock.getTotals.and.returnValue(ko.observable(mockQuoteData));

            // Re-initialize to pick up the mocked quote
            injector.clean();
            injector.mock('Magento_Checkout/js/model/quote', quoteMock)
                   .mock('Magento_Catalog/js/price-utils', priceUtilsMock)
                   .require(['Folix_VirtualCheckout/js/view/order-info'], function (OrderInfo) {
                       component = new OrderInfo({});
                       component.initObservable();
                   });

            // Act
            var result = component.subtotal();

            // Assert
            expect(result).toBe('$0.00');
            expect(priceUtilsMock.formatPrice).toHaveBeenCalledWith(0, window.checkoutConfig.priceFormat);
        });

        it('should handle large subtotal values correctly', function () {
            // Arrange
            mockQuoteData = {
                subtotal: 999999.99,
                grand_total: 1050000.00
            };
            quoteMock.getTotals.and.returnValue(ko.observable(mockQuoteData));

            // Re-initialize to pick up the mocked quote
            injector.clean();
            injector.mock('Magento_Checkout/js/model/quote', quoteMock)
                   .mock('Magento_Catalog/js/price-utils', priceUtilsMock)
                   .require(['Folix_VirtualCheckout/js/view/order-info'], function (OrderInfo) {
                       component = new OrderInfo({});
                       component.initObservable();
                   });

            // Act
            var result = component.subtotal();

            // Assert
            expect(result).toBe('$999999.99');
            expect(priceUtilsMock.formatPrice).toHaveBeenCalledWith(999999.99, window.checkoutConfig.priceFormat);
        });
    });

    describe('grandTotal computed property', function () {
        it('should return formatted grand total when totals are available', function () {
            // Arrange
            mockQuoteData = {
                subtotal: 100.00,
                grand_total: 110.55
            };
            quoteMock.getTotals.and.returnValue(ko.observable(mockQuoteData));

            // Re-initialize to pick up the mocked quote
            injector.clean();
            injector.mock('Magento_Checkout/js/model/quote', quoteMock)
                   .mock('Magento_Catalog/js/price-utils', priceUtilsMock)
                   .require(['Folix_VirtualCheckout/js/view/order-info'], function (OrderInfo) {
                       component = new OrderInfo({});
                       component.initObservable();
                   });

            // Act
            var result = component.grandTotal();

            // Assert
            expect(result).toBe('$110.55');
            expect(priceUtilsMock.formatPrice).toHaveBeenCalledWith(110.55, window.checkoutConfig.priceFormat);
        });

        it('should return empty string when grand total is missing', function () {
            // Arrange
            mockQuoteData = {
                subtotal: 100.00
            };
            quoteMock.getTotals.and.returnValue(ko.observable(mockQuoteData));

            // Re-initialize to pick up the mocked quote
            injector.clean();
            injector.mock('Magento_Checkout/js/model/quote', quoteMock)
                   .mock('Magento_Catalog/js/price-utils', priceUtilsMock)
                   .require(['Folix_VirtualCheckout/js/view/order-info'], function (OrderInfo) {
                       component = new OrderInfo({});
                       component.initObservable();
                   });

            // Act
            var result = component.grandTotal();

            // Assert
            expect(result).toBe('');
        });
    });

    describe('items computed property', function () {
        it('should return items from quote', function () {
            // Arrange
            var mockItems = [
                { name: 'Product 1', price: 10.00 },
                { name: 'Product 2', price: 20.00 }
            ];
            quoteMock.getItems.and.returnValue(mockItems);

            // Re-initialize to pick up the mocked quote
            injector.clean();
            injector.mock('Magento_Checkout/js/model/quote', quoteMock)
                   .mock('Magento_Catalog/js/price-utils', priceUtilsMock)
                   .require(['Folix_VirtualCheckout/js/view/order-info'], function (OrderInfo) {
                       component = new OrderInfo({});
                       component.initObservable();
                   });

            // Act
            var result = component.items();

            // Assert
            expect(result).toEqual(mockItems);
        });

        it('should return empty array when quote has no items', function () {
            // Arrange
            quoteMock.getItems.and.returnValue([]);

            // Re-initialize to pick up the mocked quote
            injector.clean();
            injector.mock('Magento_Checkout/js/model/quote', quoteMock)
                   .mock('Magento_Catalog/js/price-utils', priceUtilsMock)
                   .require(['Folix_VirtualCheckout/js/view/order-info'], function (OrderInfo) {
                       component = new OrderInfo({});
                       component.initObservable();
                   });

            // Act
            var result = component.items();

            // Assert
            expect(result).toEqual([]);
        });

        it('should handle null items correctly', function () {
            // Arrange
            quoteMock.getItems.and.returnValue(null);

            // Re-initialize to pick up the mocked quote
            injector.clean();
            injector.mock('Magento_Checkout/js/model/quote', quoteMock)
                   .mock('Magento_Catalog/js/price-utils', priceUtilsMock)
                   .require(['Folix_VirtualCheckout/js/view/order-info'], function (OrderInfo) {
                       component = new OrderInfo({});
                       component.initObservable();
                   });

            // Act
            var result = component.items();

            // Assert
            expect(result).toEqual([]);
        });
    });

    describe('getFormattedPrice method', function () {
        it('should format price correctly', function () {
            // Arrange
            var price = 99.99;

            // Act
            var result = component.getFormattedPrice(price);

            // Assert
            expect(result).toBe('$99.99');
            expect(priceUtilsMock.formatPrice).toHaveBeenCalledWith(price, window.checkoutConfig.priceFormat);
        });

        it('should return empty string for null price', function () {
            // Arrange
            var price = null;

            // Act
            var result = component.getFormattedPrice(price);

            // Assert
            expect(result).toBe('');
            expect(priceUtilsMock.formatPrice).not.toHaveBeenCalled();
        });

        it('should return empty string for undefined price', function () {
            // Arrange
            var price = undefined;

            // Act
            var result = component.getFormattedPrice(price);

            // Assert
            expect(result).toBe('');
            expect(priceUtilsMock.formatPrice).not.toHaveBeenCalled();
        });

        it('should format zero price correctly', function () {
            // Arrange
            var price = 0;

            // Act
            var result = component.getFormattedPrice(price);

            // Assert
            expect(result).toBe('$0.00');
            expect(priceUtilsMock.formatPrice).toHaveBeenCalledWith(0, window.checkoutConfig.priceFormat);
        });
    });

    describe('Item helper methods', function () {
        it('should get item image correctly', function () {
            // Arrange
            var mockItem = {
                product_image: 'http://example.com/image.jpg',
                name: 'Test Product'
            };

            // Act
            var result = component.getItemImage(mockItem);

            // Assert
            expect(result).toBe('http://example.com/image.jpg');
        });

        it('should return empty string for missing item image', function () {
            // Arrange
            var mockItem = {
                name: 'Test Product'
            };

            // Act
            var result = component.getItemImage(mockItem);

            // Assert
            expect(result).toBe('');
        });

        it('should get item name correctly', function () {
            // Arrange
            var mockItem = {
                name: 'Test Product',
                price: 10.00
            };

            // Act
            var result = component.getItemName(mockItem);

            // Assert
            expect(result).toBe('Test Product');
        });

        it('should return empty string for missing item name', function () {
            // Arrange
            var mockItem = {
                price: 10.00
            };

            // Act
            var result = component.getItemName(mockItem);

            // Assert
            expect(result).toBe('');
        });

        it('should get item quantity correctly', function () {
            // Arrange
            var mockItem = {
                qty: 5,
                price: 10.00
            };

            // Act
            var result = component.getItemQty(mockItem);

            // Assert
            expect(result).toBe(5);
        });

        it('should return 1 for missing item quantity', function () {
            // Arrange
            var mockItem = {
                price: 10.00
            };

            // Act
            var result = component.getItemQty(mockItem);

            // Assert
            expect(result).toBe(1);
        });

        it('should get item price correctly', function () {
            // Arrange
            var mockItem = {
                price: 19.99
            };

            // Act
            var result = component.getItemPrice(mockItem);

            // Assert
            expect(result).toBe('$19.99');
        });

        it('should get item row total correctly', function () {
            // Arrange
            var mockItem = {
                row_total: 99.95
            };

            // Act
            var result = component.getItemRowTotal(mockItem);

            // Assert
            expect(result).toBe('$99.95');
        });
    });

    describe('hasItems method', function () {
        it('should return true when items exist', function () {
            // Arrange
            component.items = ko.observable([
                { name: 'Product 1' },
                { name: 'Product 2' }
            ]);

            // Act
            var result = component.hasItems();

            // Assert
            expect(result).toBe(true);
        });

        it('should return false when no items exist', function () {
            // Arrange
            component.items = ko.observable([]);

            // Act
            var result = component.hasItems();

            // Assert
            expect(result).toBe(false);
        });
    });
});
