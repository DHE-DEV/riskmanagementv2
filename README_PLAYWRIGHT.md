# Playwright Testing Setup - Full HD Resolution

This project is configured to use Playwright for end-to-end testing with **Full HD resolution (1920x1080)**.

## Configuration

The Playwright configuration (`playwright.config.js`) is set up with:

- **Viewport**: 1920x1080 pixels (Full HD)
- **Browser Window**: --window-size=1920,1080
- **Base URL**: http://localhost:8000
- **Auto Server**: Automatically starts Laravel dev server
- **Multiple Browsers**: Chrome, Firefox, Safari support

## Available Test Commands

```bash
# Run all tests
npm run test

# Run tests with visible browser (Full HD)
npm run test:headed

# Run tests with interactive UI
npm run test:ui

# Run specific timezone tests
npm run test:timezone

# Run tests in Full HD with Chrome only
npm run test:fullhd

# Debug tests step by step
npm run test:debug

# Show test report
npm run report
```

## Test Files

### `/tests/timezone.spec.js`
Tests for timezone functionality including:
- Mexico City timezone display (UTC-6)
- Berlin timezone display (UTC+2)  
- Live time updates every second
- Full HD resolution compatibility

## Running Tests

### Quick Start
```bash
# Run timezone tests in Full HD with visible browser
npm run test:timezone -- --headed

# Run all tests in Full HD
npm run test:fullhd
```

### Manual Test Execution
```bash
# Run specific test file
npx playwright test tests/timezone.spec.js --headed

# Run with specific browser
npx playwright test --project=chromium --headed

# Run with debugging
npx playwright test --debug tests/timezone.spec.js
```

## Resolution Verification

The tests automatically verify Full HD resolution by:
1. Setting viewport to 1920x1080
2. Launching browser with --window-size=1920,1080
3. Testing UI elements at full resolution
4. Capturing screenshots on failure

## Adding New Tests

Create new test files in the `/tests` directory following this pattern:

```javascript
import { test, expect } from '@playwright/test';

test.describe('Your Test Suite', () => {
  test('should work in Full HD resolution', async ({ page }) => {
    await page.goto('/dashboard');
    // Your test logic here
    
    // Verify viewport size
    const viewportSize = page.viewportSize();
    expect(viewportSize.width).toBe(1920);
    expect(viewportSize.height).toBe(1080);
  });
});
```

## CI/CD Integration

The configuration supports CI environments with:
- Automatic retries on failure
- HTML report generation
- Video recording on failure
- Screenshot capture on failure

## Troubleshooting

### Common Issues
1. **Server not starting**: Ensure Laravel dev server is not already running
2. **Tests timing out**: Increase timeout in test files if needed
3. **Resolution issues**: Verify browser supports Full HD resolution

### Debug Commands
```bash
# Check Playwright installation
npx playwright --version

# Install browsers if missing
npx playwright install

# Run with verbose logging
DEBUG=pw:api npm run test:headed
```