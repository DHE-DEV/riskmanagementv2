import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

// Test-Credentials
const PDS_CREDENTIALS = {
  email: 'p1@dhe.de',
  password: '5zF7ckwoTD',
  url: 'https://test11-dot-web1-dot-dataservice-development.ey.r.appspot.com/'
};

const GTM_CREDENTIALS = {
  email: 'admin@test.com',
  password: '123123123',
  url: 'https://stage.global-travel-monitor.eu/admin'
};

// Screenshots-Verzeichnis
const screenshotsDir = path.join(process.cwd(), 'test-results', 'sso-e2e-screenshots');
const logsDir = path.join(process.cwd(), 'test-results', 'sso-e2e-logs');

// Erstelle Verzeichnisse wenn nicht vorhanden
if (!fs.existsSync(screenshotsDir)) {
  fs.mkdirSync(screenshotsDir, { recursive: true });
}
if (!fs.existsSync(logsDir)) {
  fs.mkdirSync(logsDir, { recursive: true });
}

test.describe('SSO End-to-End Test - Vollständiger Flow', () => {
  let networkLogs = [];
  let stepCounter = 0;

  test.beforeEach(async ({ page }) => {
    // Reset für jeden Test
    networkLogs = [];
    stepCounter = 0;

    // Netzwerk-Logging aktivieren
    page.on('request', request => {
      const logEntry = {
        timestamp: new Date().toISOString(),
        type: 'REQUEST',
        url: request.url(),
        method: request.method(),
        headers: request.headers(),
        postData: request.postData()
      };
      networkLogs.push(logEntry);

      // SSO-relevante Requests hervorheben
      if (request.url().includes('sso') ||
          request.url().includes('auth') ||
          request.url().includes('login') ||
          request.url().includes('token') ||
          request.url().includes('session')) {
        console.log(`[SSO REQUEST] ${request.method()} ${request.url()}`);
      }
    });

    page.on('response', async response => {
      const logEntry = {
        timestamp: new Date().toISOString(),
        type: 'RESPONSE',
        url: response.url(),
        status: response.status(),
        statusText: response.statusText(),
        headers: response.headers()
      };

      // Body nur bei bestimmten Content-Types speichern
      const contentType = response.headers()['content-type'] || '';
      if (contentType.includes('application/json') ||
          contentType.includes('text/html') ||
          contentType.includes('application/x-www-form-urlencoded')) {
        try {
          logEntry.body = await response.text();
        } catch (e) {
          logEntry.body = '[Could not read body]';
        }
      }

      networkLogs.push(logEntry);

      // SSO-relevante Responses hervorheben
      if (response.url().includes('sso') ||
          response.url().includes('auth') ||
          response.url().includes('login') ||
          response.url().includes('token') ||
          response.url().includes('session')) {
        console.log(`[SSO RESPONSE] ${response.status()} ${response.url()}`);
      }
    });

    // Console-Logs mitloggen
    page.on('console', msg => {
      const logEntry = {
        timestamp: new Date().toISOString(),
        type: 'CONSOLE',
        level: msg.type(),
        text: msg.text()
      };
      networkLogs.push(logEntry);
      console.log(`[BROWSER CONSOLE ${msg.type().toUpperCase()}] ${msg.text()}`);
    });

    // Page Errors mitloggen
    page.on('pageerror', error => {
      const logEntry = {
        timestamp: new Date().toISOString(),
        type: 'PAGE_ERROR',
        message: error.message,
        stack: error.stack
      };
      networkLogs.push(logEntry);
      console.error(`[PAGE ERROR] ${error.message}`);
    });
  });

  test.afterEach(async ({ page }) => {
    // Speichere alle Netzwerk-Logs
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const logFile = path.join(logsDir, `network-logs-${timestamp}.json`);
    fs.writeFileSync(logFile, JSON.stringify(networkLogs, null, 2));
    console.log(`\n📝 Netzwerk-Logs gespeichert: ${logFile}`);
    console.log(`📊 Anzahl Logs: ${networkLogs.length}`);

    // Statistik der SSO-relevanten Requests
    const ssoRequests = networkLogs.filter(log =>
      log.type === 'REQUEST' &&
      (log.url.includes('sso') || log.url.includes('auth') || log.url.includes('login'))
    );
    console.log(`🔐 SSO-relevante Requests: ${ssoRequests.length}`);
  });

  async function takeScreenshot(pageObj, stepName) {
    stepCounter++;
    const fileName = `step-${stepCounter.toString().padStart(2, '0')}-${stepName}.png`;
    const filePath = path.join(screenshotsDir, fileName);
    await pageObj.screenshot({ path: filePath, fullPage: true });
    console.log(`📸 Screenshot gespeichert: ${fileName}`);
    return filePath;
  }

  test('Vollständiger SSO-Flow: PDS Login -> GTM Weiterleitung -> Auto-Login', async ({ page }) => {
    test.setTimeout(300000); // 5 Minuten Timeout

    console.log('\n' + '='.repeat(80));
    console.log('🚀 START: SSO End-to-End Test');
    console.log('='.repeat(80) + '\n');

    // SCHRITT 1: PDS Homepage öffnen
    console.log('\n📍 SCHRITT 1: Öffne PDS Homepage');
    console.log(`URL: ${PDS_CREDENTIALS.url}`);

    try {
      await page.goto(PDS_CREDENTIALS.url, {
        waitUntil: 'networkidle',
        timeout: 30000
      });
      await page.waitForLoadState('domcontentloaded');
      await takeScreenshot(page, 'pds-homepage-geladen');
      console.log('✅ PDS Homepage erfolgreich geladen');
    } catch (error) {
      console.error('❌ Fehler beim Laden der PDS Homepage:', error.message);
      await takeScreenshot(page, 'pds-homepage-fehler');
      throw error;
    }

    // SCHRITT 2: Klicke auf Login-Button und öffne Modal
    console.log('\n📍 SCHRITT 2: Klicke auf Login-Button in der Navigation');

    try {
      // Suche Login-Button in der Navigation
      const loginNavButton = await page.locator('a:has-text("Login"), button:has-text("Login")').first();
      const count = await loginNavButton.count();

      if (count === 0) {
        throw new Error('Login-Button in Navigation nicht gefunden');
      }

      console.log('✅ Login-Button in Navigation gefunden');
      await takeScreenshot(page, 'pds-vor-login-klick');

      // Klicke auf Login-Button (öffnet Modal)
      await loginNavButton.click();
      console.log('✅ Login-Button geklickt');

      // Warte auf Login-Modal
      await page.waitForTimeout(1000);
      await takeScreenshot(page, 'pds-login-modal-geoeffnet');

      console.log(`📊 Aktuelle URL: ${page.url()}`);
    } catch (error) {
      console.error('❌ Fehler beim Klicken des Login-Buttons:', error.message);
      await takeScreenshot(page, 'pds-login-button-fehler');
      throw error;
    }

    // SCHRITT 3: Login-Formular finden und ausfüllen
    console.log('\n📍 SCHRITT 3: Login-Formular ausfüllen');
    console.log(`Email: ${PDS_CREDENTIALS.email}`);

    try {
      // Warte auf Login-Formular
      await page.waitForSelector('input[type="email"], input[name="email"], input#email, input[name="username"]', {
        timeout: 10000
      });

      await takeScreenshot(page, 'pds-login-formular');

      // Fülle Email ein
      const emailInput = await page.locator('input[type="email"], input[name="email"], input#email, input[name="username"]').first();
      await emailInput.fill(PDS_CREDENTIALS.email);
      console.log('✅ Email eingegeben');

      // Fülle Passwort ein
      const passwordInput = await page.locator('input[type="password"], input[name="password"]').first();
      await passwordInput.fill(PDS_CREDENTIALS.password);
      console.log('✅ Passwort eingegeben');

      await takeScreenshot(page, 'pds-login-ausgefuellt');
    } catch (error) {
      console.error('❌ Fehler beim Ausfüllen des Login-Formulars:', error.message);
      await takeScreenshot(page, 'pds-login-formular-fehler');
      throw error;
    }

    // SCHRITT 4: Login absenden
    console.log('\n📍 SCHRITT 4: Login absenden');

    try {
      // Finde Login-Button INNERHALB des Modals (nicht in der Navigation)
      const modal = await page.locator('[role="dialog"], .modal').first();
      const loginButton = await modal.locator('button[type="submit"], button:has-text("Login")').first();

      // Klicke auf Login-Button
      await loginButton.click();
      console.log('✅ Login-Button geklickt');

      // Warte auf Response vom Login-Request
      await page.waitForTimeout(3000);
      await takeScreenshot(page, 'pds-nach-login-klick');

      // Prüfe auf Login-Erfolgsmeldung
      const successMsg = await page.locator('.alert-success, .success, text=/Login.*[Ss]uccessful/').textContent().catch(() => '');
      if (successMsg) {
        console.log(`✅ Login-Erfolgsmeldung: ${successMsg.trim()}`);
      }

      // Prüfe ob Modal geschlossen wurde
      await page.waitForTimeout(1000);
      const modalStillOpen = await page.locator('.modal.show, [role="dialog"][aria-modal="true"]').count();
      console.log(`📊 Modal noch offen: ${modalStillOpen > 0}`);

      // Warte auf vollständiges Laden nach Login
      await page.waitForTimeout(2000);
      await takeScreenshot(page, 'pds-nach-login');

      // Prüfe auf Login-Indikatoren (My Account, Logout, oder User-Bereich)
      const myAccountVisible = await page.locator('a:has-text("My Account"), button:has-text("My Account")').count();
      const logoutVisible = await page.locator('a:has-text("Log out"), a:has-text("Logout")').count();
      const userAreaVisible = await page.locator('.user-menu, [data-user-menu]').count();

      console.log(`📊 Login-Indikatoren gefunden:`);
      console.log(`   - My Account: ${myAccountVisible > 0}`);
      console.log(`   - Logout-Link: ${logoutVisible > 0}`);
      console.log(`   - User-Bereich: ${userAreaVisible > 0}`);

      console.log(`📊 Aktuelle URL: ${page.url()}`);

      if (myAccountVisible === 0 && logoutVisible === 0 && userAreaVisible === 0) {
        throw new Error('Login scheint fehlgeschlagen zu sein - keine Login-Indikatoren gefunden');
      }

      console.log('✅ Login erfolgreich abgeschlossen');
    } catch (error) {
      console.error('❌ Fehler beim Login:', error.message);
      await takeScreenshot(page, 'pds-login-fehler');
      throw error;
    }

    // SCHRITT 5: Global Travel Monitor Link finden und klicken
    console.log('\n📍 SCHRITT 5: Suche "Global Travel Monitor" Link');

    try {
      // Warte kurz damit die Seite vollständig geladen ist
      await page.waitForTimeout(2000);
      await takeScreenshot(page, 'pds-nach-login-vollstaendig');

      // Suche nach verschiedenen möglichen Link-Varianten
      const linkSelectors = [
        'a:has-text("Global Travel Monitor")',
        'a:has-text("global travel monitor")',
        'a:has-text("GTM")',
        'a[href*="global-travel-monitor"]',
        'a[href*="riskmanagement"]',
        'a[href*="stage.global-travel-monitor"]'
      ];

      let gtmLink = null;
      for (const selector of linkSelectors) {
        gtmLink = await page.locator(selector).first();
        const count = await gtmLink.count();
        if (count > 0) {
          console.log(`✅ GTM Link gefunden mit Selektor: ${selector}`);
          break;
        }
      }

      if (!gtmLink || await gtmLink.count() === 0) {
        console.log('⚠️ GTM Link nicht gefunden, zeige Seiteninhalt:');
        const bodyText = await page.textContent('body');
        console.log(bodyText.substring(0, 500));
        throw new Error('Global Travel Monitor Link nicht gefunden');
      }

      await takeScreenshot(page, 'pds-gtm-link-gefunden');

      // Klicke auf GTM Link
      console.log('\n📍 SCHRITT 6: Klicke auf Global Travel Monitor Link');

      // Prüfe ob Link in neuem Tab/Fenster öffnet
      const target = await gtmLink.getAttribute('target');
      console.log(`📊 Link target: ${target}`);

      if (target === '_blank') {
        console.log('⚠️ Link öffnet in neuem Tab - warte auf neues Page-Objekt');

        // Warte auf neuen Tab/Popup
        const [newPage] = await Promise.all([
          page.context().waitForEvent('page'),
          gtmLink.click()
        ]);

        console.log('✅ Neuer Tab geöffnet');
        await newPage.waitForLoadState('domcontentloaded');
        await takeScreenshot(newPage, 'gtm-neuer-tab-geladen');

        console.log(`📊 Neue Tab URL: ${newPage.url()}`);

        // Wechsle zum neuen Tab
        page = newPage;
      } else {
        // Link öffnet im selben Tab
        await Promise.all([
          page.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }),
          gtmLink.click()
        ]);

        console.log('✅ GTM Link geklickt');
        await page.waitForLoadState('domcontentloaded');
        await takeScreenshot(page, 'gtm-nach-klick');

        console.log(`📊 Aktuelle URL nach Klick: ${page.url()}`);
      }
    } catch (error) {
      console.error('❌ Fehler beim Klicken des GTM Links:', error.message);
      await takeScreenshot(page, 'pds-gtm-link-fehler');
      throw error;
    }

    // SCHRITT 7: Verifiziere SSO-Weiterleitung zu GTM
    console.log('\n📍 SCHRITT 7: Verifiziere SSO-Weiterleitung');

    try {
      const currentUrl = page.url();
      console.log(`📊 Aktuelle URL: ${currentUrl}`);

      // Prüfe ob wir zur GTM-Domain weitergeleitet wurden
      const isGTMDomain = currentUrl.includes('global-travel-monitor.eu') ||
                          currentUrl.includes('stage.global-travel-monitor');

      if (!isGTMDomain) {
        console.warn('⚠️ Nicht zur GTM-Domain weitergeleitet');
        console.log(`Erwartete Domain: global-travel-monitor.eu`);
        console.log(`Tatsächliche URL: ${currentUrl}`);
      } else {
        console.log('✅ Erfolgreich zur GTM-Domain weitergeleitet');
      }

      await takeScreenshot(page, 'gtm-weiterleitung');

      // Warte auf vollständiges Laden
      await page.waitForLoadState('networkidle', { timeout: 30000 });
      await page.waitForTimeout(2000);
      await takeScreenshot(page, 'gtm-vollstaendig-geladen');

    } catch (error) {
      console.error('❌ Fehler bei SSO-Weiterleitung:', error.message);
      await takeScreenshot(page, 'gtm-weiterleitung-fehler');
      throw error;
    }

    // SCHRITT 8: Prüfe automatischen Login
    console.log('\n📍 SCHRITT 8: Prüfe automatischen Login via SSO');

    try {
      const currentUrl = page.url();
      console.log(`📊 Finale URL: ${currentUrl}`);

      // Prüfe verschiedene Indikatoren für erfolgreichen Login
      const loginIndicators = {
        noLoginForm: false,
        hasDashboard: false,
        hasLogoutButton: false,
        hasUserMenu: false,
        notOnLoginPage: false
      };

      // Prüfe ob Login-Formular NICHT vorhanden ist
      const loginFormCount = await page.locator('input[type="email"], input[name="email"]').count();
      loginIndicators.noLoginForm = loginFormCount === 0;
      console.log(`${loginIndicators.noLoginForm ? '✅' : '❌'} Login-Formular nicht vorhanden: ${loginIndicators.noLoginForm}`);

      // Prüfe auf Dashboard-Elemente
      const dashboardCount = await page.locator('text=Dashboard, text=dashboard').count();
      loginIndicators.hasDashboard = dashboardCount > 0;
      console.log(`${loginIndicators.hasDashboard ? '✅' : '❌'} Dashboard gefunden: ${loginIndicators.hasDashboard}`);

      // Prüfe auf Logout-Button
      const logoutCount = await page.locator('text=Logout, text=Abmelden, a[href*="logout"]').count();
      loginIndicators.hasLogoutButton = logoutCount > 0;
      console.log(`${loginIndicators.hasLogoutButton ? '✅' : '❌'} Logout-Button gefunden: ${loginIndicators.hasLogoutButton}`);

      // Prüfe auf User-Menu
      const userMenuCount = await page.locator('[data-user-menu], .user-menu, #user-menu').count();
      loginIndicators.hasUserMenu = userMenuCount > 0;
      console.log(`${loginIndicators.hasUserMenu ? '✅' : '❌'} User-Menu gefunden: ${loginIndicators.hasUserMenu}`);

      // Prüfe ob wir NICHT auf Login-Seite sind
      loginIndicators.notOnLoginPage = !currentUrl.includes('/login') && !currentUrl.includes('/auth');
      console.log(`${loginIndicators.notOnLoginPage ? '✅' : '❌'} Nicht auf Login-Seite: ${loginIndicators.notOnLoginPage}`);

      await takeScreenshot(page, 'gtm-login-status');

      // Zeige Seiteninhalt zur Analyse
      console.log('\n📄 Seiteninhalt (erste 1000 Zeichen):');
      const bodyText = await page.textContent('body');
      console.log(bodyText.substring(0, 1000));

      // Zähle erfolgreiche Indikatoren
      const successCount = Object.values(loginIndicators).filter(v => v).length;
      console.log(`\n📊 Login-Indikatoren: ${successCount}/${Object.keys(loginIndicators).length} erfolgreich`);

      if (successCount >= 2) {
        console.log('✅ Automatischer SSO-Login vermutlich erfolgreich');
      } else {
        console.warn('⚠️ Automatischer SSO-Login möglicherweise fehlgeschlagen');
        console.log('Details der Indikatoren:', loginIndicators);
      }

      await takeScreenshot(page, 'gtm-final-status');

    } catch (error) {
      console.error('❌ Fehler bei Login-Prüfung:', error.message);
      await takeScreenshot(page, 'gtm-login-pruefung-fehler');
      throw error;
    }

    // SCHRITT 9: Cookies und Session-Daten analysieren
    console.log('\n📍 SCHRITT 9: Analysiere Cookies und Session');

    try {
      const cookies = await page.context().cookies();
      console.log(`\n🍪 Anzahl Cookies: ${cookies.length}`);

      // Filtere SSO-relevante Cookies
      const ssoCookies = cookies.filter(c =>
        c.name.toLowerCase().includes('session') ||
        c.name.toLowerCase().includes('token') ||
        c.name.toLowerCase().includes('auth') ||
        c.name.toLowerCase().includes('sso') ||
        c.name.toLowerCase().includes('laravel')
      );

      console.log(`🔐 SSO-relevante Cookies: ${ssoCookies.length}`);
      ssoCookies.forEach(cookie => {
        console.log(`  - ${cookie.name}: ${cookie.value.substring(0, 50)}...`);
      });

      // Speichere Cookies in Logs
      const cookieLogEntry = {
        timestamp: new Date().toISOString(),
        type: 'COOKIES',
        total: cookies.length,
        ssoCookies: ssoCookies,
        allCookies: cookies
      };
      networkLogs.push(cookieLogEntry);

    } catch (error) {
      console.error('❌ Fehler bei Cookie-Analyse:', error.message);
    }

    console.log('\n' + '='.repeat(80));
    console.log('🏁 ENDE: SSO End-to-End Test abgeschlossen');
    console.log('='.repeat(80) + '\n');

    console.log('\n📂 Test-Artefakte:');
    console.log(`   Screenshots: ${screenshotsDir}`);
    console.log(`   Logs: ${logsDir}`);
    console.log(`   Anzahl Screenshots: ${stepCounter}`);
    console.log(`   Anzahl Netzwerk-Events: ${networkLogs.length}`);
  });
});
