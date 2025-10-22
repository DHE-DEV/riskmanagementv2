import { test, expect } from '@playwright/test';

test('Map highlighting works end-to-end', async ({ page }) => {
    await page.setViewportSize({ width: 1920, height: 1080 });

    const logs = [];
    page.on('console', msg => {
        const text = msg.text();
        logs.push(text);
        console.log(`[BROWSER]: ${text}`);
    });

    await page.goto('http://127.0.0.1:8000/entry-conditions');
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('#entry-conditions-map');

    console.log('\n=== Starting test ===');

    // Warte bis alles geladen ist
    await page.waitForTimeout(2000);

    // Spanien als Reiseziel eingeben
    console.log('Entering "Spanien"...');
    await page.click('#destinationsFilterInput');
    await page.fill('#destinationsFilterInput', 'Spanien');

    // Warte länger für Debounce + API
    await page.waitForTimeout(2000);

    await page.screenshot({ path: 'screenshots/step1-autocomplete.png', fullPage: false });

    // Zähle Ergebnisse
    const resultsCount = await page.locator('#destinationsFilterResults .autocomplete-item').count();
    console.log('Autocomplete results:', resultsCount);

    if (resultsCount > 0) {
        // Klicke auf ersten Eintrag
        await page.locator('#destinationsFilterResults .autocomplete-item').first().click();
        await page.waitForTimeout(500);

        console.log('Spain selected, clicking search...');
        await page.screenshot({ path: 'screenshots/step2-before-search.png', fullPage: false });

        // Klicke auf Suchen
        await page.click('button:has-text("Suchen")');

        // Warte auf alle Async-Operationen
        await page.waitForTimeout(5000);

        await page.screenshot({ path: 'screenshots/step3-after-search.png', fullPage: false });

        // Prüfe Map-Status
        const mapStatus = await page.evaluate(() => {
            const result = {
                hasMap: !!window.entryConditionsMap,
                hasLayerGroup: !!window.countryLayersGroup,
                layersCount: 0,
                zoom: 0,
                center: null
            };

            if (window.countryLayersGroup) {
                result.layersCount = window.countryLayersGroup.getLayers().length;
            }

            if (window.entryConditionsMap) {
                result.zoom = window.entryConditionsMap.getZoom();
                result.center = window.entryConditionsMap.getCenter();
            }

            return result;
        });

        console.log('\n=== Map Status ===');
        console.log(JSON.stringify(mapStatus, null, 2));

        console.log('\n=== All Console Logs ===');
        logs.forEach(log => console.log(log));

        // Assertions
        expect(mapStatus.hasMap).toBe(true);
        expect(mapStatus.hasLayerGroup).toBe(true);
        expect(mapStatus.layersCount).toBeGreaterThan(0);
        expect(mapStatus.zoom).toBeGreaterThan(2);

        console.log('\n✅ Test PASSED!');
    } else {
        console.log('❌ No autocomplete results found');
        throw new Error('No autocomplete results');
    }
});
