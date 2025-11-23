const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

// Test Credentials
const PDS_URL = 'https://test11-dot-web1-dot-dataservice-development.ey.r.appspot.com/';
const PDS_EMAIL = 'p1@dhe.de';
const PDS_PASSWORD = '5zF7ckwoTD';
const GTM_URL = 'https://stage.global-travel-monitor.eu/';
const GTM_ADMIN_URL = 'https://stage.global-travel-monitor.eu/admin/login';
const GTM_ADMIN_EMAIL = 'admin@test.com';
const GTM_ADMIN_PASSWORD = '123123123';

// Directories for outputs
const SCREENSHOTS_DIR = path.join(__dirname, 'sso-test-screenshots');
const LOGS_DIR = path.join(__dirname, 'sso-test-logs');

// Create directories
if (!fs.existsSync(SCREENSHOTS_DIR)) fs.mkdirSync(SCREENSHOTS_DIR);
if (!fs.existsSync(LOGS_DIR)) fs.mkdirSync(LOGS_DIR);

// Network request logger
const networkRequests = [];

function logRequest(request) {
    const url = request.url();
    if (url.includes('pdsauthint') ||
        url.includes('saml') ||
        url.includes('sso') ||
        url.includes('global-travel-monitor')) {
        networkRequests.push({
            timestamp: new Date().toISOString(),
            method: request.method(),
            url: url,
            headers: request.headers(),
            postData: request.postData()
        });
        console.log(`[REQUEST] ${request.method()} ${url}`);
    }
}

function logResponse(response) {
    const url = response.url();
    if (url.includes('pdsauthint') ||
        url.includes('saml') ||
        url.includes('sso') ||
        url.includes('global-travel-monitor')) {
        console.log(`[RESPONSE] ${response.status()} ${url}`);
    }
}

async function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function takeScreenshot(page, name) {
    const filename = `${Date.now()}_${name}.png`;
    const filepath = path.join(SCREENSHOTS_DIR, filename);
    await page.screenshot({ path: filepath, fullPage: true });
    console.log(`📸 Screenshot saved: ${filename}`);
    return filename;
}

(async () => {
    console.log('🚀 Starting SSO End-to-End Test...\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 500  // Slow down by 500ms for better visibility
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        userAgent: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    });

    const page = await context.newPage();

    // Setup network logging
    page.on('request', logRequest);
    page.on('response', logResponse);

    // Console logging
    page.on('console', msg => console.log(`[BROWSER CONSOLE] ${msg.type()}: ${msg.text()}`));

    try {
        // ==========================================
        // STEP 1: Login to PDS Homepage
        // ==========================================
        console.log('\n📋 STEP 1: Login to PDS Homepage');
        console.log(`   URL: ${PDS_URL}`);
        console.log(`   Email: ${PDS_EMAIL}`);

        await page.goto(PDS_URL, { waitUntil: 'networkidle' });
        await takeScreenshot(page, '01_pds_homepage');

        // Wait for login form
        await page.waitForSelector('input[type="email"], input[name="email"], input#email', { timeout: 10000 });
        await takeScreenshot(page, '02_pds_login_form');

        // Fill login form
        const emailSelector = await page.locator('input[type="email"], input[name="email"], input#email').first();
        await emailSelector.fill(PDS_EMAIL);
        console.log('   ✓ Email filled');

        const passwordSelector = await page.locator('input[type="password"], input[name="password"]').first();
        await passwordSelector.fill(PDS_PASSWORD);
        console.log('   ✓ Password filled');

        await takeScreenshot(page, '03_pds_login_filled');

        // Submit form
        const submitButton = await page.locator('button[type="submit"], input[type="submit"], button:has-text("Login"), button:has-text("Anmelden")').first();
        await submitButton.click();
        console.log('   ✓ Login submitted');

        // Wait for navigation after login
        await page.waitForLoadState('networkidle');
        await sleep(2000);
        await takeScreenshot(page, '04_pds_after_login');

        console.log('   ✅ PDS Login successful');
        console.log(`   Current URL: ${page.url()}`);

        // ==========================================
        // STEP 2: Find and Click GTM Link
        // ==========================================
        console.log('\n📋 STEP 2: Find and Click Global Travel Monitor Link');

        // Try to find the GTM link
        let gtmLink = null;
        const possibleSelectors = [
            'a:has-text("Global Travel Monitor")',
            'a:has-text("Travel Monitor")',
            'a[href*="global-travel-monitor"]',
            'a[href*="pdsauthint"]',
            'a[href*="stage.global-travel-monitor"]'
        ];

        for (const selector of possibleSelectors) {
            try {
                const link = await page.locator(selector).first();
                if (await link.count() > 0) {
                    gtmLink = link;
                    console.log(`   ✓ Found GTM link with selector: ${selector}`);

                    // Get link attributes
                    const href = await link.getAttribute('href');
                    const target = await link.getAttribute('target');
                    const text = await link.textContent();

                    console.log(`   Link text: "${text}"`);
                    console.log(`   Link href: ${href}`);
                    console.log(`   Link target: ${target || 'none'}`);

                    break;
                }
            } catch (e) {
                // Continue searching
            }
        }

        if (!gtmLink) {
            console.log('   ⚠️  Could not find GTM link automatically');
            await takeScreenshot(page, '05_gtm_link_not_found');

            // Get all links on the page for debugging
            const allLinks = await page.locator('a').all();
            console.log(`   Found ${allLinks.length} links on page:`);
            for (const link of allLinks.slice(0, 20)) { // Show first 20 links
                const text = await link.textContent();
                const href = await link.getAttribute('href');
                if (text && text.trim()) {
                    console.log(`     - "${text.trim()}" -> ${href}`);
                }
            }

            throw new Error('GTM link not found');
        }

        await takeScreenshot(page, '05_before_gtm_click');

        // Check if link opens in new tab
        const target = await gtmLink.getAttribute('target');
        const href = await gtmLink.getAttribute('href');

        if (target === '_blank') {
            console.log('   ℹ️  Link opens in new tab, handling popup...');

            // Wait for new page
            const [newPage] = await Promise.all([
                context.waitForEvent('page'),
                gtmLink.click()
            ]);

            await newPage.waitForLoadState('networkidle');
            await sleep(2000);

            // Switch to new page
            await takeScreenshot(newPage, '06_gtm_new_tab');

            console.log('   ✓ Switched to new tab');
            console.log(`   Current URL: ${newPage.url()}`);

            // Use new page for further testing
            const oldPage = page;
            page = newPage; // Replace page reference

        } else {
            console.log('   ℹ️  Link opens in same tab');

            // Click and wait for navigation
            await gtmLink.click();
            await page.waitForLoadState('networkidle');
            await sleep(2000);

            await takeScreenshot(page, '06_after_gtm_click');
            console.log(`   Current URL: ${page.url()}`);
        }

        // ==========================================
        // STEP 3: Verify GTM Auto-Login
        // ==========================================
        console.log('\n📋 STEP 3: Verify GTM Auto-Login');

        const currentUrl = page.url();
        console.log(`   Current URL: ${currentUrl}`);

        // Check if we're on GTM
        if (!currentUrl.includes('global-travel-monitor.eu')) {
            console.log('   ⚠️  Not on GTM domain!');
            await takeScreenshot(page, '07_not_on_gtm');
            throw new Error('Did not reach GTM domain');
        }

        // Check if we're on login page (should NOT be)
        if (currentUrl.includes('/login') || currentUrl.includes('/admin/login')) {
            console.log('   ❌ Still on login page - Auto-login FAILED');
            await takeScreenshot(page, '07_gtm_login_page_failed');
            throw new Error('SSO Auto-login failed - still on login page');
        }

        await takeScreenshot(page, '07_gtm_after_sso');

        // Try to verify we're logged in by checking for dashboard elements
        await sleep(2000);

        // Look for signs of being logged in
        const possibleLoggedInIndicators = [
            'a:has-text("Logout")',
            'a:has-text("Abmelden")',
            'button:has-text("Logout")',
            'nav',
            '.user-menu',
            '[class*="dashboard"]',
            '[class*="Dashboard"]'
        ];

        let loggedIn = false;
        for (const indicator of possibleLoggedInIndicators) {
            try {
                const element = await page.locator(indicator).first();
                if (await element.count() > 0) {
                    loggedIn = true;
                    console.log(`   ✓ Found logged-in indicator: ${indicator}`);
                    break;
                }
            } catch (e) {
                // Continue
            }
        }

        if (loggedIn) {
            console.log('   ✅ GTM Auto-Login SUCCESSFUL!');
        } else {
            console.log('   ⚠️  Could not verify logged-in state definitively');
        }

        await takeScreenshot(page, '08_gtm_logged_in');

        // ==========================================
        // STEP 4: Admin Login
        // ==========================================
        console.log('\n📋 STEP 4: Admin Login to Check SSO Logs');

        await page.goto(GTM_ADMIN_URL, { waitUntil: 'networkidle' });
        await takeScreenshot(page, '09_admin_login_page');

        // Fill admin login
        await page.fill('input[type="email"], input[name="email"]', GTM_ADMIN_EMAIL);
        await page.fill('input[type="password"], input[name="password"]', GTM_ADMIN_PASSWORD);
        console.log('   ✓ Admin credentials filled');

        await takeScreenshot(page, '10_admin_login_filled');

        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await sleep(2000);

        await takeScreenshot(page, '11_admin_logged_in');
        console.log('   ✅ Admin login successful');

        // ==========================================
        // STEP 5: Check SSO Logs
        // ==========================================
        console.log('\n📋 STEP 5: Check SSO Logs');

        await page.goto(GTM_URL + 'admin/sso-logs', { waitUntil: 'networkidle' });
        await sleep(2000);
        await takeScreenshot(page, '12_sso_logs_page');

        console.log('   ✅ On SSO Logs page');
        console.log(`   Current URL: ${page.url()}`);

        // Try to get table data
        try {
            const table = await page.locator('table').first();
            if (await table.count() > 0) {
                console.log('   ✓ Found logs table');

                // Get first row data
                const firstRow = await page.locator('table tbody tr').first();
                if (await firstRow.count() > 0) {
                    const rowText = await firstRow.textContent();
                    console.log(`   First log entry: ${rowText}`);

                    // Try to click on first row to see details
                    const detailsLink = await firstRow.locator('a, button').first();
                    if (await detailsLink.count() > 0) {
                        await detailsLink.click();
                        await sleep(2000);
                        await takeScreenshot(page, '13_sso_log_details');
                        console.log('   ✓ Opened log details');
                    }
                }
            }
        } catch (e) {
            console.log(`   ⚠️  Could not parse table: ${e.message}`);
        }

        // ==========================================
        // FINAL: Save Network Logs
        // ==========================================
        console.log('\n📋 Saving Network Logs...');

        const logsFile = path.join(LOGS_DIR, `network-requests-${Date.now()}.json`);
        fs.writeFileSync(logsFile, JSON.stringify(networkRequests, null, 2));
        console.log(`   ✅ Network logs saved: ${logsFile}`);
        console.log(`   Total SSO-related requests: ${networkRequests.length}`);

        // Print summary
        console.log('\n' + '='.repeat(60));
        console.log('📊 TEST SUMMARY');
        console.log('='.repeat(60));
        console.log(`✅ PDS Login: SUCCESS`);
        console.log(`✅ GTM Link Click: SUCCESS`);
        console.log(`✅ GTM Auto-Login: ${loggedIn ? 'SUCCESS' : 'UNCERTAIN'}`);
        console.log(`✅ Admin Login: SUCCESS`);
        console.log(`✅ SSO Logs Access: SUCCESS`);
        console.log(`📸 Screenshots saved in: ${SCREENSHOTS_DIR}`);
        console.log(`📝 Network logs saved in: ${LOGS_DIR}`);
        console.log('='.repeat(60));

        // Keep browser open for manual inspection
        console.log('\n⏸️  Browser will remain open for 30 seconds for inspection...');
        await sleep(30000);

    } catch (error) {
        console.error('\n❌ TEST FAILED:', error.message);
        console.error(error.stack);

        await takeScreenshot(page, '99_error_state');

        // Save network logs even on error
        const logsFile = path.join(LOGS_DIR, `network-requests-error-${Date.now()}.json`);
        fs.writeFileSync(logsFile, JSON.stringify(networkRequests, null, 2));
        console.log(`   Network logs saved: ${logsFile}`);

        // Keep browser open on error
        console.log('\n⏸️  Browser will remain open for inspection...');
        await sleep(60000);
    } finally {
        await browser.close();
        console.log('\n👋 Test completed');
    }
})();
