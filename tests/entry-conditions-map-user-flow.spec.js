import { test, expect } from '@playwright/test';

test('Map highlighting with user interaction', async ({ page }) => {
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

    console.log('\n=== Page loaded ===');

    // Warte 2 Sekunden
    await page.waitForTimeout(2000);

    // Klicke auf das Reiseziele-Eingabefeld
    console.log('Clicking destinations input...');
    await page.click('#destinationsFilterInput');
    await page.fill('#destinationsFilterInput', 'Türkei');

    // Warte auf Debounce und API-Response
    await page.waitForTimeout(1000);

    // Screenshot der Autocomplete-Ergebnisse
    await page.screenshot({ path: 'screenshots/autocomplete-results.png', fullPage: false });

    // Prüfe ob Autocomplete-Ergebnisse da sind
    const hasResults = await page.locator('#destinationsFilterResults .autocomplete-item').count();
    console.log('Autocomplete results count:', hasResults);

    if (hasResults > 0) {
        // Klicke auf "Übernehmen" Button
        console.log('Clicking first result...');
        await page.locator('#destinationsFilterResults .autocomplete-item button').first().click();
        await page.waitForTimeout(500);

        // Prüfe ob Türkei in den Selected Destinations ist
        const selectedText = await page.locator('#selectedDestinationsDisplay').textContent();
        console.log('Selected destinations:', selectedText);

        // Screenshot vor Suche
        await page.screenshot({ path: 'screenshots/before-search.png', fullPage: false });

        // Klicke auf Suchen
        console.log('Clicking search button...');
        await page.click('button:has-text("Suchen")');

        // Warte auf Map-Updates
        await page.waitForTimeout(5000);

        // Screenshot nach Suche
        await page.screenshot({ path: 'screenshots/after-search.png', fullPage: false });

        // Prüfe Map-Status
        const mapStatus = await page.evaluate(() => {
            return {
                layersCount: window.countryLayersGroup?.getLayers()?.length || 0,
                zoom: window.entryConditionsMap?.getZoom() || 0,
                center: window.entryConditionsMap?.getCenter()
            };
        });

        console.log('\n=== Final Map Status ===');
        console.log('Layers:', mapStatus.layersCount);
        console.log('Zoom:', mapStatus.zoom);
        console.log('Center:', mapStatus.center);

        // Ausgabe aller Console-Logs
        console.log('\n=== All Browser Console Logs ===');
        logs.forEach(log => console.log(log));

        // Assertions
        expect(mapStatus.layersCount).toBeGreaterThan(0);
        expect(mapStatus.zoom).toBeGreaterThan(2);
    } else {
        console.log('No autocomplete results - test cannot continue');
        throw new Error('No autocomplete results found');
    }
});
