const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  console.log('🚀 Öffne Dashboard...');

  // Navigate to dashboard
  await page.goto('http://127.0.0.1:8000', { waitUntil: 'networkidle' });

  // Take initial screenshot
  await page.screenshot({ path: 'dashboard-initial.png', fullPage: true });
  console.log('📸 Screenshot gespeichert: dashboard-initial.png');

  // Wait for map to load
  await page.waitForTimeout(3000);

  // Intercept API calls
  const apiResponse = await page.waitForResponse(
    response => response.url().includes('custom-events') || response.url().includes('dashboard-events'),
    { timeout: 10000 }
  ).catch(() => null);

  if (apiResponse) {
    const data = await apiResponse.json();
    console.log('\n📡 API Response erhalten');

    // Find event #157
    let event157 = null;
    if (data.data && data.data.events) {
      event157 = data.data.events.find(e => e.id === 157);
    } else if (Array.isArray(data.data)) {
      event157 = data.data.find(e => e.id === 157);
    }

    if (event157) {
      console.log('\n✅ Event #157 gefunden in API Response:');
      console.log('   Title:', event157.title);
      console.log('   marker_icon:', event157.marker_icon);
      console.log('   event_type:', event157.event_type);
      console.log('   event_type_name:', event157.event_type_name);
      console.log('   priority:', event157.priority);
    } else {
      console.log('\n⚠️ Event #157 nicht in API Response gefunden');
    }
  }

  // Check markers on map
  await page.waitForTimeout(2000);

  // Try to find marker elements
  const markers = await page.$$('.leaflet-marker-icon').catch(() => []);
  console.log(`\n🗺️ Gefundene Marker auf der Karte: ${markers.length}`);

  // Take screenshot of map area
  const mapElement = await page.$('#map').catch(() => null);
  if (mapElement) {
    await mapElement.screenshot({ path: 'map-screenshot.png' });
    console.log('📸 Karten-Screenshot gespeichert: map-screenshot.png');
  }

  // Check if we can find event details in the DOM
  const eventTitles = await page.$$eval('[data-event-id], .event-title, .marker-popup',
    elements => elements.map(el => el.textContent)
  ).catch(() => []);

  console.log('\n📝 Events im DOM:', eventTitles.length);

  // Take final screenshot
  await page.screenshot({ path: 'dashboard-final.png', fullPage: true });
  console.log('📸 Finaler Screenshot gespeichert: dashboard-final.png');

  await browser.close();
  console.log('\n✅ Test abgeschlossen');
})();
