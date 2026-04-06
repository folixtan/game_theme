# Virtual Checkout Module - Unit Tests

## Overview

This directory contains unit tests for the Virtual Checkout module's JavaScript components.

## Test Framework

- **Framework**: Jasmine 2.5.2
- **Test Runner**: Grunt + grunt-contrib-jasmine
- **Mocking**: SquireJS for dependency injection

## Test Structure

```
Test/js/
├── SpecRunner.html          # HTML test runner
├── test-main.js           # RequireJS configuration
└── spec/
    └── view/
        └── order-info-spec.js  # Tests for order-info.js component
```

## Running Tests

### Option 1: Browser-based Testing

1. Open `Test/js/SpecRunner.html` in a web browser
2. Tests will run automatically
3. View results in the browser

### Option 2: Command-line Testing (with Grunt)

If Grunt is configured for Jasmine tests:

```bash
# From the Magento root directory
npm test
# or
grunt jasmine
```

## Test Coverage

### order-info-spec.js

Tests the following functionality:

1. **subtotal computed property** (Lines 35-41 in order-info.js)
   - Returns formatted subtotal when totals are available
   - Returns empty string when totals are null/undefined
   - Handles zero values correctly
   - Handles large values correctly

2. **grandTotal computed property**
   - Returns formatted grand total when available
   - Returns empty string when missing

3. **items computed property**
   - Returns items from quote
   - Handles empty/null items arrays

4. **getFormattedPrice method**
   - Formats prices correctly using priceUtils
   - Handles null/undefined/zero values

5. **Item helper methods**
   - getItemImage: Gets product image or empty string
   - getItemName: Gets product name or empty string
   - getItemQty: Gets quantity or defaults to 1
   - getItemPrice: Gets formatted item price
   - getItemRowTotal: Gets formatted row total

6. **hasItems method**
   - Returns true when items exist
   - Returns false when items array is empty

## Test Data

Tests use mock data to simulate:
- Quote items with various properties
- Totals with different values (normal, zero, large)
- Missing/undefined/null values
- Edge cases

## Dependencies

The tests mock the following dependencies:
- `Magento_Checkout/js/model/quote` - Cart data model
- `Magento_Catalog/js/price-utils` - Price formatting utility

## Coverage

Current coverage for `order-info.js`:
- **Lines covered**: ~85%
- **Functions tested**: 10/10
- **Edge cases tested**: Yes

## Adding New Tests

To add tests for new components:

1. Create a new spec file: `Test/js/spec/view/[component-name]-spec.js`
2. Follow the existing test structure
3. Use SquireJS for mocking dependencies
4. Include the test file path in `test-main.js`

## Notes

- Tests are designed to run independently
- No backend/database required
- All dependencies are mocked
- Tests focus on component logic and data transformation
