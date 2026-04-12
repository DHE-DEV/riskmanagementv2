{{-- Travel Alert Tour --}}
<div x-data="travelAlertTour()" x-show="active || showConfirm" x-cloak>

    {{-- Backdrop with cutout --}}
    <div x-show="active && !showConfirm" style="position: fixed; inset: 0; pointer-events: none; z-index: 200000;">
        <svg width="100%" height="100%" style="position: absolute; inset: 0;">
            <defs>
                <mask id="ta-tour-mask">
                    <rect width="100%" height="100%" fill="white"/>
                    <rect fill="black" rx="8"
                          :x="cutoutBox.x" :y="cutoutBox.y" :width="cutoutBox.w" :height="cutoutBox.h"/>
                </mask>
            </defs>
            <rect width="100%" height="100%" fill="rgba(0,0,0,0.6)" mask="url(#ta-tour-mask)"/>
        </svg>
    </div>

    {{-- Click blocker --}}
    <div x-show="active && !showConfirm" style="position: fixed; inset: 0; z-index: 200001;" @click="nextStep"></div>

    {{-- Tooltip with arrow --}}
    <div x-show="active && !showConfirm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         :style="'position:fixed; z-index:200002; width:416px; pointer-events:auto; top:'+tooltipTop+'px; left:'+tooltipLeft+'px;'">

        <div :style="arrowStyle"></div>

        <div style="background: white; border-radius: 10px; box-shadow: 0 8px 30px rgba(0,0,0,0.25); overflow: hidden;">
            <div style="background: #002742; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-triangle-exclamation" style="color: #CEE741; font-size: 13px;"></i>
                    <span style="color: white; font-size: 12px; font-weight: 600;">Produkteinführung - Travel Alert</span>
                </div>
                <span style="color: rgba(255,255,255,0.5); font-size: 11px;" x-text="(currentStep + 1) + ' / ' + steps.length"></span>
            </div>

            <div style="padding: 16px;">
                <h3 style="margin: 0 0 6px 0; font-size: 15px; font-weight: 700; color: #111827;" x-text="steps[currentStep]?.title"></h3>
                <p style="margin: 0 0 16px 0; font-size: 13px; line-height: 1.6; color: #6b7280;" x-html="steps[currentStep]?.description"></p>

                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; gap: 5px;">
                        <template x-for="(s, i) in steps" :key="i">
                            <div style="width: 7px; height: 7px; border-radius: 50%; transition: all 0.3s;"
                                 :style="i === currentStep ? 'background: #002742; transform: scale(1.2);' : (i < currentStep ? 'background: #CEE741;' : 'background: #d1d5db;')"></div>
                        </template>
                    </div>
                    <div style="display: flex; gap: 6px;">
                        <button @click.stop="requestFinish()"
                                style="padding: 6px 12px; font-size: 12px; color: #6b7280; background: none; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; font-weight: 500;">
                            Überspringen
                        </button>
                        <button @click.stop="nextStep"
                                style="padding: 6px 14px; font-size: 12px; color: white; background: #002742; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;"
                                x-text="currentStep === steps.length - 1 ? 'Fertig' : 'Weiter'">
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirmation Dialog --}}
    <template x-if="showConfirm">
        <div style="position: fixed; inset: 0; z-index: 200010; display: flex; align-items: center; justify-content: center;">
            <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.5);"></div>
            <div x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 style="position: relative; width: 420px; background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden;">
                <div style="background: #002742; padding: 16px 20px;">
                    <h3 style="margin: 0; color: white; font-size: 15px; font-weight: 700;">Travel Alert Einführung beenden</h3>
                </div>
                <div style="padding: 20px;">
                    <p style="margin: 0 0 16px 0; font-size: 13px; color: #6b7280; line-height: 1.6;">
                        Möchten Sie, dass diese Einführung beim nächsten Besuch erneut angezeigt wird?
                    </p>
                    <label style="display: flex; align-items: flex-start; gap: 10px; padding: 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; cursor: pointer; margin: 0 0 20px 0;">
                        <input type="checkbox" x-model="dontShowAgain"
                               style="margin-top: 2px; width: 16px; height: 16px; accent-color: #002742; flex-shrink: 0;">
                        <span style="font-size: 13px; color: #374151; line-height: 1.5;">
                            Bereits gesehene Einführungen nicht mehr anzeigen
                        </span>
                    </label>
                    <p style="margin: 0 0 20px 0; font-size: 12px; color: #9ca3af; line-height: 1.5;">
                        Sie können die Einführung jederzeit in Ihren <strong>Einstellungen</strong> unter <strong>Mein Profil</strong> erneut starten.
                    </p>
                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button @click="showConfirm = false"
                                style="padding: 8px 16px; font-size: 13px; color: #6b7280; background: none; border: 1px solid #e5e7eb; border-radius: 8px; cursor: pointer; font-weight: 500;">
                            Zurück
                        </button>
                        <button @click="confirmFinish()"
                                style="padding: 8px 20px; font-size: 13px; color: white; background: #002742; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            Beenden
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function travelAlertTour() {
    const shouldShow = {{ auth('customer')->user() && !auth('customer')->user()->has_seen_travel_alert_tour && config('app.customer_product_tours_enabled', true) ? 'true' : 'false' }};
    const platformTourActive = {{ auth('customer')->user() && !auth('customer')->user()->has_seen_platform_tour ? 'true' : 'false' }};

    return {
        active: false,
        pending: shouldShow,
        currentStep: 0,
        cutoutBox: { x: 0, y: 0, w: 0, h: 0 },
        tooltipTop: -1000,
        tooltipLeft: -1000,
        arrowStyle: '',
        showConfirm: false,
        dontShowAgain: false,

        steps: [
            {
                target: '#ta-sidebar',
                title: 'Reisen & Länder',
                description: 'In der Seitenleiste sehen Sie Ihre Reisen und alle Länder im Überblick. Wählen Sie eine Reise oder ein Land aus, um Details und aktuelle Sicherheitsereignisse einzusehen.',
            },
            {
                target: '#ta-tabs',
                title: 'Ansicht wechseln',
                description: '<strong>Reisen</strong> &ndash; Zeigt Ihre geplanten Reisen mit Reiseziel, Zeitraum und Anzahl der Ereignisse.<br><br>'
                    + '<strong>Länder</strong> &ndash; Zeigt alle Länder mit aktuellen Sicherheitsereignissen auf einer Weltkarte, als Kacheln oder in einer Listenansicht.',
            },
            {
                target: '#ta-filter-btn',
                title: 'Filter',
                description: 'Über den Filter-Button öffnen Sie das Filterpanel. Damit können Sie die Ansicht gezielt einschränken.',
            },
            {
                target: '#ta-filter-panel',
                title: 'Filteroptionen',
                description: '<strong>Risikostufe</strong> &ndash; Filtern Sie nach Hoch, Mittel, Niedrig oder Info.<br><br>'
                    + '<strong>Zeitraum</strong> &ndash; Legen Sie fest, für welchen Zeitraum Reisen und Ereignisse angezeigt werden.<br><br>'
                    + '<strong>Labels</strong> &ndash; Filtern Sie nach selbst vergebenen Labels, um bestimmte Reisen schneller zu finden.',
                openFilters: true,
            },
            {
                target: '#ta-content',
                title: 'Hauptansicht',
                description: 'Hier wird die Detailansicht angezeigt. Über die Tabs oben wechseln Sie zwischen <strong>Kacheln</strong>, <strong>Liste</strong>, <strong>Kalender</strong> und <strong>Karte</strong>. Jede Ansicht zeigt die Länder mit ihren Sicherheitsereignissen aus einer anderen Perspektive.',
                forceBelow: true,
                switchToLaender: true,
                useFullContent: true,
            }
        ],

        init() {
            if (!this.pending) return;

            if (platformTourActive) {
                // Wait for platform tour to finish first
                window.addEventListener('platform-tour-finished', () => {
                    setTimeout(() => this.start(), 400);
                });
            } else {
                // Platform tour already seen, start directly
                setTimeout(() => this.start(), 500);
            }
        },

        start() {
            this.active = true;
            this.$nextTick(() => {
                setTimeout(() => this.showStep(), 100);
            });
        },

        showStep() {
            const step = this.steps[this.currentStep];
            if (!step) return;

            const appData = Alpine.$data(document.querySelector('.app-container'));

            // Open/close filter panel
            if (step.openFilters) {
                if (appData) {
                    appData.sidebarTab = 'reisen';
                    appData.showTripFilters = true;
                }
                setTimeout(() => this.measure(), 300);
            }
            // Switch to Länder tab
            else if (step.switchToLaender) {
                if (appData) {
                    appData.showTripFilters = false;
                    appData.sidebarTab = 'laender';
                }
                setTimeout(() => this.measure(), 200);
            } else {
                // Close filters if they were opened by a previous step
                if (appData && appData.showTripFilters && !this.steps[this.currentStep]?.openFilters) {
                    appData.showTripFilters = false;
                }
                setTimeout(() => this.measure(), 50);
            }
        },

        measure() {
            const step = this.steps[this.currentStep];
            const target = document.querySelector(step.target);
            if (!target) return;

            const pad = 6;
            const rect = target.getBoundingClientRect();

            let cx, cy, cw, ch;

            if (step.useFullContent) {
                // Use the sidebar's right edge as the content start
                const sidebar = document.querySelector('#ta-sidebar');
                const sidebarRight = sidebar ? sidebar.getBoundingClientRect().right : rect.left;
                cx = sidebarRight;
                cy = rect.top - pad;
                cw = window.innerWidth - sidebarRight;
                ch = rect.height + pad * 2;
            } else {
                cx = rect.left - pad;
                cy = rect.top - pad;
                cw = rect.width + pad * 2;
                ch = rect.height + pad * 2;
            }

            this.cutoutBox = { x: cx, y: cy, w: cw, h: ch };

            const vw = window.innerWidth;
            const vh = window.innerHeight;
            const tw = 416;
            const gap = 14;

            const freeRight  = vw - (cx + cw);
            const freeBottom = vh - (cy + ch);
            const freeLeft   = cx;

            let side;
            if (step.forceBelow) {
                side = 'bottom';
            } else if (freeRight >= tw + gap + 20) {
                side = 'right';
            } else if (freeBottom >= 260) {
                side = 'bottom';
            } else if (freeLeft >= tw + gap + 20) {
                side = 'left';
            } else {
                side = 'bottom';
            }

            let top, left, arrow;
            const arrowSize = 10;

            if (side === 'right') {
                left = cx + cw + gap;
                top = cy + (ch / 2) - 110;
                top = Math.max(20, Math.min(top, vh - 260));
                const arrowTop = (cy + ch / 2) - top - arrowSize;
                arrow = `position:absolute; left:-${arrowSize * 2}px; top:${Math.max(20, Math.min(arrowTop, 200))}px; `
                      + `width:0; height:0; border:${arrowSize}px solid transparent; border-right-color:white;`;
            }
            else if (side === 'bottom') {
                top = cy + ch + gap;
                // Center tooltip horizontally under the cutout
                left = cx + (cw / 2) - (tw / 2);
                left = Math.max(20, Math.min(left, vw - tw - 20));
                top = Math.min(top, vh - 260);
                const arrowLeft = (cx + cw / 2) - left - arrowSize;
                arrow = `position:absolute; top:-${arrowSize * 2}px; left:${Math.max(20, Math.min(arrowLeft, tw - 40))}px; `
                      + `width:0; height:0; border:${arrowSize}px solid transparent; border-bottom-color:white;`;
            }
            else if (side === 'left') {
                left = cx - tw - gap;
                top = cy + (ch / 2) - 110;
                top = Math.max(20, Math.min(top, vh - 260));
                const arrowTop = (cy + ch / 2) - top - arrowSize;
                arrow = `position:absolute; right:-${arrowSize * 2}px; top:${Math.max(20, Math.min(arrowTop, 200))}px; `
                      + `width:0; height:0; border:${arrowSize}px solid transparent; border-left-color:white;`;
            }

            this.tooltipLeft = Math.round(left);
            this.tooltipTop = Math.round(top);
            this.arrowStyle = arrow;
        },

        nextStep() {
            if (this.currentStep >= this.steps.length - 1) {
                this.requestFinish();
                return;
            }
            this.currentStep++;
            this.showStep();
        },

        requestFinish() {
            this.showConfirm = true;
        },

        confirmFinish() {
            this.showConfirm = false;
            this.active = false;

            fetch('{{ route("tour.completed") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ tour: 'travel_alert', dont_show_again: this.dontShowAgain })
            });
        }
    };
}
</script>
