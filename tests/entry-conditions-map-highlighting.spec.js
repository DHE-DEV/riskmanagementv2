import { test, expect } from '@playwright/test';

test('Map highlighting for selected destinations', async ({ page }) => {
    await page.setViewportSize({ width: 1920, height: 1080 });

    // Capture console logs
    const logs = [];
    page.on('console', msg => {
        const text = msg.text();
        logs.push(text);
        console.log(`[BROWSER]: ${text}`);
    });

    console.log('\n=== Loading entry conditions page ===');
    await page.goto('http://127.0.0.1:8000/entry-conditions');
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('#entry-conditions-map');

    console.log('Waiting for map initialization...');
    await page.waitForTimeout(3000);

    // Verify map is initialized
    const mapInitialized = await page.evaluate(() => {
        return {
            hasMap: !!window.entryConditionsMap,
            hasLayerGroup: !!window.countryLayersGroup,
            hasSelectedDestinations: typeof window.selectedDestinations !== 'undefined'
        };
    });

    console.log('Map initialization status:', mapInitialized);
    expect(mapInitialized.hasMap).toBe(true);
    expect(mapInitialized.hasLayerGroup).toBe(true);
    expect(mapInitialized.hasSelectedDestinations).toBe(true);

    await page.screenshot({ path: 'screenshots/map-test-01-initial.png', fullPage: false });

    console.log('\n=== Adding Spain as destination via JavaScript ===');

    // Directly add Spain to selectedDestinations via JavaScript
    await page.evaluate(() => {
        window.selectedDestinations.set('ES', { code: 'ES', name: 'Spanien' });
        window.renderSelectedDestinations();
    });

    await page.waitForTimeout(500);
    await page.screenshot({ path: 'screenshots/map-test-02-destination-added.png', fullPage: false });

    // Verify destination was added
    const selectedDests = await page.evaluate(() => {
        return Array.from(window.selectedDestinations.entries()).map(([code, data]) => ({
            code: code,
            name: data.name
        }));
    });
    console.log('Selected destinations:', selectedDests);
    expect(selectedDests.length).toBeGreaterThan(0);
    expect(selectedDests.some(d => d.code === 'ES')).toBe(true);

    console.log('\n=== Clicking search button ===');

    // Set up listener for the locate API call
    const locatePromise = page.waitForResponse(
        response => response.url().includes('/api/countries/locate?q=ES') ||
                   response.url().includes('/api/countries/locate?iso2=ES'),
        { timeout: 15000 }
    );

    // Click search button
    await page.click('button:has-text("Suchen")');
    console.log('Search button clicked');

    // Wait for the locate API call
    try {
        const locateResponse = await locatePromise;
        const locateData = await locateResponse.json();
        console.log('Locate API response:', JSON.stringify(locateData, null, 2));
    } catch (e) {
        console.log('Locate API call timeout or failed:', e.message);
    }

    // Wait for map operations to complete
    await page.waitForTimeout(5000);

    await page.screenshot({ path: 'screenshots/map-test-03-after-search.png', fullPage: false });

    console.log('\n=== Verifying map highlighting ===');

    // Check map status
    const mapStatus = await page.evaluate(() => {
        const result = {
            hasMap: !!window.entryConditionsMap,
            hasLayerGroup: !!window.countryLayersGroup,
            layersCount: 0,
            zoom: 0,
            center: null,
            bounds: null,
            layerDetails: []
        };

        if (window.countryLayersGroup) {
            const layers = window.countryLayersGroup.getLayers();
            result.layersCount = layers.length;

            // Get details about each layer
            result.layerDetails = layers.map((layer, i) => {
                const detail = {
                    index: i,
                    type: layer.constructor.name
                };

                if (layer.getLatLng) {
                    detail.latLng = layer.getLatLng();
                }

                if (layer.getRadius) {
                    detail.radius = layer.getRadius();
                }

                if (layer.options) {
                    detail.color = layer.options.color;
                    detail.fillColor = layer.options.fillColor;
                    detail.fillOpacity = layer.options.fillOpacity;
                }

                return detail;
            });
        }

        if (window.entryConditionsMap) {
            result.zoom = window.entryConditionsMap.getZoom();
            result.center = window.entryConditionsMap.getCenter();

            try {
                result.bounds = window.entryConditionsMap.getBounds();
            } catch (e) {
                result.bounds = null;
            }
        }

        return result;
    });

    console.log('\n=== Final Map Status ===');
    console.log(JSON.stringify(mapStatus, null, 2));

    console.log('\n=== All Console Logs ===');
    logs.forEach(log => console.log(log));

    // Assertions
    expect(mapStatus.hasMap).toBe(true);
    expect(mapStatus.hasLayerGroup).toBe(true);
    expect(mapStatus.layersCount).toBeGreaterThan(0);
    expect(mapStatus.zoom).toBeGreaterThan(2);

    if (mapStatus.layerDetails.length > 0) {
        console.log('\n✅ Test PASSED!');
        console.log(`Map has ${mapStatus.layersCount} circle marker(s)`);
        console.log(`Map zoom level: ${mapStatus.zoom}`);
        console.log(`Map center: lat=${mapStatus.center.lat}, lng=${mapStatus.center.lng}`);
        console.log('Layer details:', JSON.stringify(mapStatus.layerDetails, null, 2));
    } else {
        throw new Error('No layers were added to the map - map highlighting did not work!');
    }
});
