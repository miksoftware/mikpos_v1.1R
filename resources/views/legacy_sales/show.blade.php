<x-layouts.app>
    <x-slot name="title">Detalle Venta Histórica #{{ $sale->codfactura }}</x-slot>

    <div class="p-6 max-w-5xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('legacy_sales.index') }}" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Factura Histórica #{{ $sale->codfactura }}</h1>
                    <p class="text-slate-500 text-sm mt-1">Registrada el {{ \Carbon\Carbon::parse($sale->fechaventa)->format('d/m/Y h:i A') }}</p>
                </div>
            </div>
            <div class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium border border-emerald-200">
                Total Pagado: ${{ number_format($sale->totalpago, 2) }}
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Client Info -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Información del Cliente</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-xs text-slate-500 block">Cliente</span>
                        <span class="font-medium text-slate-800">{{ $sale->codcliente }}</span>
                    </div>
                </div>
            </div>

            <!-- Sale Details -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Detalles de Venta</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-xs text-slate-500 block">Tipo Documento</span>
                        <span class="font-medium text-slate-800">{{ $sale->tipodocumento }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 block">Estado</span>
                        <span class="font-medium text-slate-800">{{ $sale->statusventa }}</span>
                    </div>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Información de Pago</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-xs text-slate-500 block">Forma de Pago</span>
                        <span class="font-medium text-slate-800">{{ $sale->formapago }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 block">Monto Pagado</span>
                        <span class="font-medium text-slate-800">${{ number_format($sale->montopagado, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="font-bold text-slate-800">Productos</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-medium">Código</th>
                            <th class="px-6 py-3 font-medium">Producto</th>
                            <th class="px-6 py-3 font-medium text-center">Cantidad</th>
                            <th class="px-6 py-3 font-medium text-right">Precio Unitario</th>
                            <th class="px-6 py-3 font-medium text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($sale->items as $item)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-3 font-medium text-slate-900">{{ $item->codproducto }}</td>
                                <td class="px-6 py-3">{{ $item->producto }}</td>
                                <td class="px-6 py-3 text-center">{{ number_format($item->cantventa, 2) }}</td>
                                <td class="px-6 py-3 text-right">${{ number_format($item->precioventa, 2) }}</td>
                                <td class="px-6 py-3 text-right font-medium text-slate-800">${{ number_format($item->valorneto, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 font-medium">
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-right text-slate-600">Subtotal IVA SI:</td>
                            <td class="px-6 py-4 text-right">${{ number_format($sale->subtotalivasi, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-6 py-2 text-right text-slate-600 border-t-0">Subtotal IVA NO:</td>
                            <td class="px-6 py-2 text-right">${{ number_format($sale->subtotalivano, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-6 py-2 text-right text-slate-600 border-t-0">Descuento:</td>
                            <td class="px-6 py-2 text-right text-red-600">-${{ number_format($sale->totaldescuento, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-right text-slate-900 font-bold text-base border-t border-slate-200">Total a Pagar:</td>
                            <td class="px-6 py-4 text-right text-emerald-600 font-bold text-lg border-t border-slate-200">${{ number_format($sale->totalpago, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
