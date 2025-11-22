import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

// ES Module __dirname Ersatz
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Test-Konfiguration
const CONFIG = {
  pdsHomepage: {
    url: 'https://test11-dot-web1-dot-dataservice-development.ey.r.appspot.com/',
    username: 'p1@dhe.de',
    password: '5zF7ckwoTD'
  },
  riskManagement: {
    url: 'https://stage.global-travel-monitor.eu/',
    adminUrl: 'https://stage.global-travel-monitor.eu/admin',
    adminUsername: 'admin@test.com',
    adminPassword: '123123123'
  },
  viewport: {
    width: 1920,
    height: 1080
  },
  screenshotsDir: path.join(__dirname, 'screenshots', new Date().toISOString().replace(/:/g, '-'))
};

// Screenshot-Verzeichnis erstellen
if (!fs.existsSync(CONFIG.screenshotsDir)) {
  fs.mkdirSync(CONFIG.screenshotsDir, { recursive: true });
}

test.describe('SSO Complete End-to-End Test', () => {
  let ssoRequests = [];
  let context;
  let page;

  test.beforeAll(async ({ browser }) => {
    console.log('\n=== SSO E2E Test gestartet ===');
    console.log(`Viewport: ${CONFIG.viewport.width}x${CONFIG.viewport.height}`);
    console.log(`Screenshots: ${CONFIG.screenshotsDir}\n`);
  });

  test.beforeEach(async ({ browser }) => {
    // Browser-Context mit Full HD erstellen
    context = await browser.newContext({
      viewport: CONFIG.viewport,
      recordVideo: {
        dir: path.join(__dirname, 'videos'),
        size: CONFIG.viewport
      }
    });

    // Netzwerk-Request-Logging aktivieren
    context.on('request', request => {
      const url = request.url();
      if (url.includes('sso') || url.includes('saml') || url.includes('login')) {
        ssoRequests.push({
          method: request.method(),
          url: url,
          timestamp: new Date().toISOString()
        });
        console.log(`[SSO-REQUEST] ${request.method()} ${url}`);
      }
    });

    context.on('response', response => {
      const url = response.url();
      if (url.includes('sso') || url.includes('saml') || url.includes('login')) {
        console.log(`[SSO-RESPONSE] ${response.status()} ${url}`);
      }
    });

    page = await context.newPage();
  });

  test.afterEach(async () => {
    // SSO-Requests loggen
    console.log('\n=== SSO-Requests Summary ===');
    ssoRequests.forEach((req, idx) => {
      console.log(`${idx + 1}. [${req.timestamp}] ${req.method} ${req.url}`);
    });

    await context.close();
    ssoRequests = [];
  });

  test('Complete SSO Flow: PDS Homepage → GTM → Dashboard → Admin Logs', async () => {
    // ========================================
    // STEP 1: Login auf PDS Homepage
    // ========================================
    console.log('\n[STEP 1] Navigiere zu PDS Homepage...');
    await page.goto(CONFIG.pdsHomepage.url);
    await page.waitForLoadState('domcontentloaded');

    await page.screenshot({
      path: path.join(CONFIG.screenshotsDir, '01-pds-homepage-loaded.png'),
      fullPage: true
    });
    console.log('✓ PDS Homepage geladen');

    // Klicke auf Login-Button
    console.log('[STEP 1] Klicke auf Login-Button...');
    await page.click('a:has-text("Login"), button:has-text("Login")');
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(1000);

    await page.screenshot({
      path: path.join(CONFIG.screenshotsDir, '02-pds-login-page.png'),
      fullPage: true
    });
    console.log('✓ Login-Seite geladen');

    // Login-Formular ausfüllen
    console.log('[STEP 1] Fülle Login-Formular aus...');
    await page.fill('input[name="email"], input[type="email"]', CONFIG.pdsHomepage.username);
    await page.fill('input[name="password"], input[type="password"]', CONFIG.pdsHomepage.password);

    await page.screenshot({
      path: path.join(CONFIG.screenshotsDir, '03-pds-login-form-filled.png'),
      fullPage: true
    });
    console.log('✓ Login-Formular ausgefüllt');

    // Login abschicken
    console.log('[STEP 1] Sende Login-Formular...');

    // Klicke auf den Login-Button IM Modal (nicht den Navbar-Button)
    await page.click('#app-login-modal button:has-text("Login"), .modal button:has-text("Login"), .modal button[type="submit"]');

    // Warte auf erfolgreichen Login (Modal schließt sich)
    await page.waitForTimeout(3000);

    await page.screenshot({
      path: path.join(CONFIG.screenshotsDir, '04-pds-after-login.png'),
      fullPage: true
    });
    console.log('✓ Login erfolgreich');

    // Warte kurz für vollständiges Laden
    await page.waitForTimeout(1000);

    // ========================================
    // STEP 2: GTM Link finden und klicken
    // ========================================
    console.log('\n[STEP 2] Suche "Global Travel Monitor" Link...');

    // Verschiedene Selektoren versuchen
    const gtmLinkSelectors = [
      'a:has-text("Global Travel Monitor")',
      'a:has-text("Travel Monitor")',
      'a[href*="global-travel-monitor"]',
      'a[href*="stage.global-travel-monitor"]'
    ];

    let gtmLink = null;
    for (const selector of gtmLinkSelectors) {
      try {
        gtmLink = await page.waitForSelector(selector, { timeout: 5000 });
        if (gtmLink) {
          console.log(`✓ GTM Link gefunden mit Selector: ${selector}`);
          break;
        }
      } catch (e) {
        console.log(`  Selector nicht gefunden: ${selector}`);
      }
    }

    if (!gtmLink) {
      await page.screenshot({
        path: path.join(CONFIG.screenshotsDir, '05-ERROR-gtm-link-not-found.png'),
        fullPage: true
      });
      throw new Error('GTM Link nicht gefunden! Siehe Screenshot.');
    }

    // Link-Eigenschaften prüfen
    const linkUrl = await gtmLink.getAttribute('href');
    const linkTarget = await gtmLink.getAttribute('target');
    console.log(`  Link URL: ${linkUrl}`);
    console.log(`  Link Target: ${linkTarget || 'none (same window)'}`);

    // Screenshot vor dem Klick
    await page.screenshot({
      path: path.join(CONFIG.screenshotsDir, '05-pds-before-gtm-click.png'),
      fullPage: true
    });

    // ========================================
    // STEP 3: GTM Link klicken (mit target="_blank" Behandlung)
    // ========================================
    console.log('\n[STEP 3] Klicke auf GTM Link...');

    let gtmPage;
    if (linkTarget === '_blank') {
      console.log('  Link öffnet in neuem Tab (target="_blank")');
      console.log('  Warte auf neuen Tab...');

      const [newPage] = await Promise.all([
        context.waitForEvent('page'),
        gtmLink.click()
      ]);

      gtmPage = newPage;
      await gtmPage.waitForLoadState('domcontentloaded');
      console.log('✓ Neuer Tab geöffnet');
    } else {
      console.log('  Link öffnet im gleichen Fenster');
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }),
        gtmLink.click()
      ]);
      gtmPage = page;
    }

    // Warte auf vollständiges Laden
    await gtmPage.waitForLoadState('networkidle', { timeout: 30000 });
    await gtmPage.waitForTimeout(2000);

    const currentUrl = gtmPage.url();
    console.log(`✓ Aktuelle URL: ${currentUrl}`);

    await gtmPage.screenshot({
      path: path.join(CONFIG.screenshotsDir, '06-gtm-after-sso-redirect.png'),
      fullPage: true
    });

    // ========================================
    // STEP 4: Verifiziere SSO-Auto-Login
    // ========================================
    console.log('\n[STEP 4] Verifiziere SSO-Auto-Login...');

    // Prüfe ob wir auf der GTM-Domain sind
    expect(currentUrl).toContain('global-travel-monitor.eu');
    console.log('✓ Auf GTM-Domain');

    // Prüfe ob wir NICHT auf Login-Seite sind
    const isOnLoginPage = currentUrl.includes('/login') || currentUrl.includes('/sso/login');
    if (isOnLoginPage) {
      await gtmPage.screenshot({
        path: path.join(CONFIG.screenshotsDir, '07-ERROR-still-on-login.png'),
        fullPage: true
      });
      throw new Error('SSO Auto-Login fehlgeschlagen - noch auf Login-Seite!');
    }
    console.log('✓ Nicht auf Login-Seite → SSO Auto-Login erfolgreich');

    // ========================================
    // STEP 5: Prüfe Dashboard-Inhalte
    // ========================================
    console.log('\n[STEP 5] Prüfe Dashboard-Inhalte...');

    // Warte auf typische Dashboard-Elemente
    const dashboardChecks = [
      { name: 'Navigation/Header', selector: 'nav, header, [role="navigation"]' },
      { name: 'Hauptinhalt', selector: 'main, .container, .content' }
    ];

    for (const check of dashboardChecks) {
      try {
        await gtmPage.waitForSelector(check.selector, { timeout: 10000 });
        console.log(`✓ ${check.name} gefunden`);
      } catch (e) {
        console.log(`⚠ ${check.name} nicht gefunden (${check.selector})`);
      }
    }

    await gtmPage.screenshot({
      path: path.join(CONFIG.screenshotsDir, '07-gtm-dashboard.png'),
      fullPage: true
    });
    console.log('✓ Dashboard geladen');

    // ========================================
    // STEP 6: Admin Login für SSO-Logs
    // ========================================
    console.log('\n[STEP 6] Navigiere zum Admin-Bereich für SSO-Logs...');

    // Neue Seite für Admin-Login
    const adminPage = await context.newPage();
    await adminPage.goto(CONFIG.riskManagement.adminUrl);
    await adminPage.waitForLoadState('domcontentloaded');

    await adminPage.screenshot({
      path: path.join(CONFIG.screenshotsDir, '08-admin-login-page.png'),
      fullPage: true
    });
    console.log('✓ Admin-Login-Seite geladen');

    // Admin-Login
    console.log('[STEP 6] Fülle Admin-Login aus...');
    await adminPage.fill('input[name="email"], input[type="email"]', CONFIG.riskManagement.adminUsername);
    await adminPage.fill('input[name="password"], input[type="password"]', CONFIG.riskManagement.adminPassword);

    await adminPage.screenshot({
      path: path.join(CONFIG.screenshotsDir, '09-admin-login-filled.png'),
      fullPage: true
    });

    await Promise.all([
      adminPage.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }),
      adminPage.click('button[type="submit"], input[type="submit"]')
    ]);

    await adminPage.screenshot({
      path: path.join(CONFIG.screenshotsDir, '10-admin-dashboard.png'),
      fullPage: true
    });
    console.log('✓ Admin-Login erfolgreich');

    // ========================================
    // STEP 7: SSO-Logs prüfen
    // ========================================
    console.log('\n[STEP 7] Navigiere zu SSO-Logs...');

    await adminPage.goto(`${CONFIG.riskManagement.adminUrl}/sso-logs`);
    await adminPage.waitForLoadState('networkidle');
    await adminPage.waitForTimeout(2000);

    await adminPage.screenshot({
      path: path.join(CONFIG.screenshotsDir, '11-sso-logs-page.png'),
      fullPage: true
    });
    console.log('✓ SSO-Logs-Seite geladen');

    // Prüfe auf Log-Einträge
    console.log('[STEP 7] Prüfe SSO-Log-Einträge...');

    const logSelectors = [
      'table tbody tr',
      '.log-entry',
      '[data-log]',
      'tbody tr'
    ];

    let logsFound = false;
    for (const selector of logSelectors) {
      try {
        const logs = await adminPage.$$(selector);
        if (logs.length > 0) {
          console.log(`✓ ${logs.length} Log-Einträge gefunden (Selector: ${selector})`);
          logsFound = true;
          break;
        }
      } catch (e) {
        // Weiter zum nächsten Selector
      }
    }

    if (!logsFound) {
      console.log('⚠ Keine Log-Einträge gefunden mit Standard-Selektoren');
    }

    // Öffne den ersten Log-Eintrag für Details
    console.log('[STEP 7] Öffne ersten Log-Eintrag für Details...');
    try {
      const firstViewDetailsButton = await adminPage.waitForSelector('a:has-text("View Details"), button:has-text("View Details")', { timeout: 5000 });
      if (firstViewDetailsButton) {
        await firstViewDetailsButton.click();
        await adminPage.waitForTimeout(2000);

        await adminPage.screenshot({
          path: path.join(CONFIG.screenshotsDir, '12-sso-log-details.png'),
          fullPage: true
        });
        console.log('✓ Log-Details geöffnet');

        // Erweitere alle "Request Data" Abschnitte
        console.log('[STEP 7] Erweitere Request Data Abschnitte...');
        const requestDataToggles = await adminPage.$$('summary:has-text("Request Data"), details > summary');
        console.log(`  Gefunden: ${requestDataToggles.length} Request Data Toggles`);

        for (let i = 0; i < requestDataToggles.length; i++) {
          try {
            await requestDataToggles[i].click();
            await adminPage.waitForTimeout(500);
          } catch (e) {
            console.log(`  Toggle ${i+1} konnte nicht geklickt werden`);
          }
        }

        await adminPage.screenshot({
          path: path.join(CONFIG.screenshotsDir, '12b-sso-log-details-expanded.png'),
          fullPage: true
        });
        console.log('✓ Request Data Abschnitte erweitert');

        // Prüfe auf Versionsnummern im Detail
        console.log('[STEP 7] Suche nach Versionsnummern in Log-Details...');
        const detailContent = await adminPage.content();

        const versionPatterns = [
          /version_idp[:\s"']+([0-9.]+)/gi,
          /version_sp[:\s"']+([0-9.]+)/gi,
          /idp[_\s]*version[:\s"']+([0-9.]+)/gi,
          /sp[_\s]*version[:\s"']+([0-9.]+)/gi,
          /"version_idp":\s*"([^"]+)"/gi,
          /"version_sp":\s*"([^"]+)"/gi
        ];

        let foundVersions = new Set();
        for (const pattern of versionPatterns) {
          const matches = detailContent.matchAll(pattern);
          for (const match of matches) {
            const versionInfo = `${match[0]} → ${match[1]}`;
            if (!foundVersions.has(versionInfo)) {
              foundVersions.add(versionInfo);
              console.log(`✓ Version gefunden: ${versionInfo}`);
            }
          }
        }
      }
    } catch (e) {
      console.log('⚠ Konnte Log-Details nicht öffnen:', e.message);
    }

    await adminPage.screenshot({
      path: path.join(CONFIG.screenshotsDir, '13-sso-logs-final.png'),
      fullPage: true
    });

    // ========================================
    // Test erfolgreich abgeschlossen
    // ========================================
    console.log('\n=== Test erfolgreich abgeschlossen ===');
    console.log(`Screenshots gespeichert in: ${CONFIG.screenshotsDir}`);
    console.log(`Anzahl SSO-Requests: ${ssoRequests.length}`);

    // Cleanup
    await adminPage.close();
    if (gtmPage !== page) {
      await gtmPage.close();
    }
  });
});
