<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Mesas</h1>
            <p class="text-slate-500 text-sm mt-1">Gestión de sectores y mesas del restaurante</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-slate-200">
        <nav class="flex gap-1">
            <button wire:click="switchTab('sectores')"
                class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition-colors
                    {{ $activeTab === 'sectores' ? 'bg-white border border-b-white border-slate-200 text-[#ff7261] -mb-px' : 'text-slate-500 hover:text-slate-700' }}">
                Sectores
            </button>
            <button wire:click="switchTab('mesas')"
                class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition-colors
                    {{ $activeTab === 'mesas' ? 'bg-white border border-b-white border-slate-200 text-[#ff7261] -mb-px' : 'text-slate-500 hover:text-slate-700' }}">
                Mesas
            </button>
        </nav>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: SECTORES --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'sectores')
    <div class="space-y-4">
        {{-- Toolbar --}}
        <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
            <div class="relative w-full sm:w-72">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input wire:model.live="searchSector" type="text" placeholder="Buscar sector..."
                    class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
            </div>
            @if(auth()->user()->hasPermission('sectors.create'))
            <button wire:click="createSector"
                class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white rounded-xl bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:opacity-90 transition-opacity whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo Sector
            </button>
            @endif
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Nombre</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Mesas</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        @if(auth()->user()->hasPermission('sectors.edit') || auth()->user()->hasPermission('sectors.delete'))
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sectores as $sector)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-slate-900">{{ $sector->name }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-purple-100 text-purple-700">
                                    {{ $sector->mesas_count }} {{ $sector->mesas_count === 1 ? 'mesa' : 'mesas' }}
                                </span>
                                @if($sector->mesas_ocupadas_count > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">
                                    {{ $sector->mesas_ocupadas_count }} ocupada{{ $sector->mesas_ocupadas_count > 1 ? 's' : '' }}
                                </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(auth()->user()->hasPermission('sectors.edit'))
                            <button wire:click="toggleSectorStatus({{ $sector->id }})"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $sector->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $sector->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $sector->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $sector->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                            @endif
                        </td>
                        @if(auth()->user()->hasPermission('sectors.edit') || auth()->user()->hasPermission('sectors.delete'))
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('sectors.edit'))
                                <button wire:click="editSector({{ $sector->id }})"
                                    class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('sectors.delete'))
                                <button wire:click="confirmDeleteSector({{ $sector->id }})"
                                    class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                            <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            No se encontraron sectores
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $sectores->links() }}</div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: MESAS --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'mesas')
    <div class="space-y-4">
        {{-- Toolbar --}}
        <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <div class="relative w-full sm:w-64">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input wire:model.live="searchMesa" type="text" placeholder="Buscar mesa..."
                        class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                </div>
                <select wire:model.live="filterSector"
                    class="px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] bg-white">
                    <option value="">Todos los sectores</option>
                    @foreach($allSectores as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            @if(auth()->user()->hasPermission('mesas.create'))
            <button wire:click="createMesa"
                class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white rounded-xl bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:opacity-90 transition-opacity whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nueva Mesa
            </button>
            @endif
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Mesa</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Sector</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Disponibilidad</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        @if(auth()->user()->hasPermission('mesas.edit') || auth()->user()->hasPermission('mesas.delete'))
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($mesas as $mesa)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-slate-900">{{ $mesa->name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700">
                                {{ $mesa->sector?->name ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium
                                {{ $mesa->status === 'libre' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $mesa->status === 'libre' ? 'Libre' : 'Ocupada' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(auth()->user()->hasPermission('mesas.edit'))
                            <button wire:click="toggleMesaStatus({{ $mesa->id }})"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200
                                    {{ $mesa->isOcupada() ? 'cursor-not-allowed opacity-50 bg-[#ff7261]' : ($mesa->is_active ? 'cursor-pointer bg-[#ff7261]' : 'cursor-pointer bg-slate-200') }}"
                                {{ $mesa->isOcupada() ? 'title=No se puede desactivar: la mesa tiene una cuenta abierta' : '' }}>
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $mesa->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $mesa->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $mesa->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                            @endif
                        </td>
                        @if(auth()->user()->hasPermission('mesas.edit') || auth()->user()->hasPermission('mesas.delete'))
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('mesas.edit'))
                                <button wire:click="editMesa({{ $mesa->id }})"
                                    class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('mesas.delete'))
                                <button wire:click="confirmDeleteMesa({{ $mesa->id }})"
                                    class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                            <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18M10 4v16M14 4v16M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                            No se encontraron mesas
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $mesas->links() }}</div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: Crear / Editar Sector --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if($isSectorModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isSectorModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">{{ $sectorId ? 'Editar' : 'Nuevo' }} Sector</h3>
                    </div>
                    <div class="px-6 py-5 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                            <input wire:model="sectorName" type="text"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                placeholder="Ej: Terraza, Salón principal">
                            @error('sectorName')<span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>@enderror
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model="sectorIsActive" type="checkbox"
                                class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                            <span class="text-sm text-slate-700">Sector activo</span>
                        </label>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isSectorModalOpen', false)"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="storeSector"
                            class="px-4 py-2 text-sm font-medium text-white rounded-xl bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:opacity-90 transition-opacity">
                            {{ $sectorId ? 'Actualizar' : 'Crear' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: Advertencia Desactivar Sector --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if($isSectorDeactivateModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-5">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 mb-1">¿Desactivar sector?</h3>
                                <p class="text-sm text-slate-600">
                                    Vas a desactivar el sector <span class="font-semibold text-slate-800">{{ $sectorNameToDeactivate }}</span>.
                                </p>
                                <div class="mt-3 p-3 bg-amber-50 rounded-xl border border-amber-200 space-y-1">
                                    <p class="text-sm text-amber-800 flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Todas las mesas <strong>libres</strong> de este sector serán desactivadas.
                                    </p>
                                    <p class="text-sm text-amber-800 flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Las mesas <strong>ocupadas</strong> permanecerán sin cambios hasta que se liberen.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isSectorDeactivateModalOpen', false)"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="confirmDeactivateSector"
                            class="px-4 py-2 text-sm font-medium text-white bg-amber-500 hover:bg-amber-600 rounded-xl transition-colors">
                            Sí, desactivar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: Eliminar Sector --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if($isSectorDeleteModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-5">
                        <div class="flex items-center gap-4">
                            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Eliminar sector</h3>
                                <p class="text-sm text-slate-500 mt-1">Esta acción no se puede deshacer.</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isSectorDeleteModalOpen', false)"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="deleteSector"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: Crear / Editar Mesa --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if($isMesaModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isMesaModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">{{ $mesaId ? 'Editar' : 'Nueva' }} Mesa</h3>
                    </div>
                    <div class="px-6 py-5 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                            <input wire:model="mesaName" type="text"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                placeholder="Ej: Mesa 1, Mesa A, Barra 3">
                            @error('mesaName')<span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Sector *</label>
                            <select wire:model="mesaSectorId"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] bg-white">
                                <option value="">Seleccionar sector...</option>
                                @foreach($allSectores as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                            @error('mesaSectorId')<span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>@enderror
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model="mesaIsActive" type="checkbox"
                                class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                            <span class="text-sm text-slate-700">Mesa activa</span>
                        </label>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Capacidad (personas)</label>
                            <input wire:model="mesaCapacity" type="number" min="1" max="50"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                placeholder="4">
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isMesaModalOpen', false)"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="storeMesa"
                            class="px-4 py-2 text-sm font-medium text-white rounded-xl bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:opacity-90 transition-opacity">
                            {{ $mesaId ? 'Actualizar' : 'Crear' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: Eliminar Mesa --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if($isMesaDeleteModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-5">
                        <div class="flex items-center gap-4">
                            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Eliminar mesa</h3>
                                <p class="text-sm text-slate-500 mt-1">Esta acción no se puede deshacer.</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isMesaDeleteModalOpen', false)"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="deleteMesa"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
