import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch({ 
    headless: false,
    viewport: { width: 1920, height: 1080 },
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });
  
  const context = await browser.newContext();
  const page = await context.newPage();
  
  try {
    console.log('🌐 Navigating to dashboard...');
    await page.goto('http://localhost:8000/dashboard');
    
    // Listen for debug logs
    page.on('console', msg => {
      if (msg.text().includes('mapEventType')) {
        console.log('📝 Debug:', msg.text());
      }
    });
    
    // Wait for events to load
    await page.waitForSelector('#eventsList', { timeout: 10000 });
    await page.waitForTimeout(5000);
    
    // Test CustomEvent with ID 22 (should show "Umweltereignisse" not "Allgemein")
    const testEvent = {
      id: 22,
      title: 'Test CustomEvent - Environment',
      description: 'Testing event type fix',
      event_type: 'environment', // Should be fixed by accessor
      severity: 'medium',
      priority: 'medium',
      latitude: 52.0,
      longitude: 13.0,
      country: 'Germany',
      source: 'custom'
    };
    
    console.log('🔍 Opening CustomEvent with event_type="environment"...');
    await page.evaluate((event) => {
      if (typeof openEventSidebar === 'function') {
        openEventSidebar(event);
      }
    }, testEvent);
    
    // Wait for sidebar
    await page.waitForSelector('#eventSidebar.open', { timeout: 5000 });
    await page.waitForTimeout(3000);
    
    // Check if event type is displayed correctly in the event header
    const eventTypeInHeader = await page.locator('.event-type').textContent();
    console.log('📋 Event type in header:', eventTypeInHeader);
    
    // Also test in event list by creating element and checking content
    console.log('🔍 Testing event type in sidebar list...');
    
    // Look for the event type display in the events list
    const eventElements = await page.locator('#eventsList div').all();
    let foundCorrectEventType = false;
    
    for (let i = 0; i < Math.min(5, eventElements.length); i++) {
      const content = await eventElements[i].textContent();
      if (content && content.includes('Umweltereignisse')) {
        console.log('✅ Found "Umweltereignisse" in events list!');
        foundCorrectEventType = true;
        break;
      } else if (content && content.includes('Allgemein')) {
        console.log('❌ Still found "Allgemein" in events list');
      }
    }
    
    if (!foundCorrectEventType) {
      console.log('⚠️  Did not find "Umweltereignisse" in first 5 events');
    }
    
    // Test another event type - Travel (ID 21)
    console.log('\\n🔍 Testing Travel event type...');
    const travelEvent = {
      id: 21,
      title: 'Test Travel Event',
      event_type: 'travel',
      latitude: 40.0,
      longitude: -74.0,
      source: 'custom'
    };
    
    await page.evaluate((event) => {
      if (typeof openEventSidebar === 'function') {
        openEventSidebar(event);
      }
    }, travelEvent);
    
    await page.waitForTimeout(2000);
    
    const travelEventType = await page.locator('.event-type').textContent();
    console.log('📋 Travel event type in header:', travelEventType);
    
    if (travelEventType.includes('Reiseverkehr')) {
      console.log('✅ Travel event type correctly shows "Reiseverkehr"');
    } else {
      console.log('❌ Travel event type incorrect:', travelEventType);
    }
    
  } catch (error) {
    console.error('❌ Test failed:', error);
  } finally {
    console.log('📋 Test completed. Keeping browser open for 5 seconds...');
    await page.waitForTimeout(5000);
    await browser.close();
  }
})();