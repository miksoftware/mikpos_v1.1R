<x-app-layout>
    <x-slot name="title">Ventas Históricas</x-slot>

    <div class="p-6" x-data="{ showModal: false, selectedSale: null }">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Ventas Históricas</h1>
                <p class="text-slate-500 text-sm mt-1">Consulta de las ventas del sistema anterior (Solo lectura)</p>
            </div>
            <div class="flex items-center gap-3">
                @if($hasData && (!isset($sales) || $sales->count() > 0 || request()->hasAny(['search', 'start_date', 'end_date', 'payment_method'])))
                <a href="{{ route('legacy_sales.export', request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition shadow-md font-medium text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Descargar Excel
                </a>
                @endif
                <a href="{{ route('legacy_sales.upload.form') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl hover:from-purple-600 hover:to-indigo-700 transition shadow-md font-medium text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Subir Archivo SQL
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-emerald-50 text-emerald-600 p-4 rounded-xl border border-emerald-100 flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-50 text-red-600 p-4 rounded-xl border border-red-100 flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                {{ session('error') }}
            </div>
        @endif

        @if(!$hasData)
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
                <div class="w-16 h-16 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-700 mb-2">No hay ventas históricas</h3>
                <p class="text-slate-500 mb-6">Parece que aún no se ha subido ningún archivo SQL con los datos del sistema anterior.</p>
            </div>
        @else
            <!-- Search & Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
                <form method="GET" action="{{ route('legacy_sales.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Buscar</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Factura # o Cliente..." class="w-full pl-9 pr-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha Desde</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha Hasta</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Forma de Pago</label>
                        <select name="payment_method" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                            <option value="">Todas las formas</option>
                            @if(isset($paymentMethods))
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method }}" {{ request('payment_method') === $method ? 'selected' : '' }}>{{ $method }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-sm font-medium transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            Filtrar
                        </button>
                        @if(request()->hasAny(['search', 'start_date', 'end_date', 'payment_method']))
                        <a href="{{ route('legacy_sales.index') }}" title="Limpiar filtros" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 font-medium">Factura #</th>
                                <th class="px-6 py-4 font-medium">Fecha Venta</th>
                                <th class="px-6 py-4 font-medium">Cliente</th>
                                <th class="px-6 py-4 font-medium">Tipo Doc</th>
                                <th class="px-6 py-4 font-medium">Forma Pago</th>
                                <th class="px-6 py-4 font-medium text-right">Total</th>
                                <th class="px-6 py-4 font-medium text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($sales as $sale)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $sale->codfactura ?: ($sale->codventa ?: $sale->idventa) }}</td>
                                    <td class="px-6 py-4">{{ !empty($sale->fechaventa) ? \Carbon\Carbon::parse($sale->fechaventa)->format('d/m/Y h:i A') : '-' }}</td>
                                    <td class="px-6 py-4">{{ $sale->codcliente ?? 'Consumidor Final' }}</td>
                                    <td class="px-6 py-4">{{ $sale->tipodocumento ?? 'TICKET' }}</td>
                                    <td class="px-6 py-4">{{ $sale->formapago ?? ($sale->tipopago ?? 'EFECTIVO') }}</td>
                                    <td class="px-6 py-4 text-right font-medium text-emerald-600">${{ number_format($sale->totalpago, 2) }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <button type="button" @click="selectedSale = '{{ $sale->idventa }}'; showModal = true" class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-medium transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            Ver Detalles
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-slate-500">No se encontraron ventas registradas con los filtros actuales.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if(method_exists($sales, 'hasPages') && $sales->hasPages())
                <div class="p-4 border-t border-slate-200 bg-slate-50/50">
                    {{ $sales->links() }}
                </div>
                @endif
            </div>
        @endif

        <!-- Detail Modal -->
        <div x-show="showModal" style="display: none;" class="relative z-[100]" role="dialog" aria-modal="true">
            <div x-show="showModal" class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" @click="showModal = false"></div>
            <div class="fixed inset-0 z-[101] overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    @if(isset($sales) && $sales->count() > 0)
                    @foreach($sales as $sale)
                    <div x-show="selectedSale == '{{ $sale->idventa }}'" style="display: none;" class="relative w-full max-w-3xl bg-white rounded-2xl shadow-xl">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Detalle de Venta Histórica</h3>
                                <p class="text-sm text-slate-500">Factura #{{ $sale->codfactura ?: ($sale->codventa ?: $sale->idventa) }}</p>
                            </div>
                            <button @click="showModal = false" type="button" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="px-6 py-4 space-y-6 max-h-[70vh] overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Client Info -->
                                <div class="bg-slate-50 rounded-xl p-4">
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Cliente</h3>
                                    <span class="font-medium text-slate-800">{{ $sale->codcliente ?? 'Consumidor Final' }}</span>
                                </div>
                                <!-- Sale Details -->
                                <div class="bg-slate-50 rounded-xl p-4">
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Documento / Estado</h3>
                                    <p class="font-medium text-slate-800">{{ $sale->tipodocumento ?? 'TICKET' }}</p>
                                    <p class="text-sm text-slate-500">{{ $sale->statusventa ?? 'ACTIVA' }}</p>
                                </div>
                                <!-- Payment Info -->
                                <div class="bg-slate-50 rounded-xl p-4">
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pago</h3>
                                    <p class="font-medium text-slate-800">{{ $sale->formapago ?? ($sale->tipopago ?? 'EFECTIVO') }}</p>
                                    <p class="text-sm text-slate-500">${{ number_format($sale->montopagado ?? $sale->totalpago, 2) }}</p>
                                </div>
                            </div>

                            <!-- Items -->
                            <div>
                                <h4 class="text-sm font-semibold text-slate-700 mb-3">Productos</h4>
                                <div class="border border-slate-200 rounded-xl overflow-hidden">
                                    <table class="min-w-full divide-y divide-slate-200">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Producto</th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-slate-500">Cant.</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-slate-500">Precio</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-slate-500">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200">
                                            @if($sale->items && $sale->items->count() > 0)
                                                @foreach($sale->items as $item)
                                                <tr>
                                                    <td class="px-4 py-2">
                                                        <p class="text-sm text-slate-800">{{ $item->producto ?? 'Sin descripción' }}</p>
                                                        <p class="text-xs text-slate-500">{{ $item->codproducto ?? '-' }}</p>
                                                    </td>
                                                    <td class="px-4 py-2 text-center text-sm">{{ number_format($item->cantventa ?? ($item->cantidad ?? 1), 2) }}</td>
                                                    <td class="px-4 py-2 text-right text-sm">${{ number_format($item->precioventa ?? 0, 2) }}</td>
                                                    <td class="px-4 py-2 text-right text-sm font-medium">${{ number_format($item->valorneto ?? 0, 2) }}</td>
                                                </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="4" class="px-4 py-4 text-center text-xs text-slate-400">Sin desglose de ítems</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                        <tfoot class="bg-slate-50 text-sm font-medium">
                                            <tr>
                                                <td colspan="3" class="px-4 py-2 text-right text-slate-600">Descuento:</td>
                                                <td class="px-4 py-2 text-right text-red-600">-${{ number_format($sale->totaldescuento ?? 0, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="px-4 py-3 text-right text-slate-800 font-bold border-t border-slate-200">Total a Pagar:</td>
                                                <td class="px-4 py-3 text-right text-emerald-600 font-bold border-t border-slate-200">${{ number_format($sale->totalpago ?? 0, 2) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                            <button @click="showModal = false" type="button" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cerrar</button>
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
