<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Travel Requirements Service – Menüpunkte
    |--------------------------------------------------------------------------
    |
    | Steuerung der Sichtbarkeit einzelner Menüpunkte auf
    | /customer/settings?section=travel-requirements
    | Jeder Eintrag wird per ENV-Variable (true/false) ein- oder ausgeblendet.
    | Default: true (sichtbar).
    |
    */
    'menu_items' => [
        'content.country'                                  => env('TRS_SHOW_CONTENT_COUNTRY', true),
        'content.cruise'                                   => env('TRS_SHOW_CONTENT_CRUISE', true),
        'content.individual'                               => env('TRS_SHOW_CONTENT_INDIVIDUAL', true),
        'content.tour_operator'                            => env('TRS_SHOW_CONTENT_TOUR_OPERATOR', true),
        'customer.send_emails'                             => env('TRS_SHOW_SEND_EMAILS', true),
        'customer.travel_detail_link.create'               => env('TRS_SHOW_TDL_CREATE', true),
        'customer.travel_detail_link.manage'               => env('TRS_SHOW_TDL_MANAGE', true),
        'customer.travel_detail_link.advert.manage'        => env('TRS_SHOW_TDL_ADVERT', true),
        'customer.travel_detail_link.email_subscriptions'  => env('TRS_SHOW_TDL_EMAIL_SUBSCRIPTIONS', true),
        'customer.travel_detail_link.inspiration.manage'   => env('TRS_SHOW_TDL_INSPIRATION', true),
        'customer.travel_detail_link.media.manage'         => env('TRS_SHOW_TDL_MEDIA', true),
        'subscription'                                     => env('TRS_SHOW_SUBSCRIPTION', true),
    ],
];
