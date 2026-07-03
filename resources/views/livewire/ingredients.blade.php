<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Ingredientes</h1>
            <p class="text-slate-500 mt-1">Gestiona los ingredientes y grupos para el restaurante</p>
        </div>
        @if($activeTab === 'ingredients' && auth()->user()->hasPermission('ingredients.create'))
        <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo Ingrediente
        </button>
        @endif
        @if($activeTab === 'groups' && auth()->user()->hasPermission('ingredient_groups.create'))
        <button wire:click="createGroup" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo Grupo
        </button>
        @endif
    </div>

    {{-- Tab Switcher --}}
    <div class="flex gap-1 bg-slate-100 p-1 rounded-xl w-fit">
        <button wire:click="switchTab('ingredients')"
            class="px-5 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ $activeTab === 'ingredients' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                Ingredientes
            </span>
        </button>
        <button wire:click="switchTab('groups')"
            class="px-5 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ $activeTab === 'groups' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Grupos
            </span>
        </button>
    </div>

    {{-- ===================== TAB: INGREDIENTES ===================== --}}
    @if($activeTab === 'ingredients')

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text"
                class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm"
                placeholder="Buscar ingrediente...">
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Nombre</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Unidad</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Stock</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">P. Compra</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">P. Venta</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($ingredients as $item)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-slate-900">{{ $item->name }}</div>
                            <div class="flex gap-1 mt-1">
                                @if($item->manage_inventory)
                                <span class="text-xs px-1.5 py-0.5 bg-blue-100 text-blue-600 rounded-md">Inventario</span>
                                @endif
                                @if($item->show_in_pos)
                                <span class="text-xs px-1.5 py-0.5 bg-green-100 text-green-600 rounded-md">POS</span>
                                @endif
                                @if($item->includes_tax)
                                <span class="text-xs px-1.5 py-0.5 bg-amber-100 text-amber-600 rounded-md">{{ $item->tax ? $item->tax->name : '+IVA' }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">
                            {{ $item->unit?->abbreviation ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            @if($item->manage_inventory && $item->stock !== null)
                                <span class="font-medium text-slate-900">{{ number_format($item->stock, 2) }}</span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            @if($item->purchase_price !== null)
                                <span class="font-medium text-slate-900">{{ number_format($item->purchase_price, 2) }}</span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            @if($item->show_in_pos && $item->sale_price !== null)
                                <span class="font-medium text-slate-900">{{ number_format($item->sale_price, 2) }}</span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(auth()->user()->hasPermission('ingredients.edit'))
                            <button wire:click="toggleStatus({{ $item->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $item->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $item->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $item->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $item->is_active ? 'Activo' : 'Inactivo' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('ingredients.edit'))
                                <button wire:click="edit({{ $item->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('ingredients.delete'))
                                <button wire:click="confirmDelete({{ $item->id }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500">No hay ingredientes registrados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($ingredients->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">{{ $ingredients->links() }}</div>
        @endif
    </div>

    @endif
    {{-- ===================== END TAB: INGREDIENTES ===================== --}}

    {{-- ===================== TAB: GRUPOS ===================== --}}
    @if($activeTab === 'groups')

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input wire:model.live.debounce.300ms="searchGroup" type="text"
                class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm"
                placeholder="Buscar grupo...">
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Nombre</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Ingredientes</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($groups as $group)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $group->name }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-purple-100 text-purple-700">
                                {{ $group->ingredients_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(auth()->user()->hasPermission('ingredient_groups.edit'))
                            <button wire:click="toggleGroupStatus({{ $group->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $group->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $group->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $group->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $group->is_active ? 'Activo' : 'Inactivo' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('ingredient_groups.edit'))
                                <button wire:click="editGroup({{ $group->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('ingredient_groups.delete'))
                                <button wire:click="confirmDeleteGroup({{ $group->id }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-slate-500">No hay grupos registrados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($groups->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">{{ $groups->links() }}</div>
        @endif
    </div>

    @endif
    {{-- ===================== END TAB: GRUPOS ===================== --}}

    {{-- ===================== MODAL: CREAR / EDITAR INGREDIENTE ===================== --}}
    @if($isModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl"
                    x-data="{ manageInv: @js($manage_inventory), showInPos: @js($show_in_pos) }">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">{{ $itemId ? 'Editar' : 'Nuevo' }} Ingrediente</h3>
                    </div>
                    <div class="px-6 py-4 space-y-4 max-h-[75vh] overflow-y-auto">

                        {{-- Nombre --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                            <input wire:model="name" type="text"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all"
                                placeholder="Ej: Tomate, Queso mozzarella">
                            @error('name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>

                        {{-- Opciones --}}
                        <div class="rounded-xl border border-slate-200 p-4 space-y-3">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Opciones</p>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox"
                                    wire:model="manage_inventory"
                                    x-model="manageInv"
                                    class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                <div>
                                    <span class="text-sm font-medium text-slate-700">Gestionar inventario</span>
                                    <p class="text-xs text-slate-400">Lleva control de stock para este ingrediente</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox"
                                    wire:model="show_in_pos"
                                    x-model="showInPos"
                                    class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                <div>
                                    <span class="text-sm font-medium text-slate-700">Mostrar en POS</span>
                                    <p class="text-xs text-slate-400">Visible para el cajero en el punto de venta</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input wire:model="is_active" type="checkbox"
                                    class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                <span class="text-sm font-medium text-slate-700">Activo</span>
                            </label>
                        </div>

                        {{-- Estación de Preparación --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Estación de Preparación</label>
                            <select wire:model="preparationStationId"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] bg-white">
                                <option value="">Sin estación asignada</option>
                                @foreach($preparationStations as $ps)
                                <option value="{{ $ps->id }}">{{ $ps->icon }} {{ $ps->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Sección Inventario: se muestra si manage_inventory --}}
                        <div x-show="manageInv" x-cloak
                            class="rounded-xl border border-blue-200 bg-blue-50/50 p-4 space-y-3">
                            <p class="text-xs font-semibold text-blue-500 uppercase tracking-wide">Inventario</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Unidad de medida</label>
                                    <select wire:model="unit_id"
                                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all text-sm bg-white">
                                        <option value="">— Seleccionar —</option>
                                        @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                                        @endforeach
                                    </select>
                                    @error('unit_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Stock actual</label>
                                    <input wire:model="stock" type="number" step="0.01" min="0"
                                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all text-sm"
                                        placeholder="0.00">
                                    @error('stock')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        {{-- Sección POS: se muestra si show_in_pos --}}
                        <div x-show="showInPos" x-cloak
                            class="rounded-xl border border-green-200 bg-green-50/50 p-4 space-y-3">
                            <p class="text-xs font-semibold text-green-600 uppercase tracking-wide">Punto de Venta</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Precio de compra</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">$</span>
                                        <input wire:model="purchase_price" type="number" step="0.01" min="0"
                                            class="w-full pl-7 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all text-sm"
                                            placeholder="0.00">
                                    </div>
                                    @error('purchase_price')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Precio de venta</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">$</span>
                                        <input wire:model="sale_price" type="number" step="0.01" min="0"
                                            class="w-full pl-7 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all text-sm"
                                            placeholder="0.00">
                                    </div>
                                    @error('sale_price')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input wire:model="includes_tax" type="checkbox"
                                    class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                <div>
                                    <span class="text-sm font-medium text-slate-700">Incluye impuesto</span>
                                    <p class="text-xs text-slate-400">El precio de venta ya incluye el impuesto</p>
                                </div>
                            </label>
                            <div x-data="{ includesTax: @entangle('includes_tax') }" x-show="includesTax" x-cloak>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de impuesto *</label>
                                <select wire:model="tax_id"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all text-sm bg-white">
                                    <option value="">— Seleccionar impuesto —</option>
                                    @foreach($taxes as $tax)
                                    <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->value }}%)</option>
                                    @endforeach
                                </select>
                                @error('tax_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Categoría POS</label>
                                <select wire:model="category_id"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all text-sm bg-white">
                                    <option value="">— Sin categoría —</option>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        {{-- Sección NO POS: solo precio compra + unidad (cuando NO muestra en POS) --}}
                        <div x-show="!showInPos" x-cloak
                            class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Costos</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Precio de compra</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">$</span>
                                        <input wire:model="purchase_price" type="number" step="0.01" min="0"
                                            class="w-full pl-7 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all text-sm"
                                            placeholder="0.00">
                                    </div>
                                    @error('purchase_price')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                </div>
                                <div x-show="!manageInv">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Unidad de medida</label>
                                    <select wire:model="unit_id"
                                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all text-sm bg-white">
                                        <option value="">— Seleccionar —</option>
                                        @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                                        @endforeach
                                    </select>
                                    @error('unit_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isModalOpen', false)"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="store"
                            class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ===================== MODAL: ELIMINAR INGREDIENTE ===================== --}}
    @if($isDeleteModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Ingrediente</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro? Esta acción no se puede deshacer.</p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('isDeleteModalOpen', false)"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button wire:click="delete"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ===================== MODAL: CREAR / EDITAR GRUPO ===================== --}}
    @if($isGroupModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isGroupModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">{{ $groupId ? 'Editar' : 'Nuevo' }} Grupo de Ingredientes</h3>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre del grupo *</label>
                            <input wire:model="groupName" type="text"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all"
                                placeholder="Ej: Salsas, Toppings, Acompañamientos">
                            @error('groupName')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model="groupIsActive" type="checkbox"
                                class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                            <span class="text-sm font-medium text-slate-700">Activo</span>
                        </label>
                        <div x-data="{ selected: $wire.entangle('selectedIngredients') }">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Ingredientes del grupo</label>
                            @if($allIngredients->isEmpty())
                                <p class="text-sm text-slate-400 italic">No hay ingredientes activos disponibles. Crea ingredientes primero.</p>
                            @else
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <div class="max-h-52 overflow-y-auto divide-y divide-slate-100">
                                    @foreach($allIngredients as $ingredient)
                                    <label class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 cursor-pointer transition-colors">
                                        <input type="checkbox"
                                            x-model="selected"
                                            value="{{ $ingredient->id }}"
                                            class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                        <span class="text-sm text-slate-700">{{ $ingredient->name }}</span>
                                        <div class="ml-auto flex gap-1">
                                            @if($ingredient->manage_inventory)
                                            <span class="text-xs px-1.5 py-0.5 bg-blue-100 text-blue-600 rounded-md">Inventario</span>
                                            @endif
                                            @if($ingredient->show_in_pos)
                                            <span class="text-xs px-1.5 py-0.5 bg-green-100 text-green-600 rounded-md">POS</span>
                                            @endif
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            <p class="text-xs text-slate-400 mt-1" x-text="selected.length + ' seleccionado(s)'"></p>
                            @endif
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isGroupModalOpen', false)"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="storeGroup"
                            class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ===================== MODAL: ELIMINAR GRUPO ===================== --}}
    @if($isGroupDeleteModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isGroupDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Grupo</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro? Los ingredientes asignados no serán eliminados, solo se desvinculan del grupo.</p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('isGroupDeleteModalOpen', false)"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button wire:click="deleteGroup"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
