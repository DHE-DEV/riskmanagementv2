import { test, expect } from '@playwright/test';
import { exec } from 'child_process';
import { promisify } from 'util';
const execAsync = promisify(exec);

test('test WebSocket connection for real-time updates', async ({ page }) => {
    // Collect all console messages
    const consoleMessages = [];
    const wsConnections = [];
    const jsErrors = [];

    page.on('console', msg => {
        consoleMessages.push({ type: msg.type(), text: msg.text() });
        console.log(`[CONSOLE ${msg.type()}]:`, msg.text());
    });

    page.on('pageerror', error => {
        jsErrors.push(error.message);
        console.log('[PAGE ERROR]:', error.message);
    });

    // Monitor WebSocket connections
    page.on('websocket', ws => {
        console.log('[WEBSOCKET] Connection opened:', ws.url());
        wsConnections.push({ url: ws.url(), status: 'opened' });

        ws.on('framesent', frame => {
            console.log('[WEBSOCKET SENT]:', frame.payload);
        });

        ws.on('framereceived', frame => {
            console.log('[WEBSOCKET RECEIVED]:', frame.payload);
        });

        ws.on('close', () => {
            console.log('[WEBSOCKET] Connection closed:', ws.url());
        });
    });

    // Monitor network requests for broadcast auth
    page.on('requestfinished', async request => {
        if (request.url().includes('broadcasting/auth')) {
            const response = await request.response();
            console.log('[BROADCAST AUTH]:', request.url(), 'Status:', response?.status());
            if (response) {
                try {
                    const body = await response.text();
                    console.log('[BROADCAST AUTH RESPONSE]:', body.substring(0, 200));
                } catch (e) {
                    console.log('[BROADCAST AUTH RESPONSE]: Could not read body');
                }
            }
        }
    });

    // Go to customer login page
    console.log('--- Logging in ---');
    await page.goto('http://riskmanagementv2.local/customer/login');

    // Fill login form
    await page.fill('input[name="email"]', 'kunde@example.com');
    await page.fill('input[name="password"]', 'Test123!');

    // Click login
    await page.click('button[type="submit"]');

    // Wait for navigation
    await page.waitForTimeout(2000);
    console.log('After login URL:', page.url());

    // Navigate to my-travelers
    console.log('--- Going to my-travelers ---');
    await page.goto('http://riskmanagementv2.local/my-travelers');

    // Wait for page to fully load and WebSocket to connect
    await page.waitForTimeout(3000);

    // Check what Echo looks like
    const echoDebug = await page.evaluate(() => {
        return {
            echoExists: typeof window.Echo !== 'undefined',
            echoType: typeof window.Echo,
            echoKeys: window.Echo ? Object.keys(window.Echo) : [],
            echoHasPrivate: window.Echo && typeof window.Echo.private === 'function',
            pusherExists: typeof window.Pusher !== 'undefined',
            EchoConstructorExists: typeof window.Echo === 'function' || (typeof Echo !== 'undefined' && typeof Echo === 'function'),
        };
    });
    console.log('\n[ECHO DEBUG]:', JSON.stringify(echoDebug, null, 2));

    // Now trigger a broadcast from the server
    console.log('\n--- Triggering test broadcast ---');
    try {
        const { stdout, stderr } = await execAsync(
            'php artisan tinker --execute="broadcast(new App\\\\Events\\\\Folder\\\\FolderImported(App\\\\Models\\\\Folder\\\\Folder::where(\'customer_id\', 15)->first(), false));"',
            { cwd: '/home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2' }
        );
        console.log('Broadcast command output:', stdout);
        if (stderr) console.log('Broadcast command stderr:', stderr);
    } catch (e) {
        console.log('Broadcast error:', e.message);
    }

    // Wait for the broadcast to arrive
    console.log('Waiting for broadcast to arrive...');
    await page.waitForTimeout(5000);

    // Take screenshot
    await page.screenshot({ path: 'test-results/websocket-test.png', fullPage: true });

    // Summary
    console.log('\n=== SUMMARY ===');
    console.log('WebSocket connections:', wsConnections.length);
    wsConnections.forEach(ws => console.log('  -', ws.url, ws.status));

    console.log('\nConsole messages with "channel" or "Echo" or "WebSocket":');
    consoleMessages
        .filter(m => m.text.toLowerCase().includes('channel') ||
                     m.text.toLowerCase().includes('echo') ||
                     m.text.toLowerCase().includes('websocket') ||
                     m.text.toLowerCase().includes('subscribing'))
        .forEach(m => console.log(`  [${m.type}]:`, m.text));

    console.log('\nJavaScript errors:', jsErrors.length);
    jsErrors.forEach(e => console.log('  -', e));

    // Check if Echo subscription message appears
    const hasSubscription = consoleMessages.some(m =>
        m.text.includes('Subscribing to channel')
    );
    console.log('\nHas "Subscribing to channel" message:', hasSubscription);

    // Check for WebSocket connection to Reverb
    const hasReverbConnection = wsConnections.some(ws =>
        ws.url.includes('8080') || ws.url.includes('reverb')
    );
    console.log('Has WebSocket connection to Reverb:', hasReverbConnection);
});
