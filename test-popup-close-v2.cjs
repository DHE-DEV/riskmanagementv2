const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  console.log('🧪 Teste automatisches Schließen von Popups\n');

  await page.goto('http://127.0.0.1:8000', { waitUntil: 'networkidle' });
  await page.waitForTimeout(5000);

  // Liste alle Events auf
  const events = await page.evaluate(() => {
    const eventElements = document.querySelectorAll('#eventsList > div, #futureEventsList > div');
    const eventList = [];

    eventElements.forEach((el, idx) => {
      const titleMatch = el.textContent.match(/[A-ZÄÖÜ][a-zäöüß\s]+/);
      const title = titleMatch ? titleMatch[0].trim() : `Event ${idx}`;

      eventList.push({
        index: idx,
        text: el.textContent.substring(0, 100).replace(/\s+/g, ' ').trim(),
        title: title
      });
    });

    return eventList.slice(0, 10); // Erste 10 Events
  });

  console.log(`📋 Gefundene Events: ${events.length}\n`);

  if (events.length < 2) {
    console.log('⚠️  Nicht genug Events für Test gefunden');
    await browser.close();
    return;
  }

  // 1. Klicke auf erstes Event (vermutlich mit Koordinaten)
  console.log('📍 Schritt 1: Klicke auf erstes Event...');
  console.log(`   Event: "${events[0].text.substring(0, 60)}..."`);

  await page.evaluate((index) => {
    const eventElements = document.querySelectorAll('#eventsList > div, #futureEventsList > div');
    eventElements[index].click();
  }, 0);

  await page.waitForTimeout(2000);

  // Check if popup or sidebar opened
  const state1 = await page.evaluate(() => {
    const popup = document.querySelector('.leaflet-popup');
    const sidebar = document.getElementById('eventSidebar');

    return {
      popupOpen: popup !== null,
      sidebarOpen: sidebar && sidebar.classList.contains('open'),
      popupText: popup ? popup.textContent.substring(0, 50) : null
    };
  });

  console.log(`   Popup: ${state1.popupOpen ? 'Offen ✓' : 'Geschlossen'}`);
  console.log(`   Sidebar: ${state1.sidebarOpen ? 'Offen ✓' : 'Geschlossen'}`);

  await page.screenshot({ path: 'popup-test-step1.png' });
  console.log('   📸 Screenshot: popup-test-step1.png\n');

  // 2. Klicke auf zweites Event
  console.log('📍 Schritt 2: Klicke auf zweites Event...');
  console.log(`   Event: "${events[1].text.substring(0, 60)}..."`);

  await page.evaluate((index) => {
    const eventElements = document.querySelectorAll('#eventsList > div, #futureEventsList > div');
    eventElements[index].click();
  }, 1);

  await page.waitForTimeout(2000);

  // Check state after second click
  const state2 = await page.evaluate(() => {
    const popup = document.querySelector('.leaflet-popup');
    const sidebar = document.getElementById('eventSidebar');

    return {
      popupOpen: popup !== null,
      sidebarOpen: sidebar && sidebar.classList.contains('open'),
      popupText: popup ? popup.textContent.substring(0, 50) : null
    };
  });

  console.log(`   Popup: ${state2.popupOpen ? 'Offen' : 'Geschlossen ✓'}`);
  console.log(`   Sidebar: ${state2.sidebarOpen ? 'Offen ✓' : 'Geschlossen'}`);

  await page.screenshot({ path: 'popup-test-step2.png' });
  console.log('   📸 Screenshot: popup-test-step2.png\n');

  // Evaluation
  if (state1.popupOpen && !state2.popupOpen) {
    console.log('✅ ERFOLG: Popup vom ersten Event wurde beim zweiten Klick geschlossen!');
  } else if (!state1.popupOpen && state1.sidebarOpen && !state2.popupOpen) {
    console.log('✅ OK: Erstes Event hatte keine Koordinaten, zweites auch nicht');
  } else if (state1.popupOpen && state2.popupOpen) {
    console.log('ℹ️  Beide Events haben Koordinaten - Popup wechselte zum zweiten Event');
  } else {
    console.log('ℹ️  Verschiedene Event-Typen getestet');
  }

  await browser.close();
  console.log('\n✅ Test abgeschlossen\n');
})();
