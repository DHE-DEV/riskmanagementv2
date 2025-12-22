<?php

namespace App\Http\Controllers\Plugin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class WidgetController extends Controller
{
    public function __invoke(): Response
    {
        $appUrl = config('app.url');

        $javascript = <<<JS
(function() {
    'use strict';

    // Find the script tag with data-key
    var scripts = document.querySelectorAll('script[data-key]');
    var script = scripts[scripts.length - 1];

    if (!script) {
        console.warn('[GTM Widget] No data-key attribute found');
        return;
    }

    var key = script.getAttribute('data-key');
    if (!key) {
        console.warn('[GTM Widget] Empty data-key attribute');
        return;
    }

    // Get current domain and path
    var domain = window.location.hostname;
    var path = window.location.pathname;

    // Perform handshake
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '{$appUrl}/api/plugin/handshake', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('Accept', 'application/json');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            try {
                var response = JSON.parse(xhr.responseText);

                if (xhr.status === 200 && response.allowed) {
                    console.log('[GTM Widget] Widget enabled');
                    // Initialize widget functionality here
                    window.GTMWidget = {
                        enabled: true,
                        config: response.config || {},
                        trackEvent: function(eventType, meta) {
                            var eventXhr = new XMLHttpRequest();
                            eventXhr.open('POST', '{$appUrl}/api/plugin/handshake', true);
                            eventXhr.setRequestHeader('Content-Type', 'application/json');
                            eventXhr.setRequestHeader('Accept', 'application/json');
                            eventXhr.send(JSON.stringify({
                                key: key,
                                domain: domain,
                                path: path,
                                event_type: eventType,
                                meta: meta || {}
                            }));
                        }
                    };
                } else {
                    console.warn('[GTM Widget] Not licensed:', response.error || 'Unknown error');
                    window.GTMWidget = { enabled: false };
                }
            } catch (e) {
                console.warn('[GTM Widget] Invalid response');
                window.GTMWidget = { enabled: false };
            }
        }
    };

    xhr.onerror = function() {
        console.warn('[GTM Widget] Network error');
        window.GTMWidget = { enabled: false };
    };

    xhr.send(JSON.stringify({
        key: key,
        domain: domain,
        path: path,
        event_type: 'page_load'
    }));
})();
JS;

        return response($javascript, 200)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
