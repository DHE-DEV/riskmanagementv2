const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  console.log('🚀 Lade Dashboard und fokussiere auf Italien...\n');

  await page.goto('http://127.0.0.1:8000', { waitUntil: 'networkidle' });
  await page.waitForTimeout(5000);

  // Zoom to Italy coordinates (Roma: 41.8967, 12.4822)
  await page.evaluate(() => {
    if (typeof map !== 'undefined') {
      map.setView([41.8967, 12.4822], 7);
    }
  });

  await page.waitForTimeout(2000);

  // Take screenshot of Italy area
  await page.screenshot({
    path: 'italy-markers.png',
    fullPage: false
  });

  console.log('📸 Screenshot von Italien gespeichert: italy-markers.png');

  // Check marker HTML
  const markerInfo = await page.evaluate(() => {
    const markers = document.querySelectorAll('.leaflet-marker-icon');
    const markerDetails = [];

    markers.forEach((marker, idx) => {
      const icon = marker.querySelector('i');
      if (icon) {
        markerDetails.push({
          index: idx,
          classes: icon.className,
          innerHTML: marker.innerHTML.substring(0, 150)
        });
      }
    });

    return markerDetails;
  });

  console.log(`\n🗺️ Gefundene Marker: ${markerInfo.length}`);

  if (markerInfo.length > 0) {
    console.log('\nIcon-Klassen der ersten 10 Marker:');
    markerInfo.slice(0, 10).forEach(m => {
      console.log(`  ${m.index}: ${m.classes}`);

      // Highlight if it's a globe icon
      if (m.classes.includes('fa-globe')) {
        console.log('    ⚠️ ^^^ GLOBUS ICON ^^^');
      }
      // Highlight if it's exclamation-triangle
      if (m.classes.includes('fa-exclamation-triangle')) {
        console.log('    ✅ ^^^ AUSRUFEZEICHEN-DREIECK ^^^');
      }
    });
  }

  await browser.close();
  console.log('\n✅ Test abgeschlossen\n');
})();
