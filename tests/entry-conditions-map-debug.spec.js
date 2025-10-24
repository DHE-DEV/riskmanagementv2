import { test, expect } from '@playwright/test';

test('Debug map highlighting for Turkey', async ({ page }) => {
    await page.setViewportSize({ width: 1920, height: 1080 });

    const logs = [];
    page.on('console', msg => {
        const text = msg.text();
        logs.push(text);
        console.log(`[BROWSER]: ${text}`);
    });

    await page.goto('http://127.0.0.1:8000/entry-conditions');
    await page.waitForLoadState('networkidle');

    // Warte bis Map geladen ist
    await page.waitForSelector('#entry-conditions-map');
    console.log('Map element loaded');

    // Warte 3 Sekunden für GeoJSON-Laden
    await page.waitForTimeout(3000);

    // Prüfe ob GeoJSON geladen wurde
    const geoJsonLoaded = await page.evaluate(() => {
        return window.countriesGeoJSON !== null && window.countriesGeoJSON !== undefined;
    });
    console.log('GeoJSON loaded:', geoJsonLoaded);

    if (geoJsonLoaded) {
        const geoJsonInfo = await page.evaluate(() => {
            return {
                featuresCount: window.countriesGeoJSON?.features?.length || 0,
                firstFeatureProps: window.countriesGeoJSON?.features?.[0]?.properties || null
            };
        });
        console.log('GeoJSON info:', JSON.stringify(geoJsonInfo, null, 2));
    }

    // Türkei als Reiseziel hinzufügen direkt über JavaScript
    await page.evaluate(() => {
        // Türkei direkt hinzufügen
        window.selectedDestinations.set('TR', { code: 'TR', name: 'Türkei' });
        window.renderSelectedDestinations();
    });

    console.log('Turkey added as destination');
    await page.waitForTimeout(500);

    // Screenshot vor Suche
    await page.screenshot({ path: 'screenshots/map-before-search.png', fullPage: false });

    // Suche starten
    console.log('Clicking search button...');
    await page.click('button:has-text("Suchen")');

    // Warte auf Verarbeitung
    await page.waitForTimeout(3000);

    // Screenshot nach Suche
    await page.screenshot({ path: 'screenshots/map-after-search.png', fullPage: false });

    // Prüfe Map-Status
    const mapStatus = await page.evaluate(() => {
        return {
            layersCount: window.countryLayersGroup?.getLayers()?.length || 0,
            zoom: window.entryConditionsMap?.getZoom() || 0,
            center: window.entryConditionsMap?.getCenter() || null
        };
    });

    console.log('\n=== Map Status ===');
    console.log('Layers count:', mapStatus.layersCount);
    console.log('Zoom level:', mapStatus.zoom);
    console.log('Center:', mapStatus.center);

    // Alle Console-Logs ausgeben
    console.log('\n=== All Console Logs ===');
    logs.forEach(log => console.log(log));

    // Assertions
    expect(mapStatus.layersCount).toBeGreaterThan(0);
    expect(mapStatus.zoom).toBeGreaterThan(2);
});
