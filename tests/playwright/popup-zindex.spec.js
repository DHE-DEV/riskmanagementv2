import { test, expect } from '@playwright/test';

test('Popup should be displayed above filters and badge', async ({ page }) => {
    // Gehe zur embed/map Seite
    await page.goto('http://127.0.0.1:8000/embed/map');
    await page.waitForTimeout(3000); // Warte auf Karten-Laden

    // Hole z-index Werte
    const zIndexes = await page.evaluate(() => {
        const results = {};

        // Popup pane
        const popupPane = document.querySelector('.leaflet-popup-pane');
        if (popupPane) {
            results.popupPane = window.getComputedStyle(popupPane).zIndex;
        }

        // Filter elements (z-[1000])
        const filterElements = document.querySelectorAll('[class*="z-[1000]"]');
        results.filterElementsCount = filterElements.length;
        if (filterElements.length > 0) {
            results.filterZIndex = window.getComputedStyle(filterElements[0]).zIndex;
        }

        // Powered by badge
        const badge = document.querySelector('.powered-by');
        if (badge) {
            results.badgeZIndex = window.getComputedStyle(badge).zIndex;
        }

        return results;
    });

    console.log('Z-Index Werte:', JSON.stringify(zIndexes, null, 2));

    // Klicke auf einen Marker
    const marker = await page.$('.leaflet-marker-icon');
    if (marker) {
        await marker.click();
        await page.waitForTimeout(1000);

        // Screenshot
        await page.screenshot({ path: 'test-results/popup-zindex.png', fullPage: true });

        // Prüfe Popup z-index
        const popupZIndex = await page.evaluate(() => {
            const popup = document.querySelector('.leaflet-popup');
            const popupPane = document.querySelector('.leaflet-popup-pane');
            return {
                popup: popup ? window.getComputedStyle(popup).zIndex : 'nicht gefunden',
                popupPane: popupPane ? window.getComputedStyle(popupPane).zIndex : 'nicht gefunden'
            };
        });

        console.log('Popup z-index nach Klick:', JSON.stringify(popupZIndex, null, 2));

        // Popup sollte über Filter (1000) und Badge (1000) sein
        const popupZ = parseInt(popupZIndex.popupPane) || 0;
        expect(popupZ).toBeGreaterThan(1000);
    }
});
