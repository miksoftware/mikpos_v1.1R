<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Ingredientes</h1>
            <p class="text-slate-500 mt-1">Gestiona ingredientes y grupos intercambiables</p>
        </div>
        @if(auth()->user()->hasPermission('ingredients.create'))
        <button wire:click="{{ $activeTab === 'ingredients' ? 'create' : 'createGroup' }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            {{ $activeTab === 'ingredients' ? 'Nuevo Ingrediente' : 'Nuevo Grupo' }}
        </button>
        @endif
    </div>

    {{-- Branch selector for super_admin --}}
    @if($needsBranchSelection)
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            <select wire:model.live="branch_id" class="flex-1 px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                <option value="">Seleccionar sucursal...</option>
                @foreach($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-1 bg-slate-100 p-1 rounded-xl w-fit">
        <button wire:click="setTab('ingredients')" class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'ingredients' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
            Ingredientes
        </button>
        <button wire:click="setTab('groups')" class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'groups' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            Grupos
        </button>
    </div>

    {{-- Search & Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar {{ $activeTab === 'ingredients' ? 'ingrediente...' : 'grupo...' }}">
            </div>
            @if($activeTab === 'ingredients')
            <select wire:model.live="filterCategory" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                <option value="">Todas las categorías</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterStatus" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                <option value="">Todos los estados</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
                <option value="sale">Disponibles para venta</option>
            </select>
            @endif
        </div>
    </div>

    {{-- Ingredients Table --}}
    @if($activeTab === 'ingredients')
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">SKU</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Nombre</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Categoría</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Unidad</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Costo</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Stock</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Venta</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($items as $item)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 text-sm text-slate-500 font-mono">{{ $item->sku }}</td>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $item->name }}</td>
                        <td class="px-6 py-4 text-sm text-slate-500">{{ $item->category?->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-slate-500">{{ $item->unit?->abbreviation ?? $item->unit?->name }}</td>
                        <td class="px-6 py-4 text-sm text-slate-700 text-right">${{ number_format($item->cost, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-medium {{ $item->isLowStock() ? 'text-red-600' : 'text-green-600' }}">
                                {{ rtrim(rtrim(number_format($item->current_stock, 3), '0'), '.') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($item->available_for_sale)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">Sí</span>
                            @else
                            <span class="text-slate-400 text-xs">No</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(auth()->user()->hasPermission('ingredients.edit'))
                            <button wire:click="toggleStatus({{ $item->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $item->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $item->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $item->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $item->is_active ? 'Activo' : 'Inactivo' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('ingredients.edit'))
                                <button wire:click="edit({{ $item->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                @endif
                                @if(auth()->user()->hasPermission('ingredients.delete'))
                                <button wire:click="confirmDelete({{ $item->id }}, 'ingredient')" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-6 py-12 text-center text-slate-500">No hay ingredientes registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())<div class="px-6 py-4 border-t border-slate-200">{{ $items->links() }}</div>@endif
    </div>
    @endif

    {{-- Groups Table --}}
    @if($activeTab === 'groups')
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Nombre</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Descripción</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Opciones</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($items as $item)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $item->name }}</td>
                        <td class="px-6 py-4 text-sm text-slate-500">{{ Str::limit($item->description, 50) ?: '-' }}</td>
                        <td class="px-6 py-4 text-center"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-purple-100 text-purple-700">{{ $item->options_count }}</span></td>
                        <td class="px-6 py-4 text-center">
                            @if(auth()->user()->hasPermission('ingredients.edit'))
                            <button wire:click="toggleGroupStatus({{ $item->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $item->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $item->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $item->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $item->is_active ? 'Activo' : 'Inactivo' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="viewGroup({{ $item->id }})" class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></button>
                                @if(auth()->user()->hasPermission('ingredients.edit'))
                                <button wire:click="editGroup({{ $item->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                @endif
                                @if(auth()->user()->hasPermission('ingredients.delete'))
                                <button wire:click="confirmDelete({{ $item->id }}, 'group')" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">No hay grupos registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())<div class="px-6 py-4 border-t border-slate-200">{{ $items->links() }}</div>@endif
    </div>
    @endif

    {{-- Ingredient Modal --}}
    @if($isModalOpen)
    <div class="relative z-[100]"><div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto"><div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900">{{ $itemId ? 'Editar' : 'Nuevo' }} Ingrediente</h3>
                    <button wire:click="$set('isModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                        <input wire:model="name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: Gomitas de mango">
                        @error('name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                        <textarea wire:model="description" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Descripción opcional"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Categoría</label>
                            <select wire:model="category_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                <option value="">Sin categoría</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Unidad de medida *</label>
                            <select wire:model="unit_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                <option value="">Seleccionar...</option>
                                @foreach($units as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->abbreviation }})</option>
                                @endforeach
                            </select>
                            @error('unit_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Costo *</label>
                            <input wire:model="cost" type="number" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            @error('cost')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Impuesto</label>
                            <select wire:model="tax_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                <option value="">Sin impuesto</option>
                                @foreach($taxes as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->value }}%)</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Sale section --}}
                    <div class="border border-slate-200 rounded-xl p-4 space-y-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model.live="available_for_sale" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                            <span class="text-sm font-medium text-slate-700">Disponible para venta directa</span>
                        </label>
                        @if($available_for_sale)
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Precio de venta</label>
                                <input wire:model="sale_price" type="number" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-center gap-2 cursor-pointer pb-2">
                                    <input wire:model="price_includes_tax" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                    <span class="text-sm text-slate-700">Precio incluye IVA</span>
                                </label>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Stock --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Stock actual</label>
                            <input wire:model="current_stock" type="number" step="0.001" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Stock mínimo</label>
                            <input wire:model="min_stock" type="number" step="0.001" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Stock máximo</label>
                            <input wire:model="max_stock" type="number" step="0.001" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        </div>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input wire:model="is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                        <span class="text-sm text-slate-700">Activo</span>
                    </label>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                    <button wire:click="$set('isModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                    <button wire:click="store" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl">Guardar</button>
                </div>
            </div>
        </div></div>
    </div>
    @endif

    {{-- Group Modal --}}
    @if($isGroupModalOpen)
    <div class="relative z-[100]"><div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isGroupModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto"><div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900">{{ $groupId ? 'Editar' : 'Nuevo' }} Grupo</h3>
                    <button wire:click="$set('isGroupModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nombre del grupo *</label>
                        <input wire:model="groupName" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: Cerveza (elegir 1)">
                        @error('groupName')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                        <textarea wire:model="groupDescription" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Descripción opcional"></textarea>
                    </div>

                    {{-- Options --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-slate-700">Opciones intercambiables *</label>
                            <button wire:click="addGroupOption" type="button" class="text-xs text-[#ff7261] hover:text-[#e55a4a] font-medium flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Agregar opción
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 mb-3">Al vender un producto compuesto, se elegirá una de estas opciones.</p>
                        @error('groupOptions')<span class="text-red-500 text-sm block mb-2">{{ $message }}</span>@enderror
                        <div class="space-y-2">
                            @foreach($groupOptions as $index => $option)
                            <div class="flex items-center gap-2" wire:key="option-{{ $index }}">
                                <select wire:model="groupOptions.{{ $index }}.ingredient_id" class="flex-1 px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    <option value="">Seleccionar ingrediente...</option>
                                    @foreach($branchIngredients as $ing)
                                    <option value="{{ $ing->id }}">{{ $ing->name }} ({{ $ing->unit?->abbreviation }})</option>
                                    @endforeach
                                </select>
                                <input wire:model="groupOptions.{{ $index }}.quantity" type="number" step="0.001" min="0.001" class="w-24 px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Cant.">
                                @if(count($groupOptions) > 1)
                                <button wire:click="removeGroupOption({{ $index }})" type="button" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                                @endif
                            </div>
                            @error("groupOptions.{$index}.ingredient_id")<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                            @endforeach
                        </div>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input wire:model="groupIsActive" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                        <span class="text-sm text-slate-700">Activo</span>
                    </label>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                    <button wire:click="$set('isGroupModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                    <button wire:click="storeGroup" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl">Guardar</button>
                </div>
            </div>
        </div></div>
    </div>
    @endif

    {{-- Group Detail Modal --}}
    @if($isGroupDetailOpen && $viewingGroup)
    <div class="relative z-[100]"><div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isGroupDetailOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto"><div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900">{{ $viewingGroup->name }}</h3>
                    <button wire:click="$set('isGroupDetailOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="px-6 py-4">
                    @if($viewingGroup->description)
                    <p class="text-sm text-slate-500 mb-4">{{ $viewingGroup->description }}</p>
                    @endif
                    <p class="text-xs text-slate-400 uppercase font-semibold mb-2">Opciones intercambiables (elegir 1 al vender)</p>
                    <div class="space-y-2">
                        @foreach($viewingGroup->options as $opt)
                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                            <div>
                                <span class="font-medium text-slate-900 text-sm">{{ $opt->ingredient->name }}</span>
                                <span class="text-xs text-slate-400 ml-1">({{ $opt->ingredient->unit?->abbreviation }})</span>
                            </div>
                            <span class="text-sm font-medium text-purple-600">{{ rtrim(rtrim(number_format($opt->quantity, 3), '0'), '.') }} {{ $opt->ingredient->unit?->abbreviation }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                    <button wire:click="$set('isGroupDetailOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cerrar</button>
                </div>
            </div>
        </div></div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($isDeleteModalOpen)
    <div class="relative z-[100]"><div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto"><div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4"><svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg></div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar {{ $deleteType === 'group' ? 'Grupo' : 'Ingrediente' }}</h3>
                <p class="text-slate-500 mb-6">¿Estás seguro? Esta acción no se puede deshacer.</p>
                <div class="flex justify-center gap-3">
                    <button wire:click="$set('isDeleteModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                    <button wire:click="delete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">Eliminar</button>
                </div>
            </div>
        </div></div>
    </div>
    @endif
</div>
