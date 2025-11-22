import { test, expect } from '@playwright/test';
import fs from 'fs';

test.describe('SSO Flow Test - test11 to stage.global-travel-monitor.eu', () => {

  test('Complete SSO authentication flow', async ({ page, context }) => {
    // Enable verbose logging
    const requests = [];
    const responses = [];
    const cookies = [];

    // Listen to all requests
    page.on('request', request => {
      requests.push({
        url: request.url(),
        method: request.method(),
        headers: request.headers(),
        postData: request.postData()
      });
      console.log(`→ REQUEST: ${request.method()} ${request.url()}`);
    });

    // Listen to all responses
    page.on('response', async response => {
      const responseData = {
        url: response.url(),
        status: response.status(),
        headers: response.headers()
      };

      // Try to get body for important endpoints
      if (response.url().includes('pdsauthint') || response.url().includes('login')) {
        try {
          responseData.body = await response.text();
        } catch (e) {
          responseData.body = '[Unable to read body]';
        }
      }

      responses.push(responseData);
      console.log(`← RESPONSE: ${response.status()} ${response.url()}`);

      // Log redirects
      if (response.status() >= 300 && response.status() < 400) {
        console.log(`  ↪ REDIRECT to: ${response.headers()['location']}`);
      }
    });

    // Step 1: Navigate to test11 login page
    console.log('\n=== STEP 1: Navigate to Login Page ===');
    await page.goto('https://test11-dot-web1-dot-dataservice-development.ey.r.appspot.com/de/login');
    await page.waitForLoadState('networkidle');

    // Take screenshot of login page
    await page.screenshot({ path: 'screenshots/01-login-page.png', fullPage: true });

    // Check if already logged in (redirect to dashboard)
    const currentUrl = page.url();
    console.log(`Current URL: ${currentUrl}`);

    if (!currentUrl.includes('/login')) {
      console.log('Already logged in, proceeding to GTM link...');
    } else {
      console.log('\n=== Login form found - Please provide credentials ===');
      console.log('Looking for email/username and password fields...');

      // Wait for login form
      await page.waitForSelector('input[type="email"], input[name="email"], input[type="text"]', { timeout: 5000 });
      await page.screenshot({ path: 'screenshots/02-login-form.png', fullPage: true });

      // TODO: Enter credentials here
      // For now, let's see what form fields exist
      const emailField = await page.locator('input[type="email"], input[name="email"]').first();
      const passwordField = await page.locator('input[type="password"], input[name="password"]').first();

      console.log('Email field exists:', await emailField.count() > 0);
      console.log('Password field exists:', await passwordField.count() > 0);

      // Fill in credentials
      console.log('Filling in credentials...');
      await emailField.fill('p1@dhe.de');
      await passwordField.fill('oHUxhQ8Em3');

      await page.screenshot({ path: 'screenshots/02b-credentials-filled.png', fullPage: true });

      // Find and click submit button
      console.log('Looking for submit button...');
      const submitButton = await page.locator('button[type="submit"], input[type="submit"]').first();

      await submitButton.click();
      console.log('Submit button clicked, waiting for navigation...');

      // Wait for navigation after login
      try {
        await page.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 });
        console.log('Navigation completed after login');
      } catch (e) {
        console.log('Navigation timeout or redirect occurred');
      }
    }

    // Step 2: Check cookies after login
    console.log('\n=== STEP 2: Check Cookies After Login ===');
    const allCookies = await context.cookies();
    console.log('Cookies:', JSON.stringify(allCookies, null, 2));

    const sessionCookie = allCookies.find(c =>
      c.name.includes('session') ||
      c.name.includes('laravel') ||
      c.name.toLowerCase().includes('xsrf')
    );

    if (sessionCookie) {
      console.log('✓ Session cookie found:', sessionCookie.name);
    } else {
      console.log('✗ No session cookie found!');
    }

    await page.screenshot({ path: 'screenshots/03-after-login.png', fullPage: true });

    // Step 3: Look for "Global Travel Monitor" link
    console.log('\n=== STEP 3: Looking for Global Travel Monitor Link ===');

    // Wait a bit for page to fully load
    await page.waitForTimeout(2000);

    // Try different selectors for the GTM link
    const possibleSelectors = [
      'a[href*="global-travel-monitor"]',
      'a:has-text("Global Travel Monitor")',
      'a:has-text("Travel Monitor")',
      'form[action*="pdsauthint/redirect"]',
      '#sso-gtm-form'
    ];

    let gtmElement = null;
    let gtmSelector = null;

    for (const selector of possibleSelectors) {
      try {
        const element = page.locator(selector).first();
        if (await element.count() > 0) {
          gtmElement = element;
          gtmSelector = selector;
          console.log(`✓ Found GTM element with selector: ${selector}`);
          break;
        }
      } catch (e) {
        continue;
      }
    }

    if (!gtmElement) {
      console.log('✗ Could not find GTM link!');
      console.log('Page HTML:', await page.content());
      await page.screenshot({ path: 'screenshots/04-gtm-not-found.png', fullPage: true });
      return;
    }

    // Highlight the element
    await gtmElement.evaluate(el => {
      el.style.border = '3px solid red';
      el.style.backgroundColor = 'yellow';
    });

    await page.screenshot({ path: 'screenshots/05-gtm-link-found.png', fullPage: true });

    // Step 4: Click GTM link and track the flow
    console.log('\n=== STEP 4: Clicking Global Travel Monitor Link ===');

    // Clear previous requests/responses
    requests.length = 0;
    responses.length = 0;

    // If it's a form, submit it
    if (gtmSelector === '#sso-gtm-form' || gtmSelector.includes('form')) {
      await page.evaluate(() => {
        const form = document.querySelector('#sso-gtm-form') || document.querySelector('form[action*="pdsauthint/redirect"]');
        if (form) form.submit();
      });
    } else {
      await gtmElement.click();
    }

    // Wait for navigation with longer timeout
    console.log('Waiting for navigation...');
    try {
      await page.waitForNavigation({ timeout: 30000 });
    } catch (e) {
      console.log('Navigation timeout or no navigation occurred');
    }

    await page.waitForTimeout(3000);

    // Step 5: Analyze the result
    console.log('\n=== STEP 5: Analysis ===');
    const finalUrl = page.url();
    console.log(`Final URL: ${finalUrl}`);

    await page.screenshot({ path: 'screenshots/06-final-page.png', fullPage: true });

    // Check if we reached Service 2
    if (finalUrl.includes('stage.global-travel-monitor.eu')) {
      console.log('✓ SUCCESS: Reached Service 2!');

      // Check if we're logged in (not on login page)
      if (finalUrl.includes('/login')) {
        console.log('✗ But still on login page - SSO failed');
      } else {
        console.log('✓ Logged into Service 2 successfully!');
      }
    } else if (finalUrl.includes('test11-dot-web1')) {
      console.log('✗ FAILED: Redirected back to Service 1');

      if (finalUrl.includes('/login')) {
        console.log('  → Redirected to login page (authentication issue)');
      }
    }

    // Log all SSO-related requests
    console.log('\n=== SSO-Related Requests ===');
    const ssoRequests = requests.filter(r =>
      r.url.includes('pdsauthint') ||
      r.url.includes('exchange') ||
      r.url.includes('global-travel-monitor')
    );

    ssoRequests.forEach(req => {
      console.log(`${req.method} ${req.url}`);
      if (req.postData) {
        console.log(`  POST Data: ${req.postData.substring(0, 200)}`);
      }
    });

    // Log all SSO-related responses
    console.log('\n=== SSO-Related Responses ===');
    const ssoResponses = responses.filter(r =>
      r.url.includes('pdsauthint') ||
      r.url.includes('exchange') ||
      r.url.includes('global-travel-monitor')
    );

    ssoResponses.forEach(res => {
      console.log(`${res.status} ${res.url}`);
      if (res.headers['location']) {
        console.log(`  Location: ${res.headers['location']}`);
      }
      if (res.body) {
        console.log(`  Body: ${res.body.substring(0, 500)}`);
      }
    });

    // Save detailed logs
    if (!fs.existsSync('screenshots')) {
      fs.mkdirSync('screenshots');
    }

    fs.writeFileSync('screenshots/sso-test-log.json', JSON.stringify({
      finalUrl,
      cookies: allCookies,
      requests: ssoRequests,
      responses: ssoResponses
    }, null, 2));

    console.log('\n✓ Detailed log saved to screenshots/sso-test-log.json');
  });
});
