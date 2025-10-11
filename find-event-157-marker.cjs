const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  console.log('🔍 Suche Event #157 Marker auf der Karte...\n');

  await page.goto('http://127.0.0.1:8000', { waitUntil: 'networkidle' });
  await page.waitForTimeout(5000);

  // Zoom to Rome
  await page.evaluate(() => {
    if (typeof map !== 'undefined') {
      map.setView([41.9028, 12.4964], 13); // Event #157 neue Koordinaten
    }
  });

  await page.waitForTimeout(2000);

  // Get ALL marker icons
  const allMarkers = await page.evaluate(() => {
    const markers = document.querySelectorAll('.leaflet-marker-icon');
    const markerData = [];

    markers.forEach((marker, idx) => {
      const icon = marker.querySelector('i');
      if (icon) {
        const classes = icon.className;
        const bounds = marker.getBoundingClientRect();

        markerData.push({
          index: idx,
          classes: classes,
          position: {
            x: Math.round(bounds.left),
            y: Math.round(bounds.top)
          }
        });
      }
    });

    return markerData;
  });

  console.log(`📍 Gefundene Marker: ${allMarkers.length}\n`);

  // Count icon types
  const iconCounts = {};
  allMarkers.forEach(m => {
    const key = m.classes;
    iconCounts[key] = (iconCounts[key] || 0) + 1;
  });

  console.log('📊 Icon-Verteilung:');
  Object.entries(iconCounts).forEach(([icon, count]) => {
    console.log(`  ${icon}: ${count}x`);
    if (icon.includes('fa-exclamation-triangle')) {
      console.log('    ✅ ^^^ DAS IST EVENT #157! ^^^');
    }
  });

  // Screenshot
  await page.screenshot({
    path: 'rome-zoom.png',
    fullPage: false
  });

  console.log('\n📸 Screenshot gespeichert: rome-zoom.png\n');

  await browser.close();
})();
