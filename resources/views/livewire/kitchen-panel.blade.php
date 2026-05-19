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
                [0, 0.18, 0.36].forEach((offset, i) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain); gain.connect(ctx.destination);
                    osc.type = 'sine';
                    osc.frequency.value = 880 + i * 120;
                    gain.gain.setValueAtTime(0.001, now + offset);
                    gain.gain.exponentialRampToValueAtTime(0.4, now + offset + 0.01);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + offset + 0.28);
                    osc.start(now + offset);
                    osc.stop(now + offset + 0.32);
                });
            } catch (e) { console.log('Audio:', e); }
        }
    }"
    x-init="
        document.addEventListener('click', () => ensureAudio(), { once: true });
        document.addEventListener('touchstart', () => ensureAudio(), { once: true });
    "
    @play-kitchen-sound.window="playSound()"
    class="p-4 md:p-6 space-y-4">

    {{-- ══ Header ══ --}}
    <div class="bg-white border-2 border-[#ff7261]/40 rounded-2xl shadow-sm p-5">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center shadow-md">
                    <span class="text-2xl">👨‍🍳</span>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-900">Mi Panel</h1>
                    <p class="text-slate-500 text-sm mt-0.5">
                        {{ auth()->user()->name }}
                        @if($myStations->count() > 0)
                            · Áreas: {{ $myStations->pluck('name')->join(', ') }}
                        @endif
                    </p>
                </div>
            </div>

            {{-- Live indicator + counters --}}
            <div class="flex items-center gap-2 flex-wrap">
                <div class="flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-600">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                    </span>
                    En vivo · 5s
                </div>

                <div class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-xl text-xs font-semibold text-amber-700">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    {{ $pending->count() }} pendientes
                </div>
                <div class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-xl text-xs font-semibold text-blue-700">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    {{ $preparing->count() }} en preparación
                </div>
                <div class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-50 border border-green-200 rounded-xl text-xs font-semibold text-green-700">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    {{ $ready->count() }} listas
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Zero-state: user has no stations assigned ══ --}}
    @if(empty($myStationIds))
    <div class="bg-white border-2 border-amber-200 rounded-2xl p-10 text-center">
        <div class="mx-auto w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-slate-900 mb-1">No tienes áreas de preparación asignadas</h3>
        <p class="text-sm text-slate-500 max-w-md mx-auto">
            Pídele a un administrador que asigne una o más áreas a tu usuario desde el módulo de Usuarios.
            Una vez asignadas, aquí verás las comandas correspondientes.
        </p>
    </div>
    @else

    {{-- ══ Station filter pills (only my stations) ══ --}}
    @if($myStations->count() > 1)
    <div class="flex flex-wrap gap-2">
        <button wire:click="selectStation(null)"
            class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors cursor-pointer
                {{ is_null($selectedStationId) ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:border-[#ff7261] hover:text-[#ff7261]' }}">
            Todas mis áreas
        </button>
        @foreach($myStations as $station)
        <button wire:click="selectStation({{ $station->id }})"
            class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors inline-flex items-center gap-1.5 cursor-pointer
                {{ $selectedStationId == $station->id ? 'text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:opacity-80' }}"
            @if($selectedStationId == $station->id) style="background: {{ $station->color ?? '#6b7280' }};" @endif>
            <span>{{ $station->icon }}</span>
            <span>{{ $station->name }}</span>
        </button>
        @endforeach
    </div>
    @endif

    {{-- ══ Kanban columns ══ --}}
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
                @include('livewire.partials.kitchen-panel-card', ['order' => $order])
            @empty
                <div class="flex flex-col items-center justify-center py-10 text-amber-400/60">
                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
                    <p class="text-xs font-medium">Sin pendientes</p>
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
                @include('livewire.partials.kitchen-panel-card', ['order' => $order])
            @empty
                <div class="flex flex-col items-center justify-center py-10 text-blue-400/60">
                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <p class="text-xs font-medium">Nada en preparación</p>
                </div>
            @endforelse
        </div>

        {{-- Column: LISTAS --}}
        <div class="bg-green-50/50 border-2 border-green-200/60 rounded-2xl p-3 space-y-3 min-h-[60vh]">
            <div class="flex items-center justify-between px-2">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                    <h3 class="font-bold text-green-800 uppercase text-sm tracking-wide">Listas para entregar</h3>
                </div>
                <span class="text-xs font-bold text-green-900 bg-green-200 px-2 py-0.5 rounded-full">{{ $ready->count() }}</span>
            </div>

            @forelse($ready as $order)
                @include('livewire.partials.kitchen-panel-card', ['order' => $order])
            @empty
                <div class="flex flex-col items-center justify-center py-10 text-green-400/60">
                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-xs font-medium">Nada listo todavía</p>
                </div>
            @endforelse
        </div>
    </div>
    @endif
</div>
