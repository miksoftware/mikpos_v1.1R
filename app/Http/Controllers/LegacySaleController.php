<?php

namespace App\Http\Controllers;

use App\Models\LegacySale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LegacySaleController extends Controller
{
    public function index()
    {
        // Try to fetch legacy sales. If the table doesn't exist, it will throw an exception
        try {
            $sales = LegacySale::with('items')->orderBy('fechaventa', 'desc')->paginate(20);
            $hasData = true;
        } catch (\Exception $e) {
            $sales = collect(); // empty collection
            $hasData = false;
        }

        return view('legacy_sales.index', compact('sales', 'hasData'));
    }

    public function show($id)
    {
        $sale = LegacySale::with('items')->findOrFail($id);
        return view('legacy_sales.show', compact('sale'));
    }

    public function showUploadForm()
    {
        return view('legacy_sales.upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'sql_file' => 'required|file|mimetypes:text/plain,application/sql,text/x-sql|max:10240', // 10MB max
        ]);

        try {
            $file = $request->file('sql_file');
            $sql = file_get_contents($file->getRealPath());

            // Drop tables if they exist to prevent errors on multiple uploads
            DB::statement('DROP TABLE IF EXISTS detalleventas');
            DB::statement('DROP TABLE IF EXISTS ventas');

            // Execute the SQL file
            DB::unprepared($sql);

            return redirect()->route('legacy_sales.index')
                ->with('success', 'Archivo SQL procesado y ventas históricas cargadas correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al subir SQL de ventas históricas: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Ocurrió un error al procesar el archivo SQL. Asegúrate de que el formato sea correcto.');
        }
    }
}
