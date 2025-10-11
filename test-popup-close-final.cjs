const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  console.log('🧪 Teste automatisches Schließen von Popups bei Events ohne Koordinaten\n');

  await page.goto('http://127.0.0.1:8000', { waitUntil: 'networkidle' });
  await page.waitForTimeout(5000);

  // Schritt 1: Klicke auf Event #157 (MIT Koordinaten)
  console.log('📍 Schritt 1: Klicke auf Event #157 (mit Koordinaten)...');

  const event157Clicked = await page.evaluate(() => {
    const events = document.querySelectorAll('#eventsList > div, #futureEventsList > div');

    for (const eventEl of events) {
      if (eventEl.textContent.includes('Streik im öffentlichen Nahverkehr')) {
        eventEl.click();
        return true;
      }
    }
    return false;
  });

  if (!event157Clicked) {
    console.log('⚠️  Event #157 nicht gefunden, verwende erstes Event...');
    await page.evaluate(() => {
      const events = document.querySelectorAll('#eventsList > div');
      if (events.length > 0) events[0].click();
    });
  } else {
    console.log('✅ Event #157 geklickt');
  }

  await page.waitForTimeout(2000);

  const state1 = await page.evaluate(() => {
    const popup = document.querySelector('.leaflet-popup');
    const sidebar = document.getElementById('eventSidebar');

    return {
      popupOpen: popup !== null,
      popupText: popup ? popup.textContent.substring(0, 80) : null,
      sidebarOpen: sidebar && sidebar.classList.contains('open')
    };
  });

  console.log(`   Popup: ${state1.popupOpen ? '✓ Offen' : '✗ Geschlossen'}`);
  if (state1.popupText) {
    console.log(`   Popup-Inhalt: "${state1.popupText.substring(0, 50).replace(/\s+/g, ' ')}..."`);
  }
  console.log(`   Sidebar: ${state1.sidebarOpen ? 'Offen' : 'Geschlossen'}`);

  await page.screenshot({ path: 'test-with-coords.png' });
  console.log('   📸 Screenshot: test-with-coords.png\n');

  // Schritt 2: Klicke auf Event ohne Koordinaten (z.B. #121)
  console.log('📍 Schritt 2: Klicke auf Event OHNE Koordinaten...');

  const eventWithoutCoordsClicked = await page.evaluate(() => {
    const events = document.querySelectorAll('#eventsList > div, #futureEventsList > div');

    // Suche nach Event #121 oder einem anderen ohne Koordinaten
    for (const eventEl of events) {
      if (eventEl.textContent.includes('Visumfreiheit') ||
          eventEl.textContent.includes('Feuer im Etosha') ||
          eventEl.textContent.includes('Ryanair')) {
        eventEl.click();
        return {
          found: true,
          title: eventEl.textContent.substring(0, 60).replace(/\s+/g, ' ')
        };
      }
    }
    return { found: false };
  });

  if (eventWithoutCoordsClicked.found) {
    console.log(`✅ Event ohne Koordinaten geklickt: "${eventWithoutCoordsClicked.title.substring(0, 50)}..."`);
  } else {
    console.log('⚠️  Kein Event ohne Koordinaten gefunden');
  }

  await page.waitForTimeout(2000);

  const state2 = await page.evaluate(() => {
    const popup = document.querySelector('.leaflet-popup');
    const sidebar = document.getElementById('eventSidebar');

    return {
      popupOpen: popup !== null,
      sidebarOpen: sidebar && sidebar.classList.contains('open')
    };
  });

  console.log(`   Popup: ${state2.popupOpen ? '✗ Noch offen' : '✓ Geschlossen'}`);
  console.log(`   Sidebar: ${state2.sidebarOpen ? '✓ Offen' : '✗ Geschlossen'}`);

  await page.screenshot({ path: 'test-without-coords.png' });
  console.log('   📸 Screenshot: test-without-coords.png\n');

  // Auswertung
  console.log('📊 Ergebnis:');

  if (state1.popupOpen && !state2.popupOpen && state2.sidebarOpen) {
    console.log('✅ ✅ ✅ PERFEKT!');
    console.log('   - Popup vom ersten Event wurde geschlossen');
    console.log('   - Detail-Sidebar wurde geöffnet');
  } else if (state1.popupOpen && !state2.popupOpen && !state2.sidebarOpen) {
    console.log('✅ Popup geschlossen (Detail-Sidebar evtl. nicht sichtbar im Test)');
  } else if (!state1.popupOpen && state2.sidebarOpen) {
    console.log('ℹ️  Erstes Event hatte keine Koordinaten, Sidebar-Wechsel funktioniert');
  } else {
    console.log('⚠️  Unerwarteter Zustand');
  }

  await browser.close();
  console.log('\n✅ Test abgeschlossen\n');
})();
