<x-layouts.app>
    <x-slot name="title">Ventas Históricas</x-slot>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Ventas Históricas</h1>
                <p class="text-slate-500 text-sm mt-1">Consulta de las ventas del sistema anterior (Solo lectura)</p>
            </div>
            <div>
                <a href="{{ route('legacy_sales.upload.form') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl hover:from-purple-600 hover:to-indigo-700 transition shadow-md font-medium">
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
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $sale->codfactura }}</td>
                                    <td class="px-6 py-4">{{ \Carbon\Carbon::parse($sale->fechaventa)->format('d/m/Y h:i A') }}</td>
                                    <td class="px-6 py-4">{{ $sale->codcliente }}</td>
                                    <td class="px-6 py-4">{{ $sale->tipodocumento }}</td>
                                    <td class="px-6 py-4">{{ $sale->formapago }}</td>
                                    <td class="px-6 py-4 text-right font-medium text-emerald-600">${{ number_format($sale->totalpago, 2) }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <a href="{{ route('legacy_sales.show', $sale->idventa) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-medium transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            Ver Detalles
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-slate-500">No se encontraron ventas registradas en la base histórica.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($sales->hasPages())
                <div class="p-4 border-t border-slate-200 bg-slate-50/50">
                    {{ $sales->links() }}
                </div>
                @endif
            </div>
        @endif
    </div>
</x-layouts.app>
