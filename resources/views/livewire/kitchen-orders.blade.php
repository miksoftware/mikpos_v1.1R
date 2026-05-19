<div wire:poll.5s="poll"
    x-data="{
        audioCtx: null,
        ensureAudio() {
            if (!this.audioCtx) {
                this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (this.audioCtx.state === 'suspended') this.audioCtx.resume();
            return this.audioCtx;
        },
        playSound() {
            try {
                const ctx = this.ensureAudio();
                const now = ctx.currentTime;

                // ding-ding-ding
                [0, 0.18, 0.36].forEach((offset, i) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'sine';
                    osc.frequency.value = 880 + i * 120;
                    gain.gain.setValueAtTime(0.001, now + offset);
                    gain.gain.exponentialRampToValueAtTime(0.35, now + offset + 0.01);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + offset + 0.25);
                    osc.start(now + offset);
                    osc.stop(now + offset + 0.3);
                });
            } catch (e) { console.log('Audio not available:', e); }
        }
    }"
    x-init="
        document.addEventListener('click', () => ensureAudio(), { once: true });
        document.addEventListener('touchstart', () => ensureAudio(), { once: true });
    "
    @play-kitchen-sound.window="playSound()"
    class="p-4 md:p-6 space-y-4">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Comandas</h1>
            <p class="text-slate-500 text-sm mt-0.5">Pedidos en tiempo real por módulo de preparación</p>
        </div>

        {{-- Live indicator --}}
        <div class="flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-xl shadow-sm text-xs font-medium text-slate-600">
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
            </span>
            En vivo · actualiza cada 5s
        </div>
    </div>

    {{-- Station filter pills --}}
    <div class="flex flex-wrap gap-2">
        <button wire:click="selectStation(null)"
            class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors
                {{ is_null($selectedStationId) ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:border-[#ff7261] hover:text-[#ff7261]' }}">
            Todas
        </button>
        @foreach($stations as $station)
        <button wire:click="selectStation({{ $station->id }})"
            class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors inline-flex items-center gap-1.5
                {{ $selectedStationId == $station->id ? 'text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:opacity-80' }}"
            @if($selectedStationId == $station->id) style="background: {{ $station->color ?? '#6b7280' }};" @endif>
            <span>{{ $station->icon }}</span>
            <span>{{ $station->name }}</span>
        </button>
        @endforeach
    </div>

    {{-- Kanban columns --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- Column: PENDIENTES --}}
        <div class="bg-amber-50/50 border-2 border-amber-200/60 rounded-2xl p-3 space-y-3 min-h-[60vh]">
            <div class="flex items-center justify-between px-2">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    <h3 class="font-bold text-amber-800 uppercase text-sm tracking-wide">Pendientes</h3>
                </div>
                <span class="text-xs font-bold text-amber-900 bg-amber-200 px-2 py-0.5 rounded-full">{{ $pending->count() }}</span>
            </div>

            @forelse($pending as $order)
                @include('livewire.partials.kitchen-order-card', ['order' => $order, 'accent' => 'amber'])
            @empty
                <div class="flex flex-col items-center justify-center py-10 text-amber-400/60">
                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
                    <p class="text-xs font-medium">Sin comandas pendientes</p>
                </div>
            @endforelse
        </div>

        {{-- Column: EN PREPARACIÓN --}}
        <div class="bg-blue-50/50 border-2 border-blue-200/60 rounded-2xl p-3 space-y-3 min-h-[60vh]">
            <div class="flex items-center justify-between px-2">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <h3 class="font-bold text-blue-800 uppercase text-sm tracking-wide">En preparación</h3>
                </div>
                <span class="text-xs font-bold text-blue-900 bg-blue-200 px-2 py-0.5 rounded-full">{{ $preparing->count() }}</span>
            </div>

            @forelse($preparing as $order)
                @include('livewire.partials.kitchen-order-card', ['order' => $order, 'accent' => 'blue'])
            @empty
                <div class="flex flex-col items-center justify-center py-10 text-blue-400/60">
                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <p class="text-xs font-medium">Sin comandas en preparación</p>
                </div>
            @endforelse
        </div>

        {{-- Column: LISTAS --}}
        <div class="bg-green-50/50 border-2 border-green-200/60 rounded-2xl p-3 space-y-3 min-h-[60vh]">
            <div class="flex items-center justify-between px-2">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                    <h3 class="font-bold text-green-800 uppercase text-sm tracking-wide">Listas</h3>
                </div>
                <span class="text-xs font-bold text-green-900 bg-green-200 px-2 py-0.5 rounded-full">{{ $ready->count() }}</span>
            </div>

            @forelse($ready as $order)
                @include('livewire.partials.kitchen-order-card', ['order' => $order, 'accent' => 'green'])
            @empty
                <div class="flex flex-col items-center justify-center py-10 text-green-400/60">
                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-xs font-medium">Sin comandas listas</p>
                </div>
            @endforelse
        </div>

    </div>
</div>
