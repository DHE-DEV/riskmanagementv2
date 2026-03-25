<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GTM Notification Queue Interval (Minuten)
    |--------------------------------------------------------------------------
    */
    'gtm_interval' => (int) env('GTM_NOTIFICATION_INTERVAL', 5),

    /*
    |--------------------------------------------------------------------------
    | Travel Alert Notification Queue Interval (Minuten)
    |--------------------------------------------------------------------------
    */
    'travel_alert_interval' => (int) env('TRAVEL_ALERT_NOTIFICATION_INTERVAL', 5),

    /*
    |--------------------------------------------------------------------------
    | Lookback-Fenster (Stunden)
    |--------------------------------------------------------------------------
    | Wie weit zurück nach unverarbeiteten Events gesucht wird.
    */
    'lookback_hours' => (int) env('NOTIFICATION_LOOKBACK_HOURS', 24),
];
