const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  console.log('🚀 Teste Event #157...\n');

  // Intercept API calls
  let apiData = null;
  page.on('response', async (response) => {
    if (response.url().includes('dashboard-events') || response.url().includes('custom-events/dashboard')) {
      try {
        const data = await response.json();
        apiData = data;
      } catch (e) {}
    }
  });

  // Navigate to dashboard
  await page.goto('http://127.0.0.1:8000', { waitUntil: 'networkidle' });
  await page.waitForTimeout(5000);

  // Find event #157 in API data
  if (apiData) {
    let event157 = null;

    if (apiData.data && apiData.data.events) {
      event157 = apiData.data.events.find(e => e.id === 157);
    } else if (Array.isArray(apiData.data)) {
      event157 = apiData.data.find(e => e.id === 157);
    }

    if (event157) {
      console.log('✅ Event #157 in API Response gefunden:\n');
      console.log('   📌 Title:', event157.title);
      console.log('   🎨 marker_icon:', event157.marker_icon);
      console.log('   🏷️  event_type:', event157.event_type);
      console.log('   📋 event_type_name:', event157.event_type_name);
      console.log('   🎯 priority:', event157.priority);
      console.log('   🌍 Länder:', event157.countries?.length || 0);

      if (event157.countries && event157.countries.length > 0) {
        event157.countries.forEach(country => {
          console.log(`      - ${country.name} (${country.latitude}, ${country.longitude})`);
        });
      }
    } else {
      console.log('❌ Event #157 NICHT in API Response gefunden!');
      console.log('   Verfügbare Event IDs:',
        (apiData.data?.events || apiData.data || []).map(e => e.id).slice(0, 10).join(', ')
      );
    }
  } else {
    console.log('❌ Keine API-Daten abgefangen');
  }

  // Check the JavaScript variables in the page
  console.log('\n🔍 Prüfe Frontend-Daten...\n');

  const frontendData = await page.evaluate(() => {
    // Try to access the events array if it's in global scope
    if (typeof allEvents !== 'undefined') {
      const evt = allEvents.find(e => e.id === 157 || e.original_event_id === 157);
      if (evt) {
        return {
          found: true,
          icon: evt.icon,
          marker_icon: evt.marker_icon,
          event_type: evt.event_type,
          title: evt.title
        };
      }
    }
    return { found: false };
  });

  if (frontendData.found) {
    console.log('✅ Event #157 im Frontend gefunden:');
    console.log('   icon:', frontendData.icon);
    console.log('   marker_icon:', frontendData.marker_icon);
    console.log('   event_type:', frontendData.event_type);
  } else {
    console.log('⚠️ Event #157 nicht in Frontend-Variablen gefunden');
  }

  // Check for the event in the sidebar
  console.log('\n📝 Prüfe Sidebar-Liste...\n');

  const sidebarEvent = await page.evaluate(() => {
    const eventElements = document.querySelectorAll('.event-item, [data-event-id], .sidebar-event');
    for (const el of eventElements) {
      if (el.textContent.includes('Streik im öffentlichen Nahverkehr in Rom')) {
        return {
          found: true,
          text: el.textContent.trim().substring(0, 200),
          classes: el.className
        };
      }
    }
    return { found: false };
  });

  if (sidebarEvent.found) {
    console.log('✅ Event in Sidebar gefunden');
    console.log('   Text:', sidebarEvent.text.replace(/\s+/g, ' '));
  }

  // Take a focused screenshot
  await page.screenshot({
    path: 'event-157-test.png',
    fullPage: false
  });
  console.log('\n📸 Screenshot gespeichert: event-157-test.png');

  await browser.close();
  console.log('\n✅ Test abgeschlossen\n');
})();
