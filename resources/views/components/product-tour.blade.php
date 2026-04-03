{{-- Product Tour Overlay --}}
<div x-data="productTour()" x-show="active || showConfirm" x-cloak>

    {{-- Backdrop with cutout (only during tour steps) --}}
    <div x-show="active && !showConfirm" style="position: fixed; inset: 0; pointer-events: none; z-index: 200000;">
        <svg width="100%" height="100%" style="position: absolute; inset: 0;">
            <defs>
                <mask id="tour-mask">
                    <rect width="100%" height="100%" fill="white"/>
                    <rect fill="black" rx="8" x-ref="cutout"
                          :x="cutoutBox.x" :y="cutoutBox.y" :width="cutoutBox.w" :height="cutoutBox.h"/>
                </mask>
            </defs>
            <rect width="100%" height="100%" fill="rgba(0,0,0,0.6)" mask="url(#tour-mask)"/>
        </svg>
    </div>

    {{-- Click blocker (only during tour steps) --}}
    <div x-show="active && !showConfirm" style="position: fixed; inset: 0; z-index: 200001;" @click="nextStep"></div>

    {{-- Tooltip with arrow --}}
    <div x-show="active && !showConfirm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         :style="'position:fixed; z-index:200002; width:416px; pointer-events:auto; top:'+tooltipTop+'px; left:'+tooltipLeft+'px;'">

        {{-- Arrow --}}
        <div :style="arrowStyle"></div>

        <div style="background: white; border-radius: 10px; box-shadow: 0 8px 30px rgba(0,0,0,0.25); overflow: hidden;">
            {{-- Header --}}
            <div style="background: #002742; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-regular fa-compass" style="color: #CEE741; font-size: 13px;"></i>
                    <span style="color: white; font-size: 12px; font-weight: 600;">Produkteinführung - Oberfläche</span>
                </div>
                <span style="color: rgba(255,255,255,0.5); font-size: 11px;" x-text="(currentStep + 1) + ' / ' + steps.length"></span>
            </div>

            {{-- Body --}}
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
            {{-- Dark backdrop --}}
            <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.5);"></div>

            {{-- Dialog --}}
            <div x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 style="position: relative; width: 420px; background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden;">

                <div style="background: #002742; padding: 16px 20px;">
                    <h3 style="margin: 0; color: white; font-size: 15px; font-weight: 700;">Produkteinführung - Oberfläche beenden</h3>
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
function productTour() {
    return {
        active: {{ auth('customer')->user() && !auth('customer')->user()->has_seen_platform_tour ? 'true' : 'false' }},
        currentStep: 0,
        userMenuWasOpened: false,
        cutoutBox: { x: 0, y: 0, w: 0, h: 0 },
        tooltipTop: -1000,
        tooltipLeft: -1000,
        arrowStyle: '',
        showConfirm: false,
        dontShowAgain: false,

        steps: [
            {
                target: '#main-navigation',
                title: 'Hauptmenü',
                description: 'Über die Seitenleiste erreichen Sie alle Bereiche der Plattform. Jedes Symbol führt zu einem eigenen Modul &ndash; von Einreisebestimmungen über Reisedaten bis hin zu Travel Alert.',
            },
            {
                target: '#user-menu',
                title: 'Benutzermenü',
                description: 'Hier finden Sie Ihr persönliches Menü. Klicken Sie auf Ihren Namen, um das Menü zu öffnen.',
            },
            {
                target: '#user-menu',
                title: 'Ihr Menü',
                description: '<strong>Dashboard</strong> &ndash; Hier finden Sie eine Übersicht Ihres Accounts und Ihrer Aktivitäten.<br><br>'
                    + '<strong>Einstellungen</strong> &ndash; Verwalten Sie Ihre persönlichen Daten, Benachrichtigungen und weitere Optionen der Passolution Travel Information Platform.<br><br>'
                    + '<strong>Abmelden</strong> &ndash; Melden Sie sich sicher von Ihrem Account ab.',
                openUserMenu: true,
            }
        ],

        init() {
            if (!this.active) return;
            setTimeout(() => this.showStep(), 500);
        },

        showStep() {
            const step = this.steps[this.currentStep];
            if (!step) return;

            if (step.openUserMenu) {
                const data = Alpine.$data(document.querySelector('#user-menu'));
                if (data) data.open = true;
                this.userMenuWasOpened = true;
            } else if (this.userMenuWasOpened) {
                const data = Alpine.$data(document.querySelector('#user-menu'));
                if (data) data.open = false;
                this.userMenuWasOpened = false;
            }

            setTimeout(() => this.measure(), step.openUserMenu ? 250 : 50);
        },

        measure() {
            const step = this.steps[this.currentStep];
            const target = document.querySelector(step.target);
            if (!target) return;

            const pad = 6;
            let rect = target.getBoundingClientRect();

            if (step.target === '#main-navigation') {
                const wrapper = target.closest('.navigation') || target;
                rect = wrapper.getBoundingClientRect();
            }

            let cx = rect.left - pad;
            let cy = rect.top - pad;
            let cw = rect.width + pad * 2;
            let ch = rect.height + pad * 2;

            if (step.openUserMenu) {
                const dropdown = document.querySelector('#user-menu [x-show="open"]');
                if (dropdown) {
                    const dr = dropdown.getBoundingClientRect();
                    const l = Math.min(rect.left, dr.left) - pad;
                    const t = Math.min(rect.top, dr.top) - pad;
                    const r = Math.max(rect.right, dr.right) + pad;
                    const b = Math.max(rect.bottom, dr.bottom) + pad;
                    cx = l; cy = t; cw = r - l; ch = b - t;
                }
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
            if (freeRight >= tw + gap + 20) {
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
                left = cx + cw - tw;
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
            // Close user menu before showing confirm dialog
            if (this.userMenuWasOpened) {
                const data = Alpine.$data(document.querySelector('#user-menu'));
                if (data) data.open = false;
            }
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
                body: JSON.stringify({ tour: 'platform', dont_show_again: this.dontShowAgain })
            });

            // Trigger Travel Alert tour after platform tour finishes
            window.dispatchEvent(new CustomEvent('platform-tour-finished'));
        }
    };
}
</script>
