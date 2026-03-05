<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Zonas y Mesas</h1>
            <p class="text-slate-500 mt-1">Gestiona las zonas y mesas del restaurante</p>
        </div>
        @if(auth()->user()->hasPermission('zones_tables.create'))
        <button wire:click="{{ $activeTab === 'zones' ? 'createZone' : 'createTable' }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            {{ $activeTab === 'zones' ? 'Nueva Zona' : 'Nueva Mesa' }}
        </button>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-1 inline-flex gap-1">
        <button wire:click="switchTab('zones')" class="px-4 py-2 text-sm font-medium rounded-xl transition-all {{ $activeTab === 'zones' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
            <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            Zonas
        </button>
        <button wire:click="switchTab('tables')" class="px-4 py-2 text-sm font-medium rounded-xl transition-all {{ $activeTab === 'tables' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
            <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Mesas
        </button>
    </div>

    {{-- Search & Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            @if($needsBranchSelection)
            <select wire:model.live="filterBranch" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[160px]">
                <option value="">Seleccionar sucursal...</option>
                @foreach($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
            @endif
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar {{ $activeTab === 'zones' ? 'zona' : 'mesa' }}...">
            </div>
            @if($activeTab === 'tables')
            <select wire:model.live="filterZone" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                <option value="">Todas las zonas</option>
                @foreach($allZones as $zone)
                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                @endforeach
            </select>
            @endif
        </div>
        @if($needsBranchSelection && !$filterBranch)
        <div class="mt-3 bg-amber-50 border border-amber-200 rounded-xl p-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span class="text-sm text-amber-700">Selecciona una sucursal para ver y gestionar zonas y mesas</span>
        </div>
        @endif
    </div>

    {{-- ═══════════ ZONES TAB ═══════════ --}}
    @if($activeTab === 'zones')
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Color</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Nombre</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Descripción</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Mesas</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($zones as $zone)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="w-8 h-8 rounded-lg border border-slate-200" style="background-color: {{ $zone->color ?? '#6366f1' }}"></div>
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $zone->name }}</td>
                        <td class="px-6 py-4 text-slate-500 text-sm">{{ Str::limit($zone->description, 50) ?: '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-700">{{ $zone->tables_count }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(auth()->user()->hasPermission('zones_tables.edit'))
                            <button wire:click="toggleZoneStatus({{ $zone->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $zone->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $zone->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $zone->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $zone->is_active ? 'Activo' : 'Inactivo' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('zones_tables.edit'))
                                <button wire:click="editZone({{ $zone->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                @endif
                                @if(auth()->user()->hasPermission('zones_tables.delete'))
                                <button wire:click="confirmDeleteZone({{ $zone->id }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">No hay zonas registradas</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($zones->hasPages())<div class="px-6 py-4 border-t border-slate-200">{{ $zones->links() }}</div>@endif
    </div>
    @endif

    {{-- ═══════════ TABLES TAB ═══════════ --}}
    @if($activeTab === 'tables')
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Mesa</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Zona</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Capacidad</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado Actual</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Activo</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($tables as $table)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $table->name }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $table->zone->color ?? '#6366f1' }}"></div>
                                <span class="text-slate-600 text-sm">{{ $table->zone->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center gap-1 text-sm text-slate-600">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                {{ $table->capacity }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $table->status === 'available' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $table->status === 'occupied' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $table->status === 'reserved' ? 'bg-yellow-100 text-yellow-700' : '' }}
                            ">{{ $table->status_label }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(auth()->user()->hasPermission('zones_tables.edit'))
                            <button wire:click="toggleTableStatus({{ $table->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $table->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $table->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $table->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $table->is_active ? 'Activo' : 'Inactivo' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('zones_tables.edit'))
                                <button wire:click="editTable({{ $table->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                @endif
                                @if(auth()->user()->hasPermission('zones_tables.delete'))
                                <button wire:click="confirmDeleteTable({{ $table->id }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">No hay mesas registradas</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tables->hasPages())<div class="px-6 py-4 border-t border-slate-200">{{ $tables->links() }}</div>@endif
    </div>
    @endif

    {{-- ═══════════ ZONE MODAL ═══════════ --}}
    @if($isZoneModalOpen)
    <div class="relative z-[100]"><div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isZoneModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto"><div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900">{{ $zoneId ? 'Editar' : 'Nueva' }} Zona</h3>
                    <button wire:click="$set('isZoneModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                        <input wire:model="zoneName" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: Terraza, Salón Principal">
                        @error('zoneName')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                        <textarea wire:model="zoneDescription" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Descripción opcional"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Color identificador</label>
                        <div class="flex items-center gap-3">
                            <input wire:model="zoneColor" type="color" class="w-10 h-10 rounded-lg border border-slate-300 cursor-pointer p-0.5">
                            <input wire:model="zoneColor" type="text" class="flex-1 px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm font-mono" placeholder="#6366f1">
                        </div>
                        @error('zoneColor')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input wire:model="zoneIsActive" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                        <span class="text-sm text-slate-700">Activa</span>
                    </label>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                    <button wire:click="$set('isZoneModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                    <button wire:click="storeZone" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl">Guardar</button>
                </div>
            </div>
        </div></div>
    </div>
    @endif

    {{-- ═══════════ TABLE MODAL ═══════════ --}}
    @if($isTableModalOpen)
    <div class="relative z-[100]"><div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isTableModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto"><div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900">{{ $tableId ? 'Editar' : 'Nueva' }} Mesa</h3>
                    <button wire:click="$set('isTableModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Zona *</label>
                        <select wire:model="tableZoneId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            <option value="">Seleccionar zona...</option>
                            @foreach($allZones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                        @error('tableZoneId')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nombre / Número *</label>
                        <input wire:model="tableName" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: Mesa 1, A-01">
                        @error('tableName')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Capacidad (personas) *</label>
                        <input wire:model="tableCapacity" type="number" min="1" max="50" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="4">
                        @error('tableCapacity')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input wire:model="tableIsActive" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                        <span class="text-sm text-slate-700">Activa</span>
                    </label>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                    <button wire:click="$set('isTableModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                    <button wire:click="storeTable" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl">Guardar</button>
                </div>
            </div>
        </div></div>
    </div>
    @endif

    {{-- ═══════════ DELETE ZONE MODAL ═══════════ --}}
    @if($isZoneDeleteModalOpen)
    <div class="relative z-[100]"><div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isZoneDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto"><div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4"><svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg></div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Zona</h3>
                <p class="text-slate-500 mb-6">¿Estás seguro? Esta acción no se puede deshacer.</p>
                <div class="flex justify-center gap-3">
                    <button wire:click="$set('isZoneDeleteModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                    <button wire:click="deleteZone" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">Eliminar</button>
                </div>
            </div>
        </div></div>
    </div>
    @endif

    {{-- ═══════════ DELETE TABLE MODAL ═══════════ --}}
    @if($isTableDeleteModalOpen)
    <div class="relative z-[100]"><div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isTableDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto"><div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4"><svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg></div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Mesa</h3>
                <p class="text-slate-500 mb-6">¿Estás seguro? Esta acción no se puede deshacer.</p>
                <div class="flex justify-center gap-3">
                    <button wire:click="$set('isTableDeleteModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                    <button wire:click="deleteTable" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">Eliminar</button>
                </div>
            </div>
        </div></div>
    </div>
    @endif
</div>
