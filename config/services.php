<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'openweathermap' => [
        'api_key' => env('OPENWEATHERMAP_API_KEY', 'demo_key'),
        'base_url' => env('OPENWEATHERMAP_BASE_URL', 'https://api.openweathermap.org/data/2.5'),
    ],

    // DeepL Translation API (Pro) – used to translate Event titles/descriptions
    // DeepL Übersetzungs-API (Pro) – übersetzt Event-Titel/-Beschreibungen
    // Nutzt den vorhandenen DEEPL_KEY (Fallback: DEEPL_API_KEY).
    'deepl' => [
        'api_key' => env('DEEPL_KEY', env('DEEPL_API_KEY')),
        'api_url' => env('DEEPL_API_URL', 'https://api.deepl.com/v2/translate'),
    ],

    // Font Awesome Kit
    'fontawesome' => [
        // Erwartet eine Kit-ID oder Lizenznummer in der .env unter FONT_AWESOME
        'kit' => env('FONT_AWESOME'),
    ],

    'passolution' => [
        'api_url' => env('PASSOLUTION_API_URL', 'https://api.passolution.eu/api/v2'),
        // Basis-URL fuer die __internal-Service-Token-Endpunkte. Die liegen auf dem
        // internen Service (api-internal), nicht auf der oeffentlichen api.passolution.eu.
        // Fallback: api_url.
        'internal_api_url' => env('PASSOLUTION_INTERNAL_API_URL', 'https://api-internal-dot-pds-prod-dataservice.appspot.com/api/v2'),
        // Basis-URL fuer den token-basierten /account/subscription-Abruf (liefert
        // type + features des Token-Inhabers). Wird mit dem Kunden-Token aufgerufen.
        'subscription_api_url' => env('PASSOLUTION_SUBSCRIPTION_API_URL', 'https://api-int.passolution.de/api/v2'),
        'api_key' => env('PASSOLUTION_API_KEY'),
        'api_secret' => env('PASSOLUTION_API_SECRET'),
        // Account-Personal-Access-Token (pds-api) für server-seitige __internal-Aufrufe,
        // z. B. Stammdaten-Lookup beim Auto-Anlegen neuer Kunden (account/info).
        'internal_token' => env('PASSOLUTION_INTERNAL_TOKEN'),
        'client_id' => env('PASSOLUTION_OAUTH_CLIENT_ID'),
        'client_secret' => env('PASSOLUTION_OAUTH_CLIENT_SECRET'),
        // OAuth URLs - configurable for different environments
        'oauth_base_url' => env('PASSOLUTION_OAUTH_BASE_URL', 'https://web.passolution.eu'),
        'oauth_authorize_url' => env('PASSOLUTION_OAUTH_AUTHORIZE_URL', 'https://web.passolution.eu/oauth/authorize'),
        'oauth_token_url' => env('PASSOLUTION_OAUTH_TOKEN_URL', 'https://web.passolution.eu/oauth/token'),
        'oauth_refresh_url' => env('PASSOLUTION_OAUTH_REFRESH_URL', 'https://web.passolution.eu/oauth/token/refresh'),
        // Scheduled sync settings
        'sync_enabled' => env('PASSOLUTION_SYNC_ENABLED', true),
        'travel_details_link' => env('PASSOLUTION_TRAVEL_DETAILS_LINK', 'https://travel-details.eu'),
        'web_url' => env('PASSOLUTION_WEB_URL', 'https://web.passolution.de'),
        'help_center_url' => env('PASSOLUTION_HELP_CENTER_URL', 'https://web.passolution.de/zoho/desk/saml/login'),
        'iframe_sso_secret' => env('IFRAME_SSO_SECRET'),
        // Optionale Query-Parameter für den eingebetteten iframe (z. B. UI-Hide-Flags
        // der pds-Homepage). Beispiel: "menu-hide=gtm,hc,is&ui-hide=is"
        'iframe_ui_params' => env('PASSOLUTION_IFRAME_UI_PARAMS'),
        // Locale-Segment fuer die iframe-URL. Die Homepage leitet "/" per 302 auf
        // "/<locale>" um und verwirft dabei die Query (menu-hide/ui-hide). Daher die
        // Locale direkt in die URL legen, damit ohne Redirect gerendert wird und die
        // UI-Parameter greifen. Leer = App-Locale.
        'iframe_locale' => env('PASSOLUTION_IFRAME_LOCALE'),
    ],

    // PDS API - For SSO token-based API access
    // PDS API - Für SSO-Token-basierten API-Zugriff
    // Uses same base URL as passolution service (PASSOLUTION_API_URL)
    'pds_api' => [
        'base_url' => env('PASSOLUTION_API_URL', 'https://api.passolution.eu/api/v2'),
        'timeout' => env('PDS_API_TIMEOUT', 30),
        'detailed_logging' => env('PDS_API_DETAILED_LOGGING', false),
    ],

    // Folder Import Settings
    'folder_import' => [
        'detailed_logging' => env('FOLDER_IMPORT_DETAILED_LOGGING', false),
    ],

    'openai' => [
        'key' => env('RISK_CHARGPT_KEY'),
    ],

    // Social Authentication Providers for Customers
    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/customer/auth/facebook/callback',
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/customer/auth/google/callback',
    ],

    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/customer/auth/linkedin/callback',
    ],

    'twitter' => [
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/customer/auth/twitter/callback',
    ],

    'keycloak' => [
        'client_id' => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'redirect' => env('OIDC_REDIRECT_URI', env('APP_URL').'/auth/callback'),
        'base_url' => env('OIDC_BASE_URL', 'https://auth.passolution.de'),
        'realms' => env('OIDC_REALM', 'passolution'),
        // Service-Account-Client für die Admin-REST-API (client_credentials grant).
        // Benötigt realm-management Rollen manage-users + view-users auf dem Realm.
        'admin_client_id' => env('KEYCLOAK_ADMIN_CLIENT_ID'),
        'admin_client_secret' => env('KEYCLOAK_ADMIN_CLIENT_SECRET'),
    ],

    // SSO-Treiber-Auswahl: Welcher Socialite-Treiber den Login-Flow abwickelt.
    // 'keycloak'        = Passolution Keycloak (OIDC)              [Standard]
    // 'laravelpassport' = Passolution Laravel Passport (OAuth2)
    // Umschaltbar per .env (SSO_DRIVER). Damit ist Keycloak deaktivierbar und
    // der Laravel-Passport-Login aktivierbar, ohne Code-Aenderung.
    'sso' => [
        'driver' => env('SSO_DRIVER', 'keycloak'),
        // true  = SSO ist der einzige Login-Weg: /customer/login leitet direkt
        //         zum SSO-Provider weiter (keine eigene Login-Seite).
        // false = Login-Seite mit E-Mail/Passwort + SSO-Button anzeigen [Standard].
        // Default bewusst false, damit ein Deploy das Live-Login nicht aendert –
        // Direkt-Weiterleitung wird bei Bedarf per .env (SSO_FORCE_REDIRECT=true) aktiviert.
        'force_redirect' => env('SSO_FORCE_REDIRECT', false),
        // 'prompt=login' an den Laravel-Passport-Authorize-Endpunkt anhaengen, um
        // eine frische Anmeldung zu erzwingen (nur wirksam, wenn der Auth-Server den
        // Parameter unterstuetzt). Keycloak erzwingt das ohnehin separat ueber PKCE.
        'prompt_login' => env('SSO_PROMPT_LOGIN', true),
        // Optionaler Logout-/End-Session-Endpunkt des Passport-Auth-Servers. Wenn
        // gesetzt, wird nach dem lokalen Logout dorthin weitergeleitet (beendet die
        // Provider-Session), mit angehaengtem ?redirect_uri= auf das Post-Logout-Ziel.
        'logout_url' => env('SSO_LOGOUT_URL'),
        // Post-Logout-Ziel fuer Nicht-Keycloak-SSO (Standard: Login-Seite).
        'logout_redirect' => env('SSO_LOGOUT_REDIRECT_URI'),
    ],

    // Laravel Passport (Socialite) – alternativer SSO-Provider zu Keycloak.
    // Siehe https://socialiteproviders.com/Laravel-Passport/
    // Endpunkte relativ zu host: /oauth/authorize, /oauth/token, /api/user.
    'laravelpassport' => [
        'client_id' => env('SSO_CLIENT_ID'),
        'client_secret' => env('SSO_CLIENT_SECRET'),
        'redirect' => env('SSO_REDIRECT_URI', env('APP_URL').'/auth/callback'),
        'host' => env('SSO_HOST', 'https://auth.homepage-test.passolution.de'),
    ],

    // Plugin Demo Key for documentation page
    'plugin' => [
        'demo_key' => env('PLUGIN_DEMO_KEY'),
    ],

    // WorkFlex Visa API
    'workflex' => [
        'client_id' => env('WORKFLEX_CLIENT_ID'),
        'client_secret' => env('WORKFLEX_CLIENT_SECRET'),
        'base_url' => env('WORKFLEX_BASE_URL', 'https://visa-api.getworkflex.com/visa-api/v1'),
        'auth_url' => env('WORKFLEX_AUTH_URL', 'https://visa-api.getworkflex.com/visa-api/v1/oauth2/token'),
    ],

    // VisumPoint ComplianceCheck API
    'visumpoint' => [
        'user_name' => env('VISUMPOINT_USERNAME'),
        'access_token' => env('VISUMPOINT_ACCESS_TOKEN'),
        'base_url' => env('VISUMPOINT_BASE_URL', 'https://www.visumpoint.de/REST/ComplianceCheck/API.php'),
    ],

];
