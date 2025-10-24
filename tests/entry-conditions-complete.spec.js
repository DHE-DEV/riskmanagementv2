import { test, expect } from '@playwright/test';

test('Entry conditions map highlighting - Complete flow', async ({ page }) => {
    await page.setViewportSize({ width: 1920, height: 1080 });

    // Capture console logs
    const logs = [];
    page.on('console', msg => {
        const text = msg.text();
        logs.push(text);
        console.log(`[BROWSER]: ${text}`);
    });

    // Capture network requests
    const apiRequests = [];
    page.on('request', request => {
        if (request.url().includes('/api/')) {
            apiRequests.push({
                url: request.url(),
                method: request.method()
            });
            console.log(`[API REQUEST]: ${request.method()} ${request.url()}`);
        }
    });

    page.on('response', response => {
        if (response.url().includes('/api/')) {
            console.log(`[API RESPONSE]: ${response.status()} ${response.url()}`);
        }
    });

    console.log('\n=== Loading page ===');
    await page.goto('http://127.0.0.1:8000/entry-conditions');
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('#entry-conditions-map');

    console.log('Page loaded, waiting for map initialization...');
    await page.waitForTimeout(2000);

    // Verify map is initialized
    const mapInitialized = await page.evaluate(() => {
        return {
            hasMap: !!window.entryConditionsMap,
            hasLayerGroup: !!window.countryLayersGroup,
            hasSelectedDestinations: !!window.selectedDestinations
        };
    });

    console.log('Map status:', mapInitialized);
    expect(mapInitialized.hasMap).toBe(true);
    expect(mapInitialized.hasLayerGroup).toBe(true);

    await page.screenshot({ path: 'screenshots/00-initial-page.png', fullPage: false });

    console.log('\n=== Step 1: Select destination "Spanien" ===');

    // Click on destinations input
    await page.click('#destinationsFilterInput');
    await page.screenshot({ path: 'screenshots/01-destinations-input-focused.png', fullPage: false });

    // Set up listener for API response BEFORE typing (debounce is 300ms)
    const searchPromise = page.waitForResponse(
        response => response.url().includes('/api/countries/search') && response.status() === 200,
        { timeout: 15000 }
    );

    // Type "Spanien" - this will trigger debounced search after 300ms
    await page.fill('#destinationsFilterInput', 'Spanien');
    console.log('Typed "Spanien", waiting for API response (debounce + network)...');

    try {
        const searchResponse = await searchPromise;
        const searchData = await searchResponse.json();
        console.log('API Response received:', JSON.stringify(searchData, null, 2));
    } catch (e) {
        console.log('API response timeout - may need longer wait');
    }

    // Wait for debounce (300ms) + API response + rendering
    await page.waitForTimeout(2000);

    await page.screenshot({ path: 'screenshots/02-destinations-autocomplete.png', fullPage: false });

    // Check for autocomplete results
    const resultsCount = await page.locator('#destinationsFilterResults .autocomplete-item').count();
    console.log(`Found ${resultsCount} autocomplete results`);

    expect(resultsCount).toBeGreaterThan(0);

    // Click on first result
    console.log('Clicking on first result...');
    await page.locator('#destinationsFilterResults .autocomplete-item').first().click();
    await page.waitForTimeout(500);

    await page.screenshot({ path: 'screenshots/03-destination-selected.png', fullPage: false });

    // Verify destination was added
    const selectedDestinations = await page.evaluate(() => {
        return Array.from(window.selectedDestinations.entries()).map(([code, data]) => ({
            code: code,
            name: data.name
        }));
    });
    console.log('Selected destinations:', selectedDestinations);
    expect(selectedDestinations.length).toBeGreaterThan(0);

    console.log('\n=== Step 2: Click search button ===');

    // Wait for API responses when clicking search
    const contentPromise = page.waitForResponse(
        response => response.url().includes('/api/entry-conditions/content'),
        { timeout: 10000 }
    );

    const locatePromises = [];
    selectedDestinations.forEach(dest => {
        locatePromises.push(
            page.waitForResponse(
                response => response.url().includes(`/api/countries/locate?q=${dest.code}`),
                { timeout: 10000 }
            )
        );
    });

    await page.click('button:has-text("Suchen")');
    console.log('Search button clicked, waiting for API responses...');

    // Wait for content API
    try {
        const contentResponse = await contentPromise;
        console.log('Content API response:', contentResponse.status());
    } catch (e) {
        console.log('Content API response not received (expected for this flow)');
    }

    // Wait for locate API responses
    try {
        await Promise.all(locatePromises);
        console.log('All locate API responses received');
    } catch (e) {
        console.log('Some locate API responses may have failed:', e.message);
    }

    // Wait for map operations to complete
    await page.waitForTimeout(3000);

    await page.screenshot({ path: 'screenshots/04-after-search.png', fullPage: false });

    console.log('\n=== Step 3: Verify map highlighting ===');

    // Check map status
    const mapStatus = await page.evaluate(() => {
        const result = {
            hasMap: !!window.entryConditionsMap,
            hasLayerGroup: !!window.countryLayersGroup,
            layersCount: 0,
            zoom: 0,
            center: null,
            bounds: null
        };

        if (window.countryLayersGroup) {
            const layers = window.countryLayersGroup.getLayers();
            result.layersCount = layers.length;

            // Get details about each layer
            result.layerDetails = layers.map((layer, i) => ({
                index: i,
                type: layer.constructor.name,
                latLng: layer.getLatLng ? layer.getLatLng() : null,
                radius: layer.getRadius ? layer.getRadius() : null
            }));
        }

        if (window.entryConditionsMap) {
            result.zoom = window.entryConditionsMap.getZoom();
            result.center = window.entryConditionsMap.getCenter();
            result.bounds = window.entryConditionsMap.getBounds();
        }

        return result;
    });

    console.log('\n=== Map Status ===');
    console.log(JSON.stringify(mapStatus, null, 2));

    console.log('\n=== All Console Logs ===');
    logs.forEach(log => console.log(log));

    console.log('\n=== All API Requests ===');
    apiRequests.forEach(req => console.log(`${req.method} ${req.url}`));

    // Assertions
    expect(mapStatus.hasMap).toBe(true);
    expect(mapStatus.hasLayerGroup).toBe(true);
    expect(mapStatus.layersCount).toBeGreaterThan(0);
    expect(mapStatus.zoom).toBeGreaterThan(2);

    console.log('\n✅ Test PASSED!');
    console.log(`Map has ${mapStatus.layersCount} circle markers`);
    console.log(`Map zoom level: ${mapStatus.zoom}`);
    console.log(`Map center: ${JSON.stringify(mapStatus.center)}`);
});
