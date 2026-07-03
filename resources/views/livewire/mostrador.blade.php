<div class="p-4 md:p-6 space-y-4">

    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    {{-- VISTA: MESAS                                                            --}}
    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    @if($view === 'mesas')

    @php
        $totalMesas   = $mesas->count();
        $mesasOcupadas = $mesas->where('status', 'ocupada')->count();
        $mesasLibres   = $totalMesas - $mesasOcupadas;
        $totalVentaAbierta = $mesas->filter(fn($m) => $m->status === 'ocupada' && $m->cuenta)
                                    ->sum(fn($m) => $m->cuenta->getTotal());
    @endphp

    {{-- Header with salmon border (no dark fill) --}}
    <div class="bg-white border-2 border-[#ff7261]/40 rounded-2xl shadow-sm p-5">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 flex items-center gap-2">
                    <span class="text-3xl">🍽️</span>
                    Mostrador
                </h1>
                <p class="text-slate-500 text-sm mt-1">Selecciona una mesa para tomar el pedido</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if($needsReconciliation)
                <div class="flex items-center gap-2 px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-xl text-xs font-medium text-amber-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Caja no abierta
                </div>
                @endif

                @if($useStations && auth()->user()->hasPermission('kitchen.view'))
                <a href="{{ route('kitchen-orders') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:shadow-lg hover:shadow-[#ff7261]/30 text-white text-sm font-semibold rounded-xl transition-all cursor-pointer">
                    <span class="text-base">👨‍🍳</span>
                    Comandas
                </a>
                @endif
            </div>
        </div>

        {{-- Stats grid --}}
        <div class="mt-5 grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                <div class="text-[11px] text-slate-500 uppercase font-medium">Total mesas</div>
                <div class="text-2xl font-bold text-slate-800 mt-0.5">{{ $totalMesas }}</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-xl p-3">
                <div class="text-[11px] text-green-600 uppercase font-medium">Disponibles</div>
                <div class="text-2xl font-bold text-green-700 mt-0.5">{{ $mesasLibres }}</div>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-xl p-3">
                <div class="text-[11px] text-red-600 uppercase font-medium">Ocupadas</div>
                <div class="text-2xl font-bold text-red-700 mt-0.5">{{ $mesasOcupadas }}</div>
            </div>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
                <div class="text-[11px] text-amber-600 uppercase font-medium">Abierto</div>
                <div class="text-2xl font-bold text-amber-700 mt-0.5">${{ number_format($totalVentaAbierta, 0) }}</div>
            </div>
        </div>
    </div>

    {{-- Sector filter pills --}}
    @if($sectors->count() > 0)
    <div class="flex flex-wrap gap-2">
        <button wire:click="selectSector(null)"
            class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors cursor-pointer
                {{ is_null($selectedSectorId) ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-md' : 'bg-white border border-slate-200 text-slate-600 hover:border-[#ff7261] hover:text-[#ff7261]' }}">
            Todos
        </button>
        @foreach($sectors as $sector)
        <button wire:click="selectSector({{ $sector->id }})"
            class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors cursor-pointer
                {{ $selectedSectorId == $sector->id ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-md' : 'bg-white border border-slate-200 text-slate-600 hover:border-[#ff7261] hover:text-[#ff7261]' }}">
            {{ $sector->name }}
        </button>
        @endforeach
    </div>
    @endif

    {{-- Mesa grid --}}
    @if($mesas->count() > 0)
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
        @foreach($mesas as $mesa)
        @php
            $isOcupada = $mesa->status === 'ocupada';
            $itemCount = $isOcupada ? ($mesa->cuenta?->items->count() ?? 0) : 0;
            $cuentaTotal = $isOcupada ? ($mesa->cuenta ? $mesa->cuenta->getTotal() : 0) : 0;
        @endphp
        <button wire:click="openMesa({{ $mesa->id }})"
            class="group relative flex flex-col items-center justify-center gap-2 p-4 rounded-2xl border-2 transition-all duration-200 text-center shadow-sm hover:shadow-xl hover:-translate-y-0.5 overflow-hidden cursor-pointer
                {{ $isOcupada
                    ? 'bg-gradient-to-br from-red-50 to-rose-50 border-red-300 hover:border-red-400'
                    : 'bg-gradient-to-br from-green-50 to-emerald-50 border-green-300 hover:border-green-400' }}">

            {{-- Corner decoration --}}
            <div class="absolute top-0 right-0 w-14 h-14 opacity-30
                {{ $isOcupada ? 'bg-red-300' : 'bg-green-300' }}"
                style="clip-path: polygon(100% 0, 0 0, 100% 100%);"></div>

            {{-- Status icon --}}
            <div class="relative w-14 h-14 rounded-full flex items-center justify-center shadow-inner transform group-hover:scale-110 transition-transform
                {{ $isOcupada ? 'bg-gradient-to-br from-red-100 to-red-200' : 'bg-gradient-to-br from-green-100 to-green-200' }}">
                @if($isOcupada)
                <svg class="w-7 h-7 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor">
                    <path d="M160-160q-33 0-56.5-23.5T80-240v-440h80v440h280v80H160Zm120-560q-33 0-56.5-23.5T200-800q0-33 23.5-56.5T280-880q33 0 56.5 23.5T360-800q0 33-23.5 56.5T280-720ZM480-80v-200H280q-33 0-56.5-23.5T200-360v-236q0-35 24-59.5t58-24.5q19 0 35.5 8t28.5 22q45 49 96.5 89.5T560-520h54q-25-17-39.5-42.5T560-620h241q0 32-14.5 57.5T747-520h133v80H720v360h-80v-360h-80q-53 0-107-23t-93-55v138h120q33 0 56.5 23.5T560-300v220h-80Z"/>
                </svg>
                @else
                <svg class="w-7 h-7 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor">
                    <path d="M173-600h614l-34-120H208l-35 120Zm307-60Zm192 140H289l-11 80h404l-10-80ZM160-160l49-360h-89q-20 0-31.5-16T82-571l57-200q4-13 14-21t24-8h606q14 0 24 8t14 21l57 200q5 19-6.5 35T840-520h-88l48 360h-80l-27-200H267l-27 200h-80Z"/>
                </svg>
                @endif
            </div>

            {{-- Mesa name --}}
            <div class="font-bold text-slate-800 text-base leading-tight">{{ $mesa->name }}</div>

            {{-- Sector badge --}}
            @if($mesa->sector)
            <div class="text-xs text-slate-500 -mt-1">{{ $mesa->sector->name }}</div>
            @endif

            {{-- Status badge --}}
            @if($isOcupada)
            <div class="flex flex-col items-center gap-0.5 w-full">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500 text-white">
                    <span class="w-1.5 h-1.5 bg-white rounded-full animate-pulse"></span>
                    OCUPADA
                </span>
                @if($itemCount > 0)
                <span class="text-xs text-slate-500 font-medium">{{ $itemCount }} {{ $itemCount === 1 ? 'ítem' : 'ítems' }}</span>
                @endif
                @if($cuentaTotal > 0)
                <span class="text-sm font-bold bg-gradient-to-r from-[#ff7261] to-[#a855f7] bg-clip-text text-transparent">
                    ${{ number_format($cuentaTotal, 2) }}
                </span>
                @endif
            </div>
            @else
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-500 text-white">
                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                LIBRE
            </span>
            @endif
        </button>
        @endforeach
    </div>
    @else
    <div class="flex flex-col items-center justify-center py-20 text-slate-400 bg-white rounded-2xl border border-slate-200">
        <svg class="w-14 h-14 mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18M10 4v16M14 4v16M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>
        </svg>
        <p class="font-medium text-slate-500">No hay mesas disponibles</p>
        <p class="text-sm mt-1">Crea mesas desde el módulo de Mesas</p>
    </div>
    @endif

    @endif
    {{-- END VISTA MESAS --}}


    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    {{-- VISTA: ORDEN (split screen)                                             --}}
    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    @if($view === 'orden')

    <div class="flex flex-col h-[calc(100vh-8rem)] -mt-2" x-data="{ searchFocus: false, showSent: false }">

        {{-- Top bar: mesa info + persons counter + options --}}
        <div class="bg-white border-2 border-[#ff7261]/40 rounded-2xl shadow-sm mb-3 flex-shrink-0">
            <div class="flex items-center justify-between gap-3 px-4 py-3">
                <div class="flex items-center gap-3 min-w-0">
                    <button wire:click="backToMesas"
                        class="p-2 rounded-xl text-slate-500 hover:bg-[#ff7261]/10 hover:text-[#ff7261] transition-colors flex-shrink-0 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-xl font-bold text-slate-900 truncate">{{ $selectedMesaName }}</h2>
                            @if($selectedSectorName)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#ff7261]/10 text-[#ff7261] border border-[#ff7261]/20">
                                {{ $selectedSectorName }}
                            </span>
                            @endif
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            @php
                                $unsentCount = count($this->unsentItems);
                                $sentCount   = count($this->sentItems);
                            @endphp
                            @if($unsentCount > 0)
                                <span class="text-emerald-600 font-semibold">{{ $unsentCount }} {{ $unsentCount === 1 ? 'ítem nuevo' : 'ítems nuevos' }}</span>
                                @if($sentCount > 0) · @endif
                            @endif
                            @if($sentCount > 0)
                                <span class="text-slate-500">{{ $sentCount }} ya pedido{{ $sentCount === 1 ? '' : 's' }}</span>
                            @endif
                            @if($unsentCount === 0 && $sentCount === 0)
                                Sin ítems aún
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Right: persons counter + options dropdown --}}
                <div class="flex items-center gap-2 flex-shrink-0">

                    {{-- Persons counter --}}
                    <div class="flex items-center gap-1 px-2 py-1 bg-white border border-slate-200 rounded-xl shadow-sm">
                        <button wire:click="decrementPersons"
                            class="w-6 h-6 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-sm transition-colors cursor-pointer">−</button>
                        <div class="flex items-center gap-1 px-1.5">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-sm font-bold text-slate-800 min-w-[1.25rem] text-center">{{ $numPersons }}</span>
                        </div>
                        <button wire:click="incrementPersons"
                            class="w-6 h-6 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-sm transition-colors cursor-pointer">+</button>
                    </div>

                    {{-- Options dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.outside="open = false"
                            class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-sm transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                            Opciones
                            <svg class="w-3 h-3 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak x-transition
                            class="absolute right-0 mt-1 w-52 bg-white border border-slate-200 rounded-xl shadow-lg z-50 py-1 overflow-hidden">
                            <button @click="open=false" wire:click="openComanda"
                                class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer">
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Ver comandas
                            </button>
                            <button @click="open=false" wire:click="openPrecuenta"
                                class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                Precuenta
                            </button>
                            <button @click="open=false" wire:click="openChangeMesaModal"
                                class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer">
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                Cambiar mesa
                            </button>
                            <button @click="open=false" wire:click="openSplitModal"
                                class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer">
                                <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Dividir cuenta
                            </button>
                            <div class="my-1 border-t border-slate-100"></div>
                            <button @click="open=false" wire:click="confirmCancelarCuenta"
                                class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                Cancelar cuenta
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Split panel --}}
        <div class="flex gap-4 flex-1 overflow-hidden">

            {{-- ── LEFT: Order panel ───────────────────────────────────────── --}}
            <div class="w-2/5 flex flex-col bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

                {{-- Panel header: current batch title + "Ya pedido" toggle --}}
                <div class="px-4 py-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 leading-tight">Pedido actual</h3>
                            <p class="text-[10px] text-slate-500 leading-tight">Nuevos ítems para enviar</p>
                        </div>
                    </div>

                    @if(count($this->sentItems) > 0)
                    <button @click="showSent = !showSent"
                        class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors cursor-pointer">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span x-text="showSent ? 'Ocultar' : 'Ya pedido ({{ count($this->sentItems) }})'"></span>
                    </button>
                    @endif
                </div>

                {{-- ═══ SENT ITEMS (collapsible summary) ═══ --}}
                @if(count($this->sentItems) > 0)
                <div x-show="showSent" x-collapse class="border-b-2 border-emerald-100 bg-emerald-50/30">
                    <div class="px-4 py-2 flex items-center justify-between">
                        <div class="flex items-center gap-1.5 text-[11px] font-bold text-emerald-700 uppercase tracking-wide">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Ya pedido
                        </div>
                        <span class="text-[10px] text-slate-500">Solo lectura</span>
                    </div>
                    <div class="max-h-40 overflow-y-auto divide-y divide-emerald-100/60">
                        @foreach($this->sentItems as $entry)
                        @php $item = $entry['item']; @endphp
                        <div class="flex items-center gap-2 px-4 py-1.5 text-xs group/sent">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-emerald-100 text-emerald-700 font-bold text-[10px] flex-shrink-0">
                                {{ rtrim(rtrim(number_format((float)$item['quantity'], 3), '0'), '.') }}
                            </span>
                            <span class="flex-1 text-slate-700 truncate">{{ $item['name'] }}</span>
                            @if(!empty($item['station_name']))
                            <span class="text-[9px] px-1.5 py-0.5 rounded-full font-medium text-white flex-shrink-0"
                                style="background-color: {{ $item['station_color'] ?? '#6b7280' }};">
                                {{ $item['station_icon'] ?? '' }}
                            </span>
                            @endif
                            <span class="text-slate-600 font-semibold w-14 text-right flex-shrink-0">
                                ${{ number_format($item['subtotal'] + $item['tax_amount'], 2) }}
                            </span>
                            {{-- Remove sent item button --}}
                            <button wire:click="openRemoveSentModal({{ $entry['idx'] }})"
                                class="flex-shrink-0 opacity-0 group-hover/sent:opacity-100 p-1 rounded text-red-400 hover:text-red-600 hover:bg-red-50 transition-all cursor-pointer"
                                title="Quitar ítem ya pedido">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- ═══ UNSENT ITEMS (the "new batch" being built) ═══ --}}
                <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                    @forelse($this->unsentItems as $entry)
                    @php $idx = $entry['idx']; $item = $entry['item']; @endphp
                    <div class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50/40 transition-colors animate-[slideIn_200ms_ease-out]">
                        {{-- Item type icon --}}
                        <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center mt-0.5
                            {{ $item['type'] === 'ingredient' ? 'bg-amber-100' : 'bg-purple-100' }}">
                            @if($item['type'] === 'ingredient')
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                            @else
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate leading-tight">{{ $item['name'] }}</p>
                            <div class="flex flex-wrap items-center gap-1 mt-1">
                                <p class="text-[11px] text-slate-500">${{ number_format($item['unit_price'] + ($item['tax_rate'] > 0 ? $item['unit_price'] * $item['tax_rate'] / 100 : 0), 2) }} c/u</p>
                                @if(!empty($item['station_name']))
                                <span class="inline-flex items-center gap-0.5 text-[10px] px-1.5 py-0.5 rounded-full font-semibold text-white"
                                    style="background-color: {{ $item['station_color'] ?? '#6b7280' }};">
                                    {{ $item['station_icon'] ?? '' }} {{ $item['station_name'] }}
                                </span>
                                @endif
                                <button wire:click="openNotesModal({{ $idx }})"
                                    class="inline-flex items-center gap-0.5 text-[10px] px-1.5 py-0.5 rounded-full transition-colors cursor-pointer
                                        {{ !empty($item['notes']) ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Nota
                                </button>
                            </div>
                            @if(!empty($item['notes']))
                            <p class="text-[10px] text-yellow-700 mt-0.5 italic truncate">📝 {{ $item['notes'] }}</p>
                            @endif
                            @if(!empty($item['selected_ingredients']))
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach($item['selected_ingredients'] as $sel)
                                <span class="inline-flex items-center gap-0.5 text-[10px] px-1.5 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                                    <svg class="w-2.5 h-2.5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ $sel['ingredient_name'] }}
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        {{-- Qty controls --}}
                        <div class="flex items-center gap-1 flex-shrink-0 bg-slate-50 rounded-lg p-0.5">
                            <button wire:click="decrementQty({{ $idx }})"
                                class="w-6 h-6 rounded-md bg-white shadow-sm hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors font-bold text-sm leading-none cursor-pointer">
                                −
                            </button>
                            <span class="w-6 text-center text-sm font-bold text-slate-800">{{ (int) $item['quantity'] }}</span>
                            <button wire:click="incrementQty({{ $idx }})"
                                class="w-6 h-6 rounded-md bg-white shadow-sm hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors font-bold text-sm leading-none cursor-pointer">
                                +
                            </button>
                        </div>

                        <div class="flex flex-col items-end gap-1 flex-shrink-0">
                            <span class="text-sm font-bold text-slate-900">${{ number_format($item['subtotal'] + $item['tax_amount'], 2) }}</span>
                            <button wire:click="removeItem({{ $idx }})"
                                class="p-0.5 text-slate-300 hover:text-red-500 transition-colors rounded cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center h-full py-12 px-4 text-slate-400">
                        @if(count($this->sentItems) > 0)
                            <div class="w-14 h-14 rounded-full bg-emerald-100 flex items-center justify-center mb-3">
                                <svg class="w-7 h-7 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <p class="text-sm font-semibold text-slate-700">Todo pedido</p>
                            <p class="text-xs mt-1 text-center">Agrega más productos del menú para seguir pidiendo</p>
                        @else
                            <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <p class="text-sm font-medium text-slate-500">Pedido vacío</p>
                            <p class="text-xs mt-1">Selecciona productos del menú</p>
                        @endif
                    </div>
                    @endforelse
                </div>

                <style>
                    @keyframes slideIn {
                        from { opacity: 0; transform: translateX(-8px); }
                        to   { opacity: 1; transform: translateX(0); }
                    }
                </style>

                {{-- Order footer / totals --}}
                <div class="border-t border-slate-200 px-4 py-3 bg-gradient-to-br from-slate-50 to-white flex-shrink-0 space-y-2">

                    {{-- Totals --}}
                    <div class="space-y-0.5">
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>Subtotal (mesa)</span>
                            <span>${{ number_format($subtotal, 2) }}</span>
                        </div>
                        @if($taxTotal > 0)
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>Impuestos</span>
                            <span>${{ number_format($taxTotal, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between items-baseline pt-1 border-t border-dashed border-slate-200">
                            <span class="text-sm font-bold text-slate-900">Total mesa</span>
                            <span class="text-xl font-extrabold bg-gradient-to-r from-[#ff7261] to-[#a855f7] bg-clip-text text-transparent">
                                ${{ number_format($total, 2) }}
                            </span>
                        </div>
                    </div>

                    {{-- CTA: Hacer pedido (only if station-mode enabled) --}}
                    @if($useStations && auth()->user()->hasPermission('kitchen.send'))
                    <button wire:click="sendToKitchen"
                        @class(['w-full px-4 py-3 text-sm font-bold rounded-xl transition-all inline-flex items-center justify-center gap-2 relative overflow-hidden group',
                            'bg-gradient-to-r from-emerald-500 to-teal-600 text-white hover:shadow-xl hover:shadow-emerald-500/40 hover:-translate-y-0.5 animate-[ctaGlow_2.5s_ease-in-out_infinite] cursor-pointer' => $this->hasUnsentItems,
                            'bg-slate-100 text-slate-400 cursor-not-allowed' => !$this->hasUnsentItems])
                        @disabled(!$this->hasUnsentItems)>
                        @if($this->hasUnsentItems)
                        <span class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                        @endif
                        <svg class="w-5 h-5 relative" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <span class="relative">
                            {{ $this->hasUnsentItems ? 'Hacer pedido · $' . number_format($this->pendingBatchTotal, 2) : 'Sin ítems por pedir' }}
                        </span>
                    </button>
                    <style>
                        @keyframes ctaGlow {
                            0%, 100% { box-shadow: 0 4px 14px 0 rgba(16, 185, 129, 0.35); }
                            50%      { box-shadow: 0 6px 20px 4px rgba(16, 185, 129, 0.5); }
                        }
                    </style>
                    @endif

                    {{-- CTA: Cobrar --}}
                    @if(!$useStations || !$this->hasUnsentItems)
                    <button wire:click="openPaymentModal"
                        @class(['w-full px-4 py-3 text-sm font-bold text-white rounded-xl transition-all inline-flex items-center justify-center gap-2',
                            'bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:shadow-xl hover:shadow-purple-500/30 hover:-translate-y-0.5 cursor-pointer' => count($cart) > 0,
                            'bg-slate-200 text-slate-400 cursor-not-allowed' => count($cart) === 0])
                        @disabled(count($cart) === 0)>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Cobrar ${{ number_format($total, 2) }}
                    </button>
                    @endif
                </div>
            </div>
            {{-- END LEFT PANEL --}}


            {{-- ── RIGHT: Catalog panel ────────────────────────────────────── --}}
            <div class="w-3/5 flex flex-col bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

                {{-- Search + category filter --}}
                <div class="px-4 py-3 border-b border-slate-100 space-y-2 flex-shrink-0">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                        </svg>
                        <input wire:model.live.debounce.300ms="productSearch" type="text"
                            placeholder="Buscar productos o ingredientes..."
                            class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    </div>

                    {{-- Category pills --}}
                    @if($categories->count() > 0)
                    <div class="flex gap-1.5 flex-wrap">
                        <button wire:click="selectCategory(null)"
                            class="px-3 py-1 rounded-full text-xs font-medium transition-colors cursor-pointer
                                {{ is_null($selectedCategoryId) ? 'bg-[#ff7261] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            Todo
                        </button>
                        @foreach($categories as $cat)
                        <button wire:click="selectCategory({{ $cat->id }})"
                            class="px-3 py-1 rounded-full text-xs font-medium transition-colors cursor-pointer
                                {{ $selectedCategoryId == $cat->id ? 'bg-[#ff7261] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            {{ $cat->name }}
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Product/Ingredient grid (scrollable) --}}
                <div class="flex-1 overflow-y-auto p-3">
                    @if($sellableItems->count() > 0)
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                        @foreach($sellableItems as $item)
                        <button
                            wire:click="{{ $item['type'] === 'ingredient' ? 'addIngredientToCart(' . $item['id'] . ')' : 'addProductToCart(' . $item['id'] . ')' }}"
                            class="flex flex-col items-center text-center p-3 rounded-xl border border-slate-200 hover:border-[#ff7261] hover:shadow-md hover:bg-orange-50/30 hover:-translate-y-0.5 transition-all duration-150 group cursor-pointer">

                            {{-- Image or icon --}}
                            <div class="w-14 h-14 rounded-xl overflow-hidden mb-2 flex items-center justify-center
                                {{ $item['type'] === 'ingredient' ? 'bg-amber-50' : 'bg-slate-50' }}">
                                @if($item['image'])
                                <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['name'] }}"
                                    class="w-full h-full object-cover" loading="lazy">
                                @elseif($item['type'] === 'ingredient')
                                <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                @else
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                @endif
                            </div>

                            {{-- Name --}}
                            <p class="text-xs font-medium text-slate-700 group-hover:text-slate-900 leading-tight line-clamp-2 mb-1">
                                {{ $item['name'] }}
                            </p>

                            {{-- Type badge --}}
                            @if($item['type'] === 'ingredient')
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 mb-1">Ingrediente</span>
                            @elseif(!empty($item['has_groups']))
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-purple-100 text-purple-700 mb-1 flex items-center gap-0.5">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                                Personalizable
                            </span>
                            @endif

                            {{-- Price --}}
                            @if(!empty($item['has_groups']))
                                <p class="text-sm font-bold text-slate-400">Sin precio</p>
                            @else
                                <p class="text-sm font-bold text-[#ff7261]">${{ number_format($item['price'], 2) }}</p>
                            @endif

                            {{-- Stock badge --}}
                            @if($item['manages_inventory'] && $item['stock'] !== null && $item['stock'] <= 5)
                            <span class="text-[10px] text-amber-600 mt-0.5">Stock: {{ $item['stock'] }}</span>
                            @endif
                        </button>
                        @endforeach
                    </div>
                    @else
                    <div class="flex flex-col items-center justify-center py-16 text-slate-400">
                        <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm font-medium text-slate-500">No se encontraron productos</p>
                        <p class="text-xs mt-1">Prueba borrando los filtros</p>
                    </div>
                    @endif
                </div>
            </div>
            {{-- END RIGHT PANEL --}}

        </div>
        {{-- END split panel --}}

    </div>
    {{-- END VISTA ORDEN --}}
    @endif


    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    {{-- MODAL: Cobrar                                                           --}}
    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    @if($showPaymentModal)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl">

                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">Cobrar pedido</h3>
                        <p class="text-sm text-slate-500 mt-0.5">{{ $selectedMesaName }} · Total: <span class="font-semibold text-slate-800">${{ number_format($total, 2) }}</span></p>
                    </div>

                    <div class="px-6 py-4 space-y-4">

                        {{-- Payment rows --}}
                        @foreach($payments as $i => $payment)
                        <div class="flex gap-2 items-center">
                            <select wire:model="payments.{{ $i }}.method_id"
                                class="flex-1 px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] bg-white">
                                <option value="">Método de pago...</option>
                                @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                            <div class="relative w-32">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">$</span>
                                <input wire:model="payments.{{ $i }}.amount" type="number" step="0.01" min="0"
                                    class="w-full pl-7 pr-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                            @if(count($payments) > 1)
                            <button wire:click="removePaymentMethod({{ $i }})"
                                class="p-2 text-slate-400 hover:text-red-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            @endif
                        </div>
                        @endforeach

                        {{-- Add payment method --}}
                        <button wire:click="addPaymentMethod"
                            class="w-full py-2 text-sm text-[#ff7261] hover:text-[#e05a4a] font-medium border border-dashed border-[#ff7261]/40 hover:border-[#ff7261] rounded-xl transition-colors">
                            + Agregar método de pago
                        </button>

                        {{-- Change/Pending summary --}}
                        <div class="bg-slate-50 rounded-xl p-3 space-y-1.5 text-sm">
                            <div class="flex justify-between text-slate-600">
                                <span>Total a cobrar</span>
                                <span class="font-semibold">${{ number_format($total, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>Recibido</span>
                                <span class="font-semibold">${{ number_format($totalReceived, 2) }}</span>
                            </div>
                            @if($pendingAmount > 0)
                            <div class="flex justify-between text-red-600 font-semibold pt-1 border-t border-slate-200">
                                <span>Pendiente</span>
                                <span>${{ number_format($pendingAmount, 2) }}</span>
                            </div>
                            @else
                            <div class="flex justify-between text-green-600 font-semibold pt-1 border-t border-slate-200">
                                <span>Cambio</span>
                                <span>${{ number_format($change, 2) }}</span>
                            </div>
                            @endif
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notas (opcional)</label>
                            <input wire:model="paymentNotes" type="text" placeholder="Ej: pagó con tarjeta corporativa..."
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="closePaymentModal"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="processPayment"
                            @class(['px-4 py-2 text-sm font-bold text-white rounded-xl transition-opacity',
                                'bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:opacity-90' => $pendingAmount <= 0,
                                'bg-slate-300 cursor-not-allowed' => $pendingAmount > 0])
                            @disabled($pendingAmount > 0)>
                            Procesar pago
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif


    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    {{-- MODAL: Cancelar cuenta confirm                                          --}}
    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    @if($showCancelConfirm)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-5">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">¿Cancelar cuenta?</h3>
                                <p class="text-sm text-slate-500 mt-1">
                                    Se cancelará la cuenta de <span class="font-semibold text-slate-800">{{ $selectedMesaName }}</span>
                                    y se liberará la mesa. Esta acción no se puede deshacer.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('showCancelConfirm', false)"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                            No, volver
                        </button>
                        <button wire:click="cancelarCuenta"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors">
                            Sí, cancelar cuenta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif


    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    {{-- MODAL: Notas del ítem                                                  --}}
    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    @if($showNotesModal)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">Nota para el ítem</h3>
                        <p class="text-sm text-slate-500 mt-0.5">Instrucciones especiales para cocina / bar</p>
                    </div>
                    <div class="px-6 py-4">
                        <textarea wire:model="notesText" rows="3"
                            placeholder="Ej: sin cebolla, término medio, extra picante..."
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] resize-none"></textarea>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="closeNotesModal"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="saveNotes"
                            class="px-4 py-2 text-sm font-bold text-white rounded-xl bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:opacity-90 transition-opacity">
                            Guardar nota
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif


    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    {{-- MODAL: Cambiar mesa                                                     --}}
    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    @if($showChangeMesaModal)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">Cambiar mesa</h3>
                        <p class="text-sm text-slate-500 mt-0.5">Mover la cuenta de <span class="font-semibold text-slate-700">{{ $selectedMesaName }}</span> a:</p>
                    </div>
                    <div class="px-6 py-4">
                        @if($libreMesas->count() > 0)
                        <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto">
                            @foreach($libreMesas as $m)
                            <button wire:click="$set('targetMesaId', {{ $m->id }})"
                                class="flex flex-col items-center justify-center p-3 rounded-xl border-2 transition-all text-sm font-medium
                                    {{ $targetMesaId == $m->id ? 'border-[#ff7261] bg-red-50 text-[#ff7261]' : 'border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50' }}">
                                <span class="font-bold">{{ $m->name }}</span>
                                @if($m->sector)
                                <span class="text-xs text-slate-400 mt-0.5">{{ $m->sector->name }}</span>
                                @endif
                            </button>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-6 text-slate-400">
                            <p class="text-sm">No hay mesas libres disponibles</p>
                        </div>
                        @endif
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="closeChangeMesaModal"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="confirmChangeMesa"
                            @class(['px-4 py-2 text-sm font-bold text-white rounded-xl transition-opacity',
                                'bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:opacity-90' => $targetMesaId,
                                'bg-slate-300 cursor-not-allowed' => !$targetMesaId])
                            @disabled(!$targetMesaId)>
                            Confirmar cambio
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif


    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    {{-- MODAL: Dividir cuenta                                                   --}}
    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    @if($showSplitModal)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">Dividir cuenta</h3>
                        <p class="text-sm text-slate-500 mt-0.5">Selecciona las cantidades a cobrar en este pago parcial</p>
                    </div>
                    <div class="px-6 py-4 space-y-4 max-h-[60vh] overflow-y-auto">

                        {{-- Items table --}}
                        <div class="space-y-2">
                            @foreach($cart as $item)
                            <div class="flex items-center gap-3 py-2 border-b border-slate-100 last:border-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-800 truncate">{{ $item['name'] }}</p>
                                    <p class="text-xs text-slate-500">
                                        ${{ number_format($item['unit_price'] + ($item['tax_rate'] > 0 ? $item['unit_price'] * $item['tax_rate'] / 100 : 0), 2) }} c/u
                                        · Disponible: {{ (int) $item['quantity'] }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <button
                                        x-data
                                        @click="$wire.splitQty[{{ $item['cuenta_item_id'] }}] = Math.max(0, ($wire.splitQty[{{ $item['cuenta_item_id'] }}] || 0) - 1)"
                                        class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 font-bold text-base transition-colors">−</button>
                                    <span class="w-8 text-center text-sm font-semibold text-slate-800">
                                        {{ $splitQty[$item['cuenta_item_id']] ?? 0 }}
                                    </span>
                                    <button
                                        x-data
                                        @click="$wire.splitQty[{{ $item['cuenta_item_id'] }}] = Math.min({{ (int) $item['quantity'] }}, ($wire.splitQty[{{ $item['cuenta_item_id'] }}] || 0) + 1)"
                                        class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 font-bold text-base transition-colors">+</button>
                                </div>
                                <div class="text-sm font-semibold text-slate-700 w-16 text-right flex-shrink-0">
                                    @php
                                        $sqty = $splitQty[$item['cuenta_item_id']] ?? 0;
                                        $slineTotal = round(($item['unit_price'] + ($item['tax_rate'] > 0 ? $item['unit_price'] * $item['tax_rate'] / 100 : 0)) * $sqty, 2);
                                    @endphp
                                    ${{ number_format($slineTotal, 2) }}
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- Split payments --}}
                        <div class="space-y-2 pt-2 border-t border-slate-200">
                            <p class="text-sm font-semibold text-slate-700">Pago</p>
                            @foreach($splitPayments as $i => $sp)
                            <div class="flex gap-2 items-center">
                                <select wire:model="splitPayments.{{ $i }}.method_id"
                                    class="flex-1 px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] bg-white">
                                    <option value="">Método de pago...</option>
                                    @foreach($paymentMethods as $method)
                                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                                    @endforeach
                                </select>
                                <div class="relative w-28">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">$</span>
                                    <input wire:model="splitPayments.{{ $i }}.amount" type="number" step="0.01" min="0"
                                        class="w-full pl-7 pr-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                                @if(count($splitPayments) > 1)
                                <button wire:click="removeSplitPaymentMethod({{ $i }})"
                                    class="p-2 text-slate-400 hover:text-red-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                @endif
                            </div>
                            @endforeach
                            <button wire:click="addSplitPaymentMethod"
                                class="w-full py-2 text-sm text-[#ff7261] hover:text-[#e05a4a] font-medium border border-dashed border-[#ff7261]/40 hover:border-[#ff7261] rounded-xl transition-colors">
                                + Agregar método
                            </button>
                        </div>

                        {{-- Summary --}}
                        <div class="bg-slate-50 rounded-xl p-3 flex justify-between items-center">
                            <span class="text-sm font-semibold text-slate-700">Total a cobrar:</span>
                            <span class="text-lg font-bold text-[#ff7261]">${{ number_format($splitTotal, 2) }}</span>
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notas (opcional)</label>
                            <input wire:model="splitNotes" type="text" placeholder="Ej: cobro parcial mesa 5..."
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="closeSplitModal"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="processSplitPayment"
                            class="px-4 py-2 text-sm font-bold text-white rounded-xl bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:opacity-90 transition-opacity">
                            Cobrar ${{ number_format($splitTotal, 2) }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif


    {{-- ──────────────────────────────────────────────────────────────────────────── --}}
    {{-- MODAL: Selección de Ingredientes por Grupo                                --}}
    {{-- ──────────────────────────────────────────────────────────────────────────── --}}
    @if($showIngredientGroupModal)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeIngredientGroupModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">

                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-purple-50 to-white rounded-t-2xl">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                </svg>
                            </div>
                            <div>
                                @php
                                    $pendingProduct = $pendingProductId ? \App\Models\Product::find($pendingProductId) : null;
                                @endphp
                                <h3 class="text-lg font-bold text-slate-900">Personalizar pedido</h3>
                                <p class="text-sm text-slate-500">{{ $pendingProduct?->name ?? 'Producto' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Groups --}}
                    <div class="px-6 py-4 space-y-5 max-h-[60vh] overflow-y-auto">
                        @foreach($ingredientGroupsData as $group)
                        <div>
                            <label class="flex items-center justify-between text-sm font-bold text-slate-700 mb-2">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md bg-purple-100 text-purple-600 flex items-center justify-center text-[10px] font-black">{{ $loop->iteration }}</span>
                                    {{ $group['name'] }}
                                </span>
                                @if(isset($group['price']) && $group['price'] !== null)
                                    <span class="text-sm font-black text-[#ff7261]">${{ number_format($group['price'], 2) }}</span>
                                @endif
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($group['ingredients'] as $ing)
                                @php
                                    $isSelected = isset($selectedGroupIngredients[$group['id']]) && $selectedGroupIngredients[$group['id']] == $ing['id'];
                                    $noStock = $ing['manage_inventory'] && $ing['stock'] < 1;
                                @endphp
                                <button
                                    wire:click="selectSingleGroupIngredient({{ $group['id'] }}, {{ $ing['id'] }})"
                                    @disabled($noStock)
                                    @class([
                                        'relative flex items-center gap-2 px-3 py-2.5 rounded-xl border-2 text-left transition-all duration-150 cursor-pointer',
                                        'border-purple-500 bg-purple-50 ring-2 ring-purple-500/30 shadow-md' => $isSelected && !$noStock,
                                        'border-slate-200 hover:border-purple-300 hover:bg-purple-50/30' => !$isSelected && !$noStock,
                                        'border-slate-100 bg-slate-50 opacity-50 cursor-not-allowed' => $noStock,
                                    ])>
                                    {{-- Selection indicator --}}
                                    <div @class([
                                        'w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors',
                                        'border-purple-500 bg-purple-500' => $isSelected,
                                        'border-slate-300' => !$isSelected,
                                    ])>
                                        @if($isSelected)
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-800 truncate">{{ $ing['name'] }}</p>
                                        @if($noStock)
                                        <p class="text-[10px] text-red-500 font-semibold">Agotado</p>
                                        @endif
                                    </div>
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-2xl flex justify-end gap-3">
                        <button wire:click="closeIngredientGroupModal"
                            class="px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors cursor-pointer">
                            Cancelar
                        </button>
                        <button wire:click="confirmIngredientSelection"
                            class="px-5 py-2.5 text-sm font-bold text-white rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 hover:shadow-lg hover:shadow-purple-500/30 transition-all cursor-pointer inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Agregar al pedido
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif


    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    {{-- MODAL: Quitar ítem ya pedido (con reversión de stock)                   --}}
    {{-- ──────────────────────────────────────────────────────────────────────── --}}
    @if($showRemoveSentModal && $removeSentIdx !== null && isset($cart[$removeSentIdx]))
    @php $removingItem = $cart[$removeSentIdx]; @endphp
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeRemoveSentModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">

                    {{-- Red warning header --}}
                    <div class="bg-gradient-to-r from-red-500 to-rose-600 px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">Quitar ítem ya pedido</h3>
                                <p class="text-red-100 text-xs mt-0.5">Esta acción cancela la comanda en cocina y devuelve el stock</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-5 space-y-4">

                        {{-- Item summary --}}
                        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-bold text-sm">
                                    {{ rtrim(rtrim(number_format((float)$removingItem['quantity'], 3), '0'), '.') }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-900 truncate">{{ $removingItem['name'] }}</p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    @if(!empty($removingItem['station_name']))
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full font-semibold text-white"
                                        style="background: {{ $removingItem['station_color'] ?? '#6b7280' }};">
                                        {{ $removingItem['station_icon'] ?? '' }} {{ $removingItem['station_name'] }}
                                    </span>
                                    @endif
                                    <span class="text-xs font-bold text-red-600">
                                        −${{ number_format($removingItem['subtotal'] + $removingItem['tax_amount'], 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- What will happen --}}
                        <div class="space-y-2 text-sm">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span class="text-slate-600">La comanda en cocina será <strong>cancelada</strong></span>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                                <span class="text-slate-600">El stock será <strong>devuelto al inventario</strong></span>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>
                                <span class="text-slate-600">El ítem se <strong>eliminará de la cuenta</strong> y del total</span>
                            </div>
                        </div>

                        {{-- Optional reason --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Motivo <span class="text-slate-400 font-normal">(opcional)</span>
                            </label>
                            <input wire:model="removeSentReason" type="text"
                                placeholder="Ej: el cliente cambió de opinión, error de pedido…"
                                class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-red-400/50 focus:border-red-400 transition-colors"
                                maxlength="120">
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="closeRemoveSentModal"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors cursor-pointer">
                            Cancelar
                        </button>
                        <button wire:click="removeSentItem"
                            class="px-4 py-2 text-sm font-bold text-white bg-gradient-to-r from-red-500 to-rose-600 rounded-xl hover:from-red-600 hover:to-rose-700 transition-all shadow-sm shadow-red-500/30 cursor-pointer inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Quitar ítem
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- JS: listen for openWindow + print-receipt events dispatched by Livewire --}}
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('openWindow', ({ url }) => {
            window.open(url, '_blank');
        });

        Livewire.on('print-receipt', (event) => {
            const saleId = Array.isArray(event) ? event[0]?.saleId : event.saleId;
            if (!saleId) return;
            const printWindow = window.open(
                `/receipt/${saleId}?print=auto`,
                'receipt_' + saleId,
                'width=350,height=600,scrollbars=yes,resizable=yes'
            );
            if (printWindow) printWindow.focus();
        });
    });
</script>
