@props(['tourKey', 'tourLabel', 'tourIcon' => 'fa-regular fa-compass', 'steps' => '[]'])

@php
    $customer = auth('customer')->user();
    $field = 'has_seen_' . $tourKey . '_tour';
    $hasSeen = $customer ? ($customer->$field ?? false) : true;
    $uniqueId = 'tour_' . $tourKey;
@endphp

@if($customer && !$hasSeen)
<div x-data="{{ $uniqueId }}()" x-show="active || showConfirm" x-cloak>
    {{-- Backdrop --}}
    <div x-show="active && !showConfirm" style="position: fixed; inset: 0; pointer-events: none; z-index: 200000;">
        <svg width="100%" height="100%" style="position: absolute; inset: 0;">
            <defs>
                <mask id="mask-{{ $tourKey }}">
                    <rect width="100%" height="100%" fill="white"/>
                    <rect fill="black" rx="8" :x="cutoutBox.x" :y="cutoutBox.y" :width="cutoutBox.w" :height="cutoutBox.h"/>
                </mask>
            </defs>
            <rect width="100%" height="100%" fill="rgba(0,0,0,0.6)" mask="url(#mask-{{ $tourKey }})"/>
        </svg>
    </div>
    <div x-show="active && !showConfirm" style="position: fixed; inset: 0; z-index: 200001;" @click="nextStep"></div>

    {{-- Tooltip --}}
    <div x-show="active && !showConfirm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         :style="'position:fixed; z-index:200002; width:416px; pointer-events:auto; top:'+tooltipTop+'px; left:'+tooltipLeft+'px;'">
        <div :style="arrowStyle"></div>
        <div style="background: white; border-radius: 10px; box-shadow: 0 8px 30px rgba(0,0,0,0.25); overflow: hidden;">
            <div style="background: #002742; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="{{ $tourIcon }}" style="color: #CEE741; font-size: 13px;"></i>
                    <span style="color: white; font-size: 12px; font-weight: 600;">Produkteinführung - {{ $tourLabel }}</span>
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
                        <button @click.stop="requestFinish()" style="padding: 6px 12px; font-size: 12px; color: #6b7280; background: none; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; font-weight: 500;">Überspringen</button>
                        <button @click.stop="nextStep" style="padding: 6px 14px; font-size: 12px; color: white; background: #002742; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;" x-text="currentStep === steps.length - 1 ? 'Fertig' : 'Weiter'"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm --}}
    <template x-if="showConfirm">
        <div style="position: fixed; inset: 0; z-index: 200010; display: flex; align-items: center; justify-content: center;">
            <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.5);"></div>
            <div x-transition style="position: relative; width: 420px; background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden;">
                <div style="background: #002742; padding: 16px 20px;">
                    <h3 style="margin: 0; color: white; font-size: 15px; font-weight: 700;">{{ $tourLabel }} Einführung beenden</h3>
                </div>
                <div style="padding: 20px;">
                    <p style="margin: 0 0 16px 0; font-size: 13px; color: #6b7280;">Möchten Sie, dass diese Einführung beim nächsten Besuch erneut angezeigt wird?</p>
                    <label style="display: flex; align-items: flex-start; gap: 10px; padding: 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; cursor: pointer; margin: 0 0 20px 0;">
                        <input type="checkbox" x-model="dontShowAgain" style="margin-top: 2px; width: 16px; height: 16px; accent-color: #002742; flex-shrink: 0;">
                        <span style="font-size: 13px; color: #374151;">Bereits gesehene Einführungen nicht mehr anzeigen</span>
                    </label>
                    <p style="margin: 0 0 20px 0; font-size: 12px; color: #9ca3af;">Sie können die Einführung jederzeit in Ihren <strong>Einstellungen</strong> unter <strong>Mein Profil</strong> erneut starten.</p>
                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button @click="showConfirm = false" style="padding: 8px 16px; font-size: 13px; color: #6b7280; background: none; border: 1px solid #e5e7eb; border-radius: 8px; cursor: pointer;">Zurück</button>
                        <button @click="confirmFinish()" style="padding: 8px 20px; font-size: 13px; color: white; background: #002742; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Beenden</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function {{ $uniqueId }}() {
    return {
        active: true,
        currentStep: 0,
        cutoutBox: { x: 0, y: 0, w: 0, h: 0 },
        tooltipTop: -1000,
        tooltipLeft: -1000,
        arrowStyle: '',
        showConfirm: false,
        dontShowAgain: false,
        steps: {!! $steps !!},

        init() { setTimeout(() => this.showStep(), 600); },

        showStep() {
            const step = this.steps[this.currentStep];
            if (!step) return;
            setTimeout(() => this.measure(), 50);
        },

        measure() {
            const step = this.steps[this.currentStep];
            const target = document.querySelector(step.target);
            if (!target) { this.nextStep(); return; }

            const pad = 6;
            const rect = target.getBoundingClientRect();
            let cx = rect.left - pad, cy = rect.top - pad, cw = rect.width + pad * 2, ch = rect.height + pad * 2;
            this.cutoutBox = { x: cx, y: cy, w: cw, h: ch };

            const vw = window.innerWidth, vh = window.innerHeight, tw = 416, gap = 14, arrowSize = 10;
            const freeRight = vw - (cx + cw), freeBottom = vh - (cy + ch), freeLeft = cx;

            let side;
            if (step.forceBelow) side = 'bottom';
            else if (freeRight >= tw + gap + 20) side = 'right';
            else if (freeBottom >= 260) side = 'bottom';
            else if (freeLeft >= tw + gap + 20) side = 'left';
            else side = 'bottom';

            let top, left, arrow;
            if (side === 'right') {
                left = cx + cw + gap;
                top = Math.max(20, Math.min(cy + ch / 2 - 110, vh - 260));
                const at = cy + ch / 2 - top - arrowSize;
                arrow = `position:absolute; left:-${arrowSize*2}px; top:${Math.max(20, Math.min(at, 200))}px; width:0; height:0; border:${arrowSize}px solid transparent; border-right-color:white;`;
            } else if (side === 'bottom') {
                top = Math.min(cy + Math.min(ch, 80) + gap, vh - 260);
                left = Math.max(20, Math.min(cx + cw / 2 - tw / 2, vw - tw - 20));
                const al = cx + cw / 2 - left - arrowSize;
                arrow = `position:absolute; top:-${arrowSize*2}px; left:${Math.max(20, Math.min(al, tw - 40))}px; width:0; height:0; border:${arrowSize}px solid transparent; border-bottom-color:white;`;
            } else {
                left = cx - tw - gap;
                top = Math.max(20, Math.min(cy + ch / 2 - 110, vh - 260));
                const at = cy + ch / 2 - top - arrowSize;
                arrow = `position:absolute; right:-${arrowSize*2}px; top:${Math.max(20, Math.min(at, 200))}px; width:0; height:0; border:${arrowSize}px solid transparent; border-left-color:white;`;
            }
            this.tooltipLeft = Math.round(left);
            this.tooltipTop = Math.round(top);
            this.arrowStyle = arrow;
        },

        nextStep() {
            if (this.currentStep >= this.steps.length - 1) { this.requestFinish(); return; }
            this.currentStep++;
            this.showStep();
        },

        requestFinish() { this.showConfirm = true; },

        confirmFinish() {
            this.showConfirm = false;
            this.active = false;
            fetch('{{ route("tour.completed") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json' },
                body: JSON.stringify({ tour: '{{ $tourKey }}', dont_show_again: this.dontShowAgain })
            });
        }
    };
}
</script>
@endif
