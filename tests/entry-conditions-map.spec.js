import { test, expect } from '@playwright/test';

test.describe('Entry Conditions Map Highlighting', () => {
    test.beforeEach(async ({ page }) => {
        await page.setViewportSize({ width: 1920, height: 1080 });
        await page.goto('http://127.0.0.1:8000/entry-conditions');
        await page.waitForLoadState('networkidle');
    });

    test('should highlight selected destination on map and zoom', async ({ page }) => {
        // Warte bis die Map geladen ist
        await page.waitForSelector('#entry-conditions-map', { timeout: 10000 });

        // Warte bis GeoJSON geladen ist
        await page.waitForTimeout(2000);

        // Console-Logs erfassen
        const logs = [];
        page.on('console', msg => {
            logs.push(`${msg.type()}: ${msg.text()}`);
            console.log(`Browser Console [${msg.type()}]:`, msg.text());
        });

        // Reiseziel auswählen - Türkei (TR)
        await page.click('#destinationsFilterInput');
        await page.fill('#destinationsFilterInput', 'türkei');
        await page.waitForTimeout(500);

        // Warte auf Suchergebnisse
        await page.waitForSelector('#destinationsFilterResults .autocomplete-item', { timeout: 5000 });

        // Ersten Eintrag übernehmen
        await page.click('#destinationsFilterResults .autocomplete-item button');
        await page.waitForTimeout(500);

        // Screenshot vor der Suche
        await page.screenshot({ path: 'screenshots/entry-conditions-before-search.png' });

        // Prüfe ob Türkei ausgewählt wurde
        const selectedDestination = await page.textContent('#selectedDestinationsDisplay');
        console.log('Selected destination:', selectedDestination);
        expect(selectedDestination).toContain('Türkei');

        // Auf Suchen klicken
        await page.click('button:has-text("Suchen")');
        await page.waitForTimeout(3000);

        // Screenshot nach der Suche
        await page.screenshot({ path: 'screenshots/entry-conditions-after-search.png' });

        // Prüfe Console-Logs
        console.log('\n=== All Console Logs ===');
        logs.forEach(log => console.log(log));

        // Prüfe ob countryLayersGroup Layer hat
        const hasLayers = await page.evaluate(() => {
            if (window.countryLayersGroup) {
                const layers = window.countryLayersGroup.getLayers();
                console.log('Number of layers in countryLayersGroup:', layers.length);
                return layers.length > 0;
            }
            return false;
        });

        console.log('Has layers on map:', hasLayers);

        // Prüfe GeoJSON-Daten
        const geoJsonInfo = await page.evaluate(() => {
            if (window.countriesGeoJSON) {
                return {
                    loaded: true,
                    featuresCount: window.countriesGeoJSON.features ? window.countriesGeoJSON.features.length : 0,
                    sampleFeature: window.countriesGeoJSON.features ? window.countriesGeoJSON.features[0]?.properties : null
                };
            }
            return { loaded: false };
        });

        console.log('GeoJSON Info:', JSON.stringify(geoJsonInfo, null, 2));

        // Prüfe ob TR gefunden wird
        const trFeature = await page.evaluate(() => {
            if (window.countriesGeoJSON && window.countriesGeoJSON.features) {
                const feature = window.countriesGeoJSON.features.find(f => {
                    const code = f.properties?.iso_a2 || f.properties?.ISO_A2 ||
                                f.properties?.iso2 || f.properties?.code;
                    return code && code.toUpperCase() === 'TR';
                });
                return feature ? {
                    found: true,
                    properties: feature.properties,
                    geometryType: feature.geometry?.type
                } : { found: false };
            }
            return { found: false, error: 'No GeoJSON data' };
        });

        console.log('TR Feature:', JSON.stringify(trFeature, null, 2));

        // Assertion - Layer sollte vorhanden sein
        expect(hasLayers).toBe(true);

        // Prüfe ob die Map gezoomt hat (nicht mehr auf default zoom level 2)
        const zoomLevel = await page.evaluate(() => {
            return window.entryConditionsMap ? window.entryConditionsMap.getZoom() : null;
        });

        console.log('Map zoom level:', zoomLevel);
        expect(zoomLevel).toBeGreaterThan(2);
    });

    test('should highlight multiple destinations', async ({ page }) => {
        await page.waitForSelector('#entry-conditions-map');
        await page.waitForTimeout(2000);

        // Türkei auswählen
        await page.fill('#destinationsFilterInput', 'türkei');
        await page.waitForTimeout(500);
        await page.click('#destinationsFilterResults .autocomplete-item button');
        await page.waitForTimeout(500);

        // Spanien auswählen
        await page.fill('#destinationsFilterInput', 'spanien');
        await page.waitForTimeout(500);
        await page.click('#destinationsFilterResults .autocomplete-item button');
        await page.waitForTimeout(500);

        // Auf Suchen klicken
        await page.click('button:has-text("Suchen")');
        await page.waitForTimeout(3000);

        await page.screenshot({ path: 'screenshots/entry-conditions-multiple-countries.png' });

        // Prüfe Layer Count
        const layerCount = await page.evaluate(() => {
            if (window.countryLayersGroup) {
                return window.countryLayersGroup.getLayers().length;
            }
            return 0;
        });

        console.log('Number of layers for multiple countries:', layerCount);
        expect(layerCount).toBeGreaterThan(0);
    });
});
