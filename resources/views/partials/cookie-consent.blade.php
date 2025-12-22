{{-- Cookie Consent Banner --}}
<div x-data="cookieConsent()" x-show="showBanner" x-cloak
     class="fixed bottom-0 left-0 right-0 z-[9999] p-4 shadow-2xl"
     style="background-color: rgb(211, 227, 96);"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-y-full"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform translate-y-0"
     x-transition:leave-end="opacity-0 transform translate-y-full">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex-1">
                <h3 class="text-lg font-semibold mb-1 text-gray-900">Cookie-Einstellungen</h3>
                <p class="text-sm text-gray-800">
                    Wir verwenden Cookies und Google Analytics, um unsere Website zu verbessern und die Nutzung zu analysieren.
                    Sie können wählen, ob Sie dies akzeptieren möchten.
                    <a href="https://www.passolution.de/datenschutz/" target="_blank" class="font-medium text-gray-900 underline hover:text-gray-700">Datenschutzerklärung</a>
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 shrink-0">
                <button @click="acceptAll()"
                        class="px-6 py-2 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-lg transition-colors">
                    Alle akzeptieren
                </button>
                <button @click="acceptEssential()"
                        class="px-6 py-2 bg-white hover:bg-gray-100 text-gray-900 font-medium rounded-lg transition-colors border border-gray-300">
                    Nur essenzielle
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function cookieConsent() {
    return {
        showBanner: false,

        init() {
            // Check if consent was already given
            const consent = localStorage.getItem('cookie_consent');
            if (!consent) {
                this.showBanner = true;
            } else if (consent === 'all') {
                this.loadGTM();
            }
        },

        acceptAll() {
            localStorage.setItem('cookie_consent', 'all');
            localStorage.setItem('cookie_consent_date', new Date().toISOString());
            this.showBanner = false;
            this.loadGTM();
        },

        acceptEssential() {
            localStorage.setItem('cookie_consent', 'essential');
            localStorage.setItem('cookie_consent_date', new Date().toISOString());
            this.showBanner = false;
            // Don't load GTM for essential only
        },

        loadGTM() {
            if (window.gtmLoaded) return;
            window.gtmLoaded = true;

            // Load Google Tag Manager
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','GTM-T7R2SWKD');
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
