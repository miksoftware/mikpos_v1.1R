<x-layouts.app>
    <x-slot name="title">Subir Archivo de Ventas Históricas</x-slot>

    <div class="p-6 max-w-3xl mx-auto">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('legacy_sales.index') }}" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Cargar Archivo SQL</h1>
                <p class="text-slate-500 text-sm mt-1">Sube el respaldo de las ventas del sistema anterior (.sql)</p>
            </div>
        </div>

        @if(session('error'))
            <div class="mb-4 bg-red-50 text-red-600 p-4 rounded-xl border border-red-100 flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3 mb-8">
                <svg class="w-6 h-6 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div class="text-amber-800 text-sm">
                    <strong class="block mb-1">¡Atención!</strong>
                    Esta acción reemplazará cualquier registro histórico previo que hayas cargado. Solamente debes subir un archivo con las tablas <code class="bg-amber-100 px-1 rounded">ventas</code> y <code class="bg-amber-100 px-1 rounded">detalleventas</code>. Tu inventario y ventas actuales no se verán afectados.
                </div>
            </div>

            <form action="{{ route('legacy_sales.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="mb-8">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Archivo SQL</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl hover:bg-slate-50 transition cursor-pointer" onclick="document.getElementById('sql_file').click()">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex justify-center text-sm text-slate-600 mt-2">
                                <span class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                    Seleccionar archivo
                                </span>
                            </div>
                            <p class="text-xs text-slate-500">.sql hasta 10MB</p>
                        </div>
                    </div>
                    <input id="sql_file" name="sql_file" type="file" class="sr-only" accept=".sql" required onchange="document.getElementById('file-name').textContent = this.files[0] ? this.files[0].name : 'Ningún archivo seleccionado'">
                    <p id="file-name" class="mt-2 text-sm text-slate-500 text-center font-medium">Ningún archivo seleccionado</p>
                    
                    @error('sql_file')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('legacy_sales.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-medium hover:bg-slate-50 transition">Cancelar</a>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-600 text-white font-medium hover:from-purple-600 hover:to-indigo-700 transition shadow-md">
                        Subir y Procesar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
