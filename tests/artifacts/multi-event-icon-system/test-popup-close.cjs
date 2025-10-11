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

  // 1. Klicke auf ein Event MIT Koordinaten (öffnet Popup)
  console.log('📍 Schritt 1: Klicke auf Event MIT Koordinaten...');

  const firstEventClicked = await page.evaluate(() => {
    const events = document.querySelectorAll('#eventsList > div');

    // Finde ein Event mit Koordinaten (z.B. Italien)
    for (const eventEl of events) {
      if (eventEl.textContent.includes('ITALIEN')) {
        eventEl.click();
        return true;
      }
    }
    return false;
  });

  if (firstEventClicked) {
    console.log('✅ Event mit Koordinaten angeklickt');
    await page.waitForTimeout(2000);

    // Check if popup is open
    const popupOpen1 = await page.evaluate(() => {
      const popup = document.querySelector('.leaflet-popup');
      return popup !== null;
    });

    console.log(`   Popup offen: ${popupOpen1 ? 'Ja ✓' : 'Nein ✗'}`);

    // Take screenshot with popup
    await page.screenshot({ path: 'popup-before-close.png' });
    console.log('   📸 Screenshot: popup-before-close.png\n');

    // 2. Klicke auf ein Event OHNE Koordinaten
    console.log('📍 Schritt 2: Klicke auf Event OHNE Koordinaten...');

    const secondEventClicked = await page.evaluate(() => {
      const events = document.querySelectorAll('#eventsList > div');

      // Finde ein Event, das vermutlich keine Koordinaten hat (ALLGEMEIN)
      for (const eventEl of events) {
        if (eventEl.textContent.includes('ALLGEMEIN')) {
          eventEl.click();
          return true;
        }
      }
      return false;
    });

    if (secondEventClicked) {
      console.log('✅ Event ohne Koordinaten angeklickt');
      await page.waitForTimeout(1500);

      // Check if popup is now closed
      const popupOpen2 = await page.evaluate(() => {
        const popup = document.querySelector('.leaflet-popup');
        return popup !== null;
      });

      console.log(`   Popup geschlossen: ${!popupOpen2 ? 'Ja ✓' : 'Nein ✗'}`);

      // Check if sidebar is open
      const sidebarOpen = await page.evaluate(() => {
        const sidebar = document.getElementById('eventSidebar');
        return sidebar && sidebar.classList.contains('open');
      });

      console.log(`   Detail-Sidebar offen: ${sidebarOpen ? 'Ja ✓' : 'Nein ✗'}`);

      // Take screenshot
      await page.screenshot({ path: 'popup-after-close.png' });
      console.log('   📸 Screenshot: popup-after-close.png\n');

      if (!popupOpen2 && sidebarOpen) {
        console.log('✅ ERFOLG: Popup wurde geschlossen und Detail-Sidebar geöffnet!');
      } else if (!popupOpen2 && !sidebarOpen) {
        console.log('⚠️  Popup geschlossen, aber Detail-Sidebar nicht gefunden');
      } else {
        console.log('❌ FEHLER: Popup wurde nicht geschlossen');
      }

    } else {
      console.log('⚠️  Kein Event ohne Koordinaten gefunden');
    }

  } else {
    console.log('⚠️  Kein Event mit Koordinaten gefunden');
  }

  await browser.close();
  console.log('\n✅ Test abgeschlossen\n');
})();
