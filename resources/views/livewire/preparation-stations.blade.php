<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Módulos de Preparación</h1>
            <p class="text-slate-500 mt-1">Gestiona las estaciones de preparación (cocina, bar, repostería, etc.)</p>
        </div>
        @if(auth()->user()->hasPermission('preparation_stations.create'))
        <button wire:click="create"
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nueva Estación
        </button>
        @endif
    </div>

    {{-- Search --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text"
                class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm"
                placeholder="Buscar estación...">
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Estación</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Descripción</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($items as $item)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xl shadow-sm border border-slate-200"
                                    style="background-color: {{ $item->color ?? '#6b7280' }}1a; border-color: {{ $item->color ?? '#6b7280' }}40;">
                                    {{ $item->icon ?? '🔧' }}
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $item->name }}</p>
                                    @if($item->color)
                                    <span class="text-xs text-slate-400 font-mono">{{ $item->color }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $item->description ?? '—' }}</td>
                        <td class="px-6 py-4 text-center">
                            @if(auth()->user()->hasPermission('preparation_stations.edit'))
                            <button wire:click="toggleStatus({{ $item->id }})"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $item->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $item->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $item->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $item->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('preparation_stations.edit'))
                                <button wire:click="edit({{ $item->id }})"
                                    class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('preparation_stations.delete'))
                                <button wire:click="confirmDelete({{ $item->id }})"
                                    class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-slate-500">No hay estaciones registradas</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">{{ $items->links() }}</div>
        @endif
    </div>

    {{-- Create / Edit Modal --}}
    @if($isModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">{{ $itemId ? 'Editar' : 'Nueva' }} Estación</h3>
                    </div>
                    <div class="px-6 py-5 space-y-4">

                        {{-- Nombre --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                            <input wire:model="name" type="text"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                placeholder="Ej: Cocina, Bar, Repostería, Parrilla">
                            @error('name')<span class="text-red-500 text-xs mt-1">{{ $message }}</span>@enderror
                        </div>

                        {{-- Icono y Color --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Ícono (emoji)</label>
                                <input wire:model="icon" type="text" maxlength="5"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-2xl text-center"
                                    placeholder="🍳">
                                @error('icon')<span class="text-red-500 text-xs mt-1">{{ $message }}</span>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Color de identificación</label>
                                <div class="flex gap-2">
                                    <input wire:model="color" type="color"
                                        class="w-12 h-10 p-1 border border-slate-300 rounded-lg cursor-pointer">
                                    <input wire:model="color" type="text" maxlength="7"
                                        class="flex-1 px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] font-mono text-sm"
                                        placeholder="#16a34a">
                                </div>
                                @error('color')<span class="text-red-500 text-xs mt-1">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        {{-- Preview --}}
                        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-2xl shadow-sm"
                                style="background-color: {{ $color ?? '#6b7280' }}1a; border: 1px solid {{ $color ?? '#6b7280' }}60;">
                                {{ $icon ?: '🔧' }}
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800">{{ $name ?: 'Vista previa' }}</p>
                                <span class="text-xs px-2 py-0.5 rounded-full text-white font-medium"
                                    style="background-color: {{ $color ?? '#6b7280' }};">
                                    {{ $icon ?: '' }} {{ $name ?: 'Estación' }}
                                </span>
                            </div>
                        </div>

                        {{-- Descripción --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                            <textarea wire:model="description" rows="2"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] resize-none text-sm"
                                placeholder="Descripción opcional de esta estación..."></textarea>
                        </div>

                        {{-- Activa --}}
                        <div class="flex items-center gap-3">
                            <button wire:click="$toggle('is_active')" type="button"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            <span class="text-sm font-medium text-slate-700">Estación activa</span>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isModalOpen', false)"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="store"
                            class="px-5 py-2 text-sm font-semibold text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] rounded-xl shadow-md transition-all">
                            {{ $itemId ? 'Actualizar' : 'Crear' }} Estación
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirm Modal --}}
    @if($isDeleteModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">¿Eliminar estación?</h3>
                    <p class="text-slate-500 text-sm mb-6">Esta acción no se puede deshacer. Los productos e ingredientes asociados perderán su estación asignada.</p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('isDeleteModalOpen', false)"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="delete"
                            class="px-4 py-2 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl shadow-sm transition-colors">
                            Sí, eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
