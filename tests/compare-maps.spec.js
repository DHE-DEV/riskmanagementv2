import { test, expect } from '@playwright/test';

test.describe('Map Comparison', () => {
    test.use({ viewport: { width: 1920, height: 1080 } });

    test('Dashboard map displays correctly', async ({ page }) => {
        await page.goto('http://127.0.0.1:8000/');

        // Warte bis die Karte geladen ist
        await page.waitForSelector('#map', { timeout: 10000 });
        await page.waitForTimeout(3000); // Warte auf Tile-Loading

        // Screenshot
        await page.screenshot({
            path: 'tests/screenshots/dashboard-map.png',
            fullPage: false
        });

        console.log('Dashboard screenshot saved');
    });

    test('Entry-conditions map displays correctly', async ({ page }) => {
        await page.goto('http://127.0.0.1:8000/entry-conditions');

        // Warte bis die Karte geladen ist
        await page.waitForSelector('#entry-conditions-map', { timeout: 10000 });
        await page.waitForTimeout(3000); // Warte auf Tile-Loading

        // Screenshot
        await page.screenshot({
            path: 'tests/screenshots/entry-conditions-map.png',
            fullPage: false
        });

        console.log('Entry-conditions screenshot saved');

        // Prüfe ob Karte sichtbar ist
        const mapVisible = await page.isVisible('#entry-conditions-map');
        console.log('Map visible:', mapVisible);

        // Prüfe Kartengröße
        const mapSize = await page.evaluate(() => {
            const map = document.getElementById('entry-conditions-map');
            return {
                width: map.offsetWidth,
                height: map.offsetHeight,
                computed: window.getComputedStyle(map)
            };
        });
        console.log('Map size:', mapSize);

        // Prüfe Leaflet Map Objekt
        const mapInfo = await page.evaluate(() => {
            if (window.entryConditionsMap) {
                const size = window.entryConditionsMap.getSize();
                const bounds = window.entryConditionsMap.getBounds();
                const zoom = window.entryConditionsMap.getZoom();
                return {
                    size: size,
                    bounds: bounds,
                    zoom: zoom,
                    center: window.entryConditionsMap.getCenter()
                };
            }
            return null;
        });
        console.log('Leaflet map info:', JSON.stringify(mapInfo, null, 2));
    });
});
