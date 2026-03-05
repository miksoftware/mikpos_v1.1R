<div>
@if($selectedTableId && isset($table) && $table)
{{-- ══════════════════════════════════════════════════ --}}
{{-- GESTIÓN DE VENTA POR MESA — Full viewport split   --}}
{{-- ══════════════════════════════════════════════════ --}}
<div class="fixed inset-0 z-50 flex flex-col bg-slate-100">

    {{-- Top bar --}}
    <div class="h-12 bg-white border-b border-slate-200 flex items-center justify-between px-4 flex-shrink-0">
        <div class="flex items-center gap-3">
            <button wire:click="backToReception" class="flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Recepción
            </button>
            <span class="text-slate-300">|</span>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $table->zone->color ?? '#6366f1' }}"></span>
                <span class="text-sm font-semibold text-slate-700">{{ $table->zone->name ?? '' }}</span>
                <span class="text-slate-300">/</span>
                <span class="text-sm font-bold text-slate-900">{{ $table->name }}</span>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase
                {{ $table->status === 'available' ? 'bg-green-100 text-green-700' : '' }}
                {{ $table->status === 'occupied' ? 'bg-red-100 text-red-700' : '' }}
                {{ $table->status === 'reserved' ? 'bg-amber-100 text-amber-700' : '' }}
            ">{{ $table->status_label }}</span>
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-400">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            {{ $table->capacity }} personas
        </div>
    </div>

    {{-- Split: Left (detail) + Right (products) --}}
    <div class="flex flex-1 min-h-0">

        {{-- ═══ LEFT HALF: Detalle de Venta ═══ --}}
        <div class="w-1/2 flex flex-col bg-white border-r border-slate-200">

            {{-- Cliente --}}
            <div class="px-5 py-3 border-b border-slate-100 flex-shrink-0">
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Búsqueda de Cliente</p>
                <div class="flex items-center gap-2.5 bg-slate-50 rounded-xl px-3 py-2">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-[10px] font-bold">CF</span>
                    </div>
                    <span class="text-sm font-semibold text-slate-700">CONSUMIDOR FINAL</span>
                </div>
            </div>

            {{-- Items del pedido --}}
            <div class="flex-1 overflow-y-auto min-h-0">
                @if(count($cart) > 0)
                <table class="w-full">
                    <thead class="sticky top-0 bg-slate-50 z-10">
                        <tr class="text-[10px] font-semibold text-slate-400 uppercase">
                            <th class="text-left px-5 py-2">Producto</th>
                            <th class="text-center px-2 py-2 w-28">Cant.</th>
                            <th class="text-right px-5 py-2 w-24">Subtotal</th>
                            <th class="w-8"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($cart as $key => $item)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-5 py-2.5">
                                <div class="flex items-center gap-2">
                                    @if($item['type'] === 'service')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-100 text-indigo-600">SRV</span>
                                    @elseif($item['type'] === 'ingredient')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-600">ING</span>
                                    @endif
                                    <div>
                                        <p class="text-sm font-medium text-slate-800 leading-tight">{{ $item['name'] }}</p>
                                        <p class="text-[11px] text-slate-400">${{ number_format($item['unit_price'], 0, ',', '.') }} c/u</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-2 py-2.5">
                                <div class="flex items-center justify-center gap-1">
                                    <button wire:click="decrementItem('{{ $key }}')" class="w-6 h-6 rounded-lg bg-slate-100 hover:bg-red-100 hover:text-red-600 flex items-center justify-center text-slate-500 transition-colors text-xs font-bold">−</button>
                                    <span class="w-8 text-center text-sm font-bold text-slate-800">{{ $item['quantity'] }}</span>
                                    <button wire:click="incrementItem('{{ $key }}')" class="w-6 h-6 rounded-lg bg-slate-100 hover:bg-green-100 hover:text-green-600 flex items-center justify-center text-slate-500 transition-colors text-xs font-bold">+</button>
                                </div>
                            </td>
                            <td class="px-5 py-2.5 text-right">
                                <span class="text-sm font-bold text-slate-800">${{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                            </td>
                            <td class="pr-3 py-2.5">
                                <button wire:click="removeItem('{{ $key }}')" class="opacity-0 group-hover:opacity-100 w-6 h-6 rounded-lg flex items-center justify-center text-red-400 hover:bg-red-50 hover:text-red-600 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="flex flex-col items-center justify-center h-full text-center px-8">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <p class="text-slate-400 text-sm font-medium">No hay detalles agregados</p>
                    <p class="text-slate-300 text-xs mt-1">Selecciona productos del monitor</p>
                </div>
                @endif
            </div>

            {{-- Observaciones --}}
            <div class="px-5 py-2.5 border-t border-slate-100 flex-shrink-0">
                <label class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Observaciones</label>
                <textarea wire:model="observations" rows="2" placeholder="Ingresar observaciones..." class="w-full mt-1 px-3 py-2 text-sm border border-slate-200 rounded-xl resize-none focus:ring-1 focus:ring-[#ff7261]/50 focus:border-[#ff7261] placeholder-slate-300"></textarea>
            </div>

            {{-- Totales --}}
            <div class="px-5 py-3 border-t border-slate-200 bg-slate-50/80 flex-shrink-0">
                <div class="flex justify-between text-sm text-slate-500 mb-1">
                    <span>Total de items</span>
                    <span class="font-semibold text-slate-600">{{ $cartItemCount }}</span>
                </div>
                <div class="flex justify-between text-lg font-bold text-slate-900">
                    <span>Total a confirmar</span>
                    <span>${{ number_format($cartTotal, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Botones de acción --}}
            <div class="px-5 py-3 border-t border-slate-200 flex gap-2 flex-shrink-0">
                <button class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Ver Cocina
                </button>
                <button wire:click="clearCart" class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Limpiar
                </button>
                <button wire:click="confirmOrder" class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] rounded-xl transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Confirmar
                </button>
            </div>
        </div>

        {{-- ═══ RIGHT HALF: Monitor de Productos ═══ --}}
        <div class="w-1/2 flex flex-col bg-slate-50">

            {{-- Search bar --}}
            <div class="px-4 py-2.5 bg-white border-b border-slate-200 flex-shrink-0">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input wire:model.live.debounce.300ms="productSearch" type="text" placeholder="Buscar producto, servicio o ingrediente..." class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-1 focus:ring-[#ff7261]/50 focus:border-[#ff7261] placeholder-slate-400">
                </div>
            </div>

            {{-- Category pills --}}
            @if($categories->count())
            <div class="px-4 py-2 bg-white border-b border-slate-100 flex-shrink-0">
                <div class="flex gap-1.5 overflow-x-auto pb-0.5 scrollbar-hide">
                    <button wire:click="selectCategory(null)" class="px-3 py-1 rounded-lg text-[11px] font-semibold whitespace-nowrap transition-all {{ !$selectedCategory ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Todos
                    </button>
                    @foreach($categories as $cat)
                    <button wire:click="selectCategory({{ $cat->id }})" class="px-3 py-1 rounded-lg text-[11px] font-semibold whitespace-nowrap transition-all {{ $selectedCategory == $cat->id ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        {{ $cat->name }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Product grid (compact cards) --}}
            <div class="flex-1 overflow-y-auto min-h-0 p-3">
                @php
                    $hasItems = (isset($products) && $products->count()) || (isset($services) && $services->count()) || (isset($ingredients) && $ingredients->count());
                @endphp

                @if($hasItems)
                <div class="grid grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-2">
                    {{-- Products --}}
                    @foreach($products as $product)
                    <button wire:click="addToCart({{ $product->id }})" class="group relative bg-white rounded-lg border border-slate-200 hover:border-[#a855f7]/40 hover:shadow-md transition-all text-left overflow-hidden">
                        <div class="h-16 bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center relative">
                            @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                            @else
                            <svg class="w-7 h-7 text-slate-300 group-hover:text-[#a855f7]/40 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            @endif
                            {{-- Price badge --}}
                            <span class="absolute top-1 right-1 px-1 py-0.5 bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white text-[9px] font-bold rounded">
                                ${{ number_format($product->sale_price, 0, ',', '.') }}
                            </span>
                            {{-- Composite badge --}}
                            @if($product->product_type === 'composite')
                            <span class="absolute top-1 left-1 px-1 py-0.5 bg-purple-500 text-white text-[8px] font-bold rounded">Comp.</span>
                            @endif
                        </div>
                        <div class="px-1.5 py-1.5">
                            <p class="text-[11px] font-semibold text-slate-700 leading-tight line-clamp-2">{{ $product->name }}</p>
                            <p class="text-[9px] mt-0.5 font-medium {{ $product->current_stock <= ($product->min_stock ?? 0) ? 'text-red-500' : 'text-green-600' }}">
                                {{ rtrim(rtrim(number_format($product->current_stock, 3), '0'), '.') }} {{ $product->unit->abbreviation ?? '' }}
                            </p>
                        </div>
                    </button>
                    @endforeach

                    {{-- Services --}}
                    @foreach($services as $service)
                    <button wire:click="addServiceToCart({{ $service->id }})" class="group relative bg-white rounded-lg border border-slate-200 hover:border-indigo-300 hover:shadow-md transition-all text-left overflow-hidden">
                        <div class="h-16 bg-gradient-to-br from-indigo-50 to-slate-50 flex items-center justify-center relative">
                            @if($service->image)
                            <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->name }}" class="w-full h-full object-cover">
                            @else
                            <svg class="w-7 h-7 text-indigo-300 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            @endif
                            <span class="absolute top-1 left-1 px-1 py-0.5 bg-indigo-500 text-white text-[8px] font-bold rounded">Serv.</span>
                            <span class="absolute top-1 right-1 px-1 py-0.5 bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white text-[9px] font-bold rounded">
                                ${{ number_format($service->sale_price, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="px-1.5 py-1.5">
                            <p class="text-[11px] font-semibold text-slate-700 leading-tight line-clamp-2">{{ $service->name }}</p>
                        </div>
                    </button>
                    @endforeach

                    {{-- Ingredients (available for sale) --}}
                    @foreach($ingredients as $ingredient)
                    <button wire:click="addIngredientToCart({{ $ingredient->id }})" class="group relative bg-white rounded-lg border border-slate-200 hover:border-amber-300 hover:shadow-md transition-all text-left overflow-hidden">
                        <div class="h-16 bg-gradient-to-br from-amber-50 to-slate-50 flex items-center justify-center relative">
                            @if($ingredient->image)
                            <img src="{{ asset('storage/' . $ingredient->image) }}" alt="{{ $ingredient->name }}" class="w-full h-full object-cover">
                            @else
                            <svg class="w-7 h-7 text-amber-300 group-hover:text-amber-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                            @endif
                            <span class="absolute top-1 left-1 px-1 py-0.5 bg-amber-500 text-white text-[8px] font-bold rounded">Ing.</span>
                            <span class="absolute top-1 right-1 px-1 py-0.5 bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white text-[9px] font-bold rounded">
                                ${{ number_format($ingredient->sale_price, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="px-1.5 py-1.5">
                            <p class="text-[11px] font-semibold text-slate-700 leading-tight line-clamp-2">{{ $ingredient->name }}</p>
                            <p class="text-[9px] mt-0.5 font-medium {{ $ingredient->current_stock <= ($ingredient->min_stock ?? 0) ? 'text-red-500' : 'text-green-600' }}">
                                {{ rtrim(rtrim(number_format($ingredient->current_stock, 3), '0'), '.') }} {{ $ingredient->unit->abbreviation ?? '' }}
                            </p>
                        </div>
                    </button>
                    @endforeach
                </div>
                @else
                <div class="flex flex-col items-center justify-center h-full text-center">
                    <div class="w-16 h-16 rounded-2xl bg-white border border-slate-200 flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                    <p class="text-slate-400 text-sm font-medium">No se encontraron productos</p>
                    <p class="text-slate-300 text-xs mt-1">Intenta con otra búsqueda o categoría</p>
                </div>
                @endif
            </div>
        </div>

    </div>{{-- end split --}}
</div>{{-- end fixed overlay --}}

{{-- ══════════════════════════════════════════════════ --}}
{{-- Modal: Producto Compuesto — Selección de grupos    --}}
{{-- ══════════════════════════════════════════════════ --}}
@if($showCompositeModal)
<div class="fixed inset-0 z-[200]" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[200]" wire:click="closeCompositeModal"></div>
    <div class="fixed inset-0 z-[201] overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">{{ $compositeProductName }}</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Selecciona las opciones para este producto</p>
                    </div>
                    <button wire:click="closeCompositeModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                {{-- Groups --}}
                <div class="px-6 py-4 space-y-5 max-h-[60vh] overflow-y-auto">
                    @foreach($compositeGroups as $gIndex => $group)
                    <div>
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">{{ $group['group_name'] }}</label>
                        <div class="mt-2 space-y-1.5">
                            @foreach($group['options'] as $option)
                            <label class="flex items-center gap-3 px-3 py-2.5 rounded-xl border-2 cursor-pointer transition-all
                                {{ ($selectedGroupOptions[$group['group_id']] ?? null) == $option['id'] ? 'border-[#a855f7] bg-purple-50' : 'border-slate-200 hover:border-slate-300' }}
                            ">
                                <input type="radio"
                                    wire:model="selectedGroupOptions.{{ $group['group_id'] }}"
                                    value="{{ $option['id'] }}"
                                    class="text-[#a855f7] focus:ring-[#a855f7]">
                                <span class="text-sm font-medium text-slate-700">{{ $option['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between rounded-b-2xl">
                    <span class="text-sm font-bold text-slate-700">${{ number_format($compositeProductPrice, 0, ',', '.') }}</span>
                    <div class="flex gap-3">
                        <button wire:click="closeCompositeModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="confirmCompositeProduct" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">Agregar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif


@else
{{-- ══════════════════════════════════════════════════ --}}
{{-- RECEPCIÓN — Vista de mesas por zona                --}}
{{-- ══════════════════════════════════════════════════ --}}
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Recepción</h1>
            <p class="text-sm text-slate-500 mt-0.5">Gestión de mesas por zona</p>
        </div>

        @if($needsBranchSelection)
        <div class="w-64">
            <select wire:model.live="filterBranch" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                <option value="">Seleccionar sucursal...</option>
                @foreach($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
    </div>

    @if(!$needsBranchSelection || $filterBranch)

    {{-- Stats row --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-3">
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Total Mesas</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ $totalAll }}</p>
        </div>
        <div class="bg-white rounded-xl border border-green-200 px-4 py-3">
            <p class="text-[10px] font-semibold text-green-500 uppercase tracking-wider">Disponibles</p>
            <p class="text-2xl font-bold text-green-600 mt-0.5">{{ $availableAll }}</p>
        </div>
        <div class="bg-white rounded-xl border border-red-200 px-4 py-3">
            <p class="text-[10px] font-semibold text-red-500 uppercase tracking-wider">Ocupadas</p>
            <p class="text-2xl font-bold text-red-600 mt-0.5">{{ $occupiedAll }}</p>
        </div>
        <div class="bg-white rounded-xl border border-amber-200 px-4 py-3">
            <p class="text-[10px] font-semibold text-amber-500 uppercase tracking-wider">Reservadas</p>
            <p class="text-2xl font-bold text-amber-600 mt-0.5">{{ $reservedAll }}</p>
        </div>
    </div>

    {{-- Zone tabs --}}
    @if($zones->count())
    <div class="flex gap-2 overflow-x-auto pb-1">
        @foreach($zones as $zone)
        <button wire:click="switchZone({{ $zone->id }})" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap transition-all {{ $activeZone == $zone->id ? 'bg-white shadow-sm border border-slate-200 text-slate-800' : 'text-slate-500 hover:bg-white/60 hover:text-slate-700' }}">
            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $zone->color }}"></span>
            {{ $zone->name }}
            <span class="text-xs text-slate-400">{{ $zone->tables_count }}</span>
        </button>
        @endforeach
    </div>
    @endif

    {{-- Tables grid --}}
    @if($currentZone && $tables->count())
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        @foreach($tables as $tbl)
        <button wire:click="openTable({{ $tbl->id }})" class="group bg-white rounded-2xl border-2 transition-all text-left overflow-hidden hover:shadow-lg hover:-translate-y-0.5
            {{ $tbl->status === 'available' ? 'border-green-200 hover:border-green-400' : '' }}
            {{ $tbl->status === 'occupied' ? 'border-red-200 hover:border-red-400' : '' }}
            {{ $tbl->status === 'reserved' ? 'border-amber-200 hover:border-amber-400' : '' }}
        ">
            <div class="h-1.5" style="background-color: {{ $currentZone->color }}"></div>
            <div class="p-4 flex flex-col items-center text-center">
                {{-- Icon based on status --}}
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-2
                    {{ $tbl->status === 'available' ? 'bg-green-50' : '' }}
                    {{ $tbl->status === 'occupied' ? 'bg-red-50' : '' }}
                    {{ $tbl->status === 'reserved' ? 'bg-amber-50' : '' }}
                ">
                    @if($tbl->status === 'available')
                    {{-- Mesa libre --}}
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 10v8a2 2 0 002 2h14a2 2 0 002-2v-8M3 10l2-6h14l2 6"></path></svg>
                    @elseif($tbl->status === 'occupied')
                    {{-- Mesa ocupada --}}
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    @else
                    {{-- Mesa reservada --}}
                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    @endif
                </div>

                {{-- Table name --}}
                <span class="text-sm font-bold text-slate-800">{{ $tbl->name }}</span>

                {{-- Capacity --}}
                <div class="flex items-center gap-1 text-slate-400 mt-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span class="text-[10px]">{{ $tbl->capacity }}</span>
                </div>

                {{-- Order total for occupied tables --}}
                @if($tbl->status === 'occupied' && $tbl->activeOrder)
                <span class="mt-2 px-2 py-0.5 bg-red-50 text-red-600 text-xs font-bold rounded-lg">
                    ${{ number_format($tbl->activeOrder->total, 0, ',', '.') }}
                </span>
                @endif

                {{-- Status label --}}
                <span class="mt-1.5 inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase
                    {{ $tbl->status === 'available' ? 'bg-green-50 text-green-600' : '' }}
                    {{ $tbl->status === 'occupied' ? 'bg-red-50 text-red-600' : '' }}
                    {{ $tbl->status === 'reserved' ? 'bg-amber-50 text-amber-600' : '' }}
                ">{{ $tbl->status_label }}</span>
            </div>
        </button>
        @endforeach
    </div>
    @elseif($currentZone && $tables->count() === 0)
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
        </div>
        <p class="text-slate-500 font-medium">No hay mesas en esta zona</p>
        <p class="text-slate-400 text-sm mt-1">Agrega mesas desde Zonas y Mesas</p>
    </div>
    @elseif(!$zones->count())
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        </div>
        <p class="text-slate-500 font-medium">No hay zonas configuradas</p>
        <p class="text-slate-400 text-sm mt-1">Configura zonas y mesas desde Administración</p>
    </div>
    @endif

    @else
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-amber-50 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <p class="text-slate-500 font-medium">Selecciona una sucursal</p>
        <p class="text-slate-400 text-sm mt-1">Para ver las zonas y mesas disponibles</p>
    </div>
    @endif

</div>
@endif
</div>
