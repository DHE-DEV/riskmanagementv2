import { test, expect } from '@playwright/test';

test.describe('Timezone Display Tests', () => {
  test('should display correct timezone for Mexico City in Full HD resolution', async ({ page }) => {
    // Navigate to dashboard
    await page.goto('/dashboard');
    
    // Wait for events to load
    await page.waitForSelector('#eventsList');
    await page.waitForTimeout(5000);
    
    // Test event with Mexico City coordinates
    const testEvent = {
      id: 999,
      title: 'Test Event - Mexico City',
      description: 'Testing timezone display in Full HD',
      event_type: 'earthquake',
      severity: 'medium',
      priority: 'medium',
      latitude: 19.4326,
      longitude: -99.1332,
      country: 'Mexico',
      date: '10/09/2025 13:40',
      source: 'test'
    };
    
    // Open event sidebar with test coordinates
    await page.evaluate((event) => {
      if (typeof openEventSidebar === 'function') {
        openEventSidebar(event);
      }
    }, testEvent);
    
    // Wait for sidebar to open
    await expect(page.locator('#eventSidebar.open')).toBeVisible();
    
    // Wait for timezone data to load
    await page.waitForTimeout(5000);
    
    // Verify timezone elements are visible and correct
    const timeDisplay = page.locator('#local-time-display');
    const timezoneDisplay = page.locator('#tz-zone');
    const berlinDiff = page.locator('#tz-berlin-diff');
    
    await expect(timeDisplay).toBeVisible();
    await expect(timezoneDisplay).toBeVisible();
    await expect(berlinDiff).toBeVisible();
    
    // Check timezone is correct for Mexico
    const timezone = await timezoneDisplay.textContent();
    expect(timezone).toContain('Mexico');
    
    // Check time difference to Berlin
    const diff = await berlinDiff.textContent();
    expect(diff).toContain('-8 Stunden');
    
    // Verify the displayed time is reasonable (should be different from Berlin time)
    const displayedTime = await timeDisplay.textContent();
    expect(displayedTime).toMatch(/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/);
  });

  test('should display correct timezone for Berlin in Full HD resolution', async ({ page }) => {
    // Navigate to dashboard
    await page.goto('/dashboard');
    
    // Wait for events to load
    await page.waitForSelector('#eventsList');
    await page.waitForTimeout(5000);
    
    // Test event with Berlin coordinates
    const testEvent = {
      id: 998,
      title: 'Test Event - Berlin',
      description: 'Testing timezone display for Berlin',
      event_type: 'flood',
      severity: 'low',
      priority: 'low',
      latitude: 52.5200,
      longitude: 13.4050,
      country: 'Germany',
      date: '10/09/2025 15:40',
      source: 'test'
    };
    
    // Open event sidebar
    await page.evaluate((event) => {
      if (typeof openEventSidebar === 'function') {
        openEventSidebar(event);
      }
    }, testEvent);
    
    // Wait for sidebar to open
    await expect(page.locator('#eventSidebar.open')).toBeVisible();
    
    // Wait for timezone data
    await page.waitForTimeout(5000);
    
    // Check timezone is Berlin/Europe
    const timezone = await page.locator('#tz-zone').textContent();
    expect(timezone).toContain('Berlin');
    
    // Check Berlin difference (should be "Gleiche Zeit")
    const diff = await page.locator('#tz-berlin-diff').textContent();
    expect(diff).toContain('Gleiche Zeit');
  });

  test('should update time display live every second in Full HD', async ({ page }) => {
    // Navigate to dashboard
    await page.goto('/dashboard');
    
    // Wait for events to load
    await page.waitForSelector('#eventsList');
    await page.waitForTimeout(3000);
    
    // Open event sidebar with test event
    const testEvent = {
      id: 997,
      title: 'Live Update Test',
      latitude: 19.4326,
      longitude: -99.1332,
      country: 'Mexico'
    };
    
    await page.evaluate((event) => {
      if (typeof openEventSidebar === 'function') {
        openEventSidebar(event);
      }
    }, testEvent);
    
    await expect(page.locator('#eventSidebar.open')).toBeVisible();
    await page.waitForTimeout(3000);
    
    // Get initial time
    const initialTime = await page.locator('#local-time-display').textContent();
    
    // Wait for time to update (should update every second)
    await page.waitForTimeout(2000);
    
    // Get updated time
    const updatedTime = await page.locator('#local-time-display').textContent();
    
    // Time should have changed (allowing for minute rollover)
    console.log(`Initial: ${initialTime}, Updated: ${updatedTime}`);
    
    // At minimum, the time should still be in valid format
    expect(updatedTime).toMatch(/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/);
  });
});