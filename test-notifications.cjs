const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  try {
    console.log('1. Navigiere zur Login-Seite...');
    await page.goto('http://127.0.0.1:8000/customer/login');
    await page.waitForTimeout(1000);

    console.log('2. Login als Customer...');
    await page.fill('input[name="email"]', 'p1@dhe.de');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/customer/dashboard', { timeout: 10000 });
    await page.waitForTimeout(2000);

    console.log('3. Screenshot nach Login...');
    await page.screenshot({ path: '/tmp/01-after-login.png', fullPage: true });

    console.log('4. Suche Bell-Icon...');
    const bellIcon = await page.locator('i.fa-bell').first();
    const isBellVisible = await bellIcon.isVisible();
    console.log('   Bell-Icon sichtbar:', isBellVisible);

    if (isBellVisible) {
      const bellBox = await bellIcon.boundingBox();
      console.log('   Bell-Icon Position:', bellBox);
    }

    // Prüfe Badge
    const badge = await page.locator('span.bg-red-500').first();
    const isBadgeVisible = await badge.isVisible().catch(() => false);
    console.log('   Badge sichtbar:', isBadgeVisible);

    if (isBadgeVisible) {
      const badgeText = await badge.textContent();
      console.log('   Badge Text:', badgeText);
    }

    console.log('5. Screenshot von Navigation...');
    await page.screenshot({ path: '/tmp/02-navigation-view.png' });

    console.log('6. Klicke auf Bell-Icon...');
    await bellIcon.click();
    await page.waitForTimeout(1000);

    console.log('7. Screenshot nach Bell-Klick...');
    await page.screenshot({ path: '/tmp/03-after-bell-click.png', fullPage: true });

    // Prüfe Dropdown
    const dropdown = await page.locator('div.absolute.bottom-full').first();
    const isDropdownVisible = await dropdown.isVisible().catch(() => false);
    console.log('   Dropdown sichtbar:', isDropdownVisible);

    if (isDropdownVisible) {
      const dropdownBox = await dropdown.boundingBox();
      console.log('   Dropdown Position:', dropdownBox);
    }

    console.log('8. Warte 3 Sekunden...');
    await page.waitForTimeout(3000);

    console.log('9. Final Screenshot...');
    await page.screenshot({ path: '/tmp/04-final-view.png', fullPage: true });

    console.log('\nScreenshots gespeichert in /tmp/');
    console.log('- 01-after-login.png');
    console.log('- 02-navigation-view.png');
    console.log('- 03-after-bell-click.png');
    console.log('- 04-final-view.png');

  } catch (error) {
    console.error('Fehler:', error);
    await page.screenshot({ path: '/tmp/error-screenshot.png', fullPage: true });
  }

  await page.waitForTimeout(5000);
  await browser.close();
})();
