import { test, expect } from '@playwright/test';

test.describe('Entry Conditions Tests', () => {
    test('should show entry conditions filter container when clicking button', async ({ page }) => {
        // Set viewport to Full HD
        await page.setViewportSize({ width: 1920, height: 1080 });

        // Listen to console messages
        page.on('console', msg => console.log('BROWSER:', msg.text()));
        page.on('pageerror', err => console.log('PAGE ERROR:', err.message));

        // Navigate to dashboard
        await page.goto('http://127.0.0.1:8000');

        // Wait for page to load
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Take screenshot before clicking
        await page.screenshot({ path: 'screenshots/before-entry-conditions.png', fullPage: true });

        // Find and click the Einreisebestimmungen button in the navigation (the one with onclick="showEntryConditions()")
        const entryButton = page.locator('button[onclick="showEntryConditions()"]');
        await expect(entryButton).toBeVisible();
        await entryButton.click();

        // Wait for animation
        await page.waitForTimeout(500);

        // Take screenshot after clicking
        await page.screenshot({ path: 'screenshots/after-entry-conditions-click.png', fullPage: true });

        // Check if left sidebar exists and has the right display style
        const leftSidebar = page.locator('.sidebar.overflow-y-auto');
        const displayStyle = await leftSidebar.evaluate(el => window.getComputedStyle(el).display);
        console.log('Sidebar computed display style:', displayStyle);

        const isAttached = await leftSidebar.evaluate(el => el.isConnected);
        console.log('Sidebar is attached to DOM:', isAttached);

        // Check if entry conditions filter container exists
        const filterContainer = page.locator('#entry-conditions-filter-container');

        // Log the container's visibility
        const isVisible = await filterContainer.isVisible().catch(() => false);
        console.log('Filter container visible:', isVisible);

        // Log all containers in sidebar
        const containers = await leftSidebar.locator('> div').all();
        console.log('Number of containers in sidebar:', containers.length);

        for (let i = 0; i < containers.length; i++) {
            const id = await containers[i].getAttribute('id');
            const className = await containers[i].getAttribute('class');
            const display = await containers[i].evaluate(el => window.getComputedStyle(el).display);
            console.log(`Container ${i}: id="${id}", class="${className}", display="${display}"`);
        }

        // Take screenshot of the sidebar specifically
        await leftSidebar.screenshot({ path: 'screenshots/sidebar-detail.png' });

        // Expect the filter container to be visible
        await expect(filterContainer).toBeVisible();

        // Check for specific filter elements
        const nationalitySelect = page.locator('#nationality-select');
        await expect(nationalitySelect).toBeVisible();

        // Check for passport checkbox
        const passportCheckbox = page.locator('#filter-passport');
        await expect(passportCheckbox).toBeVisible();

        // Final screenshot
        await page.screenshot({ path: 'screenshots/final-entry-conditions.png', fullPage: true });
    });
});
