const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  console.log('🧪 Teste Marker-Clustering und Auto-Spiderfy\n');

  await page.goto('http://127.0.0.1:8000', { waitUntil: 'networkidle' });
  await page.waitForTimeout(5000);

  // Zoom to Italy
  await page.evaluate(() => {
    if (typeof map !== 'undefined') {
      map.setView([41.896155, 12.476934], 12); // Italien Standard-Koordinaten
    }
  });

  await page.waitForTimeout(2000);

  // Check for clusters
  const clusterInfo = await page.evaluate(() => {
    const clusters = document.querySelectorAll('.custom-cluster-icon');
    const markers = document.querySelectorAll('.leaflet-marker-icon:not(.custom-cluster-icon)');

    return {
      clusterCount: clusters.length,
      markerCount: markers.length,
      totalVisible: clusters.length + markers.length
    };
  });

  console.log('📊 Karten-Status:');
  console.log(`  Cluster: ${clusterInfo.clusterCount}`);
  console.log(`  Einzelne Marker: ${clusterInfo.markerCount}`);
  console.log(`  Gesamt sichtbar: ${clusterInfo.totalVisible}`);

  // Take screenshot before click
  await page.screenshot({ path: 'clustering-before.png' });
  console.log('\n📸 Screenshot gespeichert: clustering-before.png');

  // Find Event #157 in the list and click it
  console.log('\n🖱️  Klicke auf Event #157 in der Liste...');

  const eventClicked = await page.evaluate(() => {
    const events = document.querySelectorAll('#eventsList > div, #futureEventsList > div');

    for (const eventEl of events) {
      if (eventEl.textContent.includes('Streik im öffentlichen Nahverkehr in Rom')) {
        eventEl.click();
        return true;
      }
    }
    return false;
  });

  if (eventClicked) {
    console.log('✅ Event #157 wurde geklickt');

    // Wait for animations
    await page.waitForTimeout(2000);

    // Check if spiderfy happened
    const spiderfyInfo = await page.evaluate(() => {
      const spiderfiedMarkers = document.querySelectorAll('.leaflet-marker-icon.leaflet-zoom-animated');
      const popup = document.querySelector('.leaflet-popup');

      return {
        spiderfiedCount: spiderfiedMarkers.length,
        popupVisible: popup !== null,
        popupContent: popup ? popup.textContent.substring(0, 100) : null
      };
    });

    console.log('\n🕷️  Spiderfy-Status:');
    console.log(`  Marker sichtbar: ${spiderfyInfo.spiderfiedCount}`);
    console.log(`  Popup sichtbar: ${spiderfyInfo.popupVisible ? 'Ja' : 'Nein'}`);

    if (spiderfyInfo.popupVisible) {
      console.log(`  Popup-Inhalt: ${spiderfyInfo.popupContent.substring(0, 50)}...`);

      if (spiderfyInfo.popupContent.includes('Streik')) {
        console.log('\n✅ ERFOLG: Event #157 Popup wird korrekt angezeigt!');
      }
    }

    // Take screenshot after click
    await page.screenshot({ path: 'clustering-after.png' });
    console.log('\n📸 Screenshot gespeichert: clustering-after.png');

  } else {
    console.log('❌ Event #157 nicht in der Liste gefunden');
  }

  await browser.close();
  console.log('\n✅ Test abgeschlossen\n');
})();
