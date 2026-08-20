<?php

namespace App\Http\Controllers;

use App\Models\LegacySale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class LegacySaleController extends Controller
{
    public function index(Request $request)
    {
        // Try to fetch legacy sales. If the table doesn't exist, it will throw an exception
        try {
            $query = LegacySale::with('items')->orderBy('fechaventa', 'desc');

            if ($request->filled('search')) {
                $search = trim($request->get('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('codfactura', 'like', "%{$search}%")
                      ->orWhere('codventa', 'like', "%{$search}%")
                      ->orWhere('codcliente', 'like', "%{$search}%");
                });
            }

            if ($request->filled('start_date')) {
                $query->whereDate('fechaventa', '>=', $request->get('start_date'));
            }

            if ($request->filled('end_date')) {
                $query->whereDate('fechaventa', '<=', $request->get('end_date'));
            }

            if ($request->filled('payment_method')) {
                $query->where('formapago', $request->get('payment_method'));
            }

            $sales = $query->paginate(20)->withQueryString();
            $hasData = true;

            $paymentMethods = LegacySale::select('formapago')
                ->whereNotNull('formapago')
                ->where('formapago', '!=', '')
                ->distinct()
                ->pluck('formapago');
        } catch (\Exception $e) {
            $sales = collect();
            $hasData = false;
            $paymentMethods = collect();
        }

        return view('legacy_sales.index', compact('sales', 'hasData', 'paymentMethods'));
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

    public function exportExcel(Request $request)
    {
        try {
            $query = LegacySale::with('items')->orderBy('fechaventa', 'desc');

            if ($request->filled('search')) {
                $search = trim($request->get('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('codfactura', 'like', "%{$search}%")
                      ->orWhere('codventa', 'like', "%{$search}%")
                      ->orWhere('codcliente', 'like', "%{$search}%");
                });
            }

            if ($request->filled('start_date')) {
                $query->whereDate('fechaventa', '>=', $request->get('start_date'));
            }

            if ($request->filled('end_date')) {
                $query->whereDate('fechaventa', '<=', $request->get('end_date'));
            }

            if ($request->filled('payment_method')) {
                $query->where('formapago', $request->get('payment_method'));
            }

            $sales = $query->get();

            if ($sales->isEmpty()) {
                return redirect()->back()->with('error', 'No hay ventas históricas para exportar con los filtros seleccionados.');
            }

            // Create Spreadsheet
            $spreadsheet = new Spreadsheet();

            // Set styles
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']], // Purple 600
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '6D28D9']]],
            ];

            $subHeaderStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']], // Indigo 600
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '4338CA']]],
            ];

            $titleStyle = [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1E293B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ];

            $sectionTitleStyle = [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '7C3AED']],
            ];

            $metaLabelStyle = [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '64748B']],
            ];

            $metaValueStyle = [
                'font' => ['size' => 10, 'color' => ['rgb' => '1E293B']],
            ];

            $dataStyle = [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ];

            $totalRowStyle = [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0F172A']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ];

            // ══════════════════════════════════════════════
            // SHEET 1: Resumen de Ventas
            // ══════════════════════════════════════════════
            $sheet1 = $spreadsheet->getActiveSheet();
            $sheet1->setTitle('Ventas Históricas');

            $row = 1;
            // Title
            $sheet1->setCellValue('A' . $row, 'REPORTE DE VENTAS HISTÓRICAS (SISTEMA ANTERIOR)');
            $sheet1->getStyle('A' . $row)->applyFromArray($titleStyle);
            $sheet1->getRowDimension($row)->setRowHeight(28);
            $row += 2;

            // Metadata info
            $sheet1->setCellValue('A' . $row, 'Fecha de Generación:');
            $sheet1->setCellValue('B' . $row, Carbon::now()->format('d/m/Y h:i A'));
            $sheet1->getStyle('A' . $row)->applyFromArray($metaLabelStyle);
            $sheet1->getStyle('B' . $row)->applyFromArray($metaValueStyle);
            $row++;

            $periodText = 'Todas las fechas';
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $periodText = Carbon::parse($request->get('start_date'))->format('d/m/Y') . ' al ' . Carbon::parse($request->get('end_date'))->format('d/m/Y');
            } elseif ($request->filled('start_date')) {
                $periodText = 'Desde ' . Carbon::parse($request->get('start_date'))->format('d/m/Y');
            } elseif ($request->filled('end_date')) {
                $periodText = 'Hasta ' . Carbon::parse($request->get('end_date'))->format('d/m/Y');
            }
            $sheet1->setCellValue('A' . $row, 'Período:');
            $sheet1->setCellValue('B' . $row, $periodText);
            $sheet1->getStyle('A' . $row)->applyFromArray($metaLabelStyle);
            $sheet1->getStyle('B' . $row)->applyFromArray($metaValueStyle);
            $row++;

            if ($request->filled('search')) {
                $sheet1->setCellValue('A' . $row, 'Filtro Búsqueda:');
                $sheet1->setCellValue('B' . $row, $request->get('search'));
                $sheet1->getStyle('A' . $row)->applyFromArray($metaLabelStyle);
                $sheet1->getStyle('B' . $row)->applyFromArray($metaValueStyle);
                $row++;
            }

            if ($request->filled('payment_method')) {
                $sheet1->setCellValue('A' . $row, 'Forma de Pago:');
                $sheet1->setCellValue('B' . $row, $request->get('payment_method'));
                $sheet1->getStyle('A' . $row)->applyFromArray($metaLabelStyle);
                $sheet1->getStyle('B' . $row)->applyFromArray($metaValueStyle);
                $row++;
            }

            $totalCount = $sales->count();
            $totalSalesAmount = (float) $sales->sum('totalpago');

            $sheet1->setCellValue('A' . $row, 'Total de Facturas:');
            $sheet1->setCellValue('B' . $row, $totalCount);
            $sheet1->getStyle('A' . $row)->applyFromArray($metaLabelStyle);
            $sheet1->getStyle('B' . $row)->applyFromArray($metaValueStyle);
            $sheet1->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $row++;

            $sheet1->setCellValue('A' . $row, 'Monto Total:');
            $sheet1->setCellValue('B' . $row, $totalSalesAmount);
            $sheet1->getStyle('A' . $row)->applyFromArray($metaLabelStyle);
            $sheet1->getStyle('B' . $row)->applyFromArray($metaValueStyle);
            $sheet1->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $row += 2;

            // Table Headers for Sheet 1
            $headers1 = [
                'A' => 'Factura #',
                'B' => 'Fecha Venta',
                'C' => 'Cliente',
                'D' => 'Tipo Documento',
                'E' => 'Forma de Pago',
                'F' => 'Subtotal',
                'G' => 'IVA / Impuestos',
                'H' => 'Descuento',
                'I' => 'Total',
                'J' => 'Monto Pagado',
                'K' => 'Estado',
                'L' => 'Observaciones'
            ];

            foreach ($headers1 as $col => $header) {
                $sheet1->setCellValue($col . $row, $header);
            }
            $sheet1->getStyle('A' . $row . ':L' . $row)->applyFromArray($headerStyle);
            $sheet1->getRowDimension($row)->setRowHeight(24);
            $startDataRow1 = $row + 1;
            $row++;

            foreach ($sales as $sale) {
                $invoiceNum = $sale->codfactura ?: ($sale->codventa ?: $sale->idventa);
                $saleDate = !empty($sale->fechaventa) ? Carbon::parse($sale->fechaventa)->format('d/m/Y h:i A') : '-';
                
                $sheet1->setCellValue('A' . $row, $invoiceNum);
                $sheet1->setCellValue('B' . $row, $saleDate);
                $sheet1->setCellValue('C' . $row, $sale->codcliente ?? 'Consumidor Final');
                $sheet1->setCellValue('D' . $row, $sale->tipodocumento ?? 'TICKET');
                $sheet1->setCellValue('E' . $row, $sale->formapago ?? ($sale->tipopago ?? 'EFECTIVO'));
                $sheet1->setCellValue('F' . $row, (float) ($sale->subtotal ?? 0));
                $sheet1->setCellValue('G' . $row, (float) ($sale->totaliva ?? 0));
                $sheet1->setCellValue('H' . $row, (float) ($sale->totaldescuento ?? 0));
                $sheet1->setCellValue('I' . $row, (float) ($sale->totalpago ?? 0));
                $sheet1->setCellValue('J' . $row, (float) ($sale->montopagado ?? $sale->totalpago ?? 0));
                $sheet1->setCellValue('K' . $row, $sale->statusventa ?? 'ACTIVA');
                $sheet1->setCellValue('L' . $row, $sale->observaciones ?? '');

                $sheet1->getStyle('A' . $row . ':L' . $row)->applyFromArray($dataStyle);
                $sheet1->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet1->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet1->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet1->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet1->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet1->getStyle('K' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet1->getStyle('F' . $row . ':J' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');

                $row++;
            }

            $endDataRow1 = $row - 1;

            // Summary row for Sheet 1
            $sheet1->setCellValue('A' . $row, 'TOTALES');
            $sheet1->mergeCells('A' . $row . ':E' . $row);
            $sheet1->setCellValue('F' . $row, "=SUM(F{$startDataRow1}:F{$endDataRow1})");
            $sheet1->setCellValue('G' . $row, "=SUM(G{$startDataRow1}:G{$endDataRow1})");
            $sheet1->setCellValue('H' . $row, "=SUM(H{$startDataRow1}:H{$endDataRow1})");
            $sheet1->setCellValue('I' . $row, "=SUM(I{$startDataRow1}:I{$endDataRow1})");
            $sheet1->setCellValue('J' . $row, "=SUM(J{$startDataRow1}:J{$endDataRow1})");
            $sheet1->setCellValue('K' . $row, '');
            $sheet1->setCellValue('L' . $row, '');

            $sheet1->getStyle('A' . $row . ':L' . $row)->applyFromArray($totalRowStyle);
            $sheet1->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle('F' . $row . ':J' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet1->getRowDimension($row)->setRowHeight(22);

            foreach (range('A', 'L') as $col) {
                $sheet1->getColumnDimension($col)->setAutoSize(true);
            }

            // ══════════════════════════════════════════════
            // SHEET 2: Detalle de Productos
            // ══════════════════════════════════════════════
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Detalle de Productos');

            $dRow = 1;
            // Title
            $sheet2->setCellValue('A' . $dRow, 'DETALLE DE PRODUCTOS VENDIDOS (HISTÓRICO)');
            $sheet2->getStyle('A' . $dRow)->applyFromArray($titleStyle);
            $sheet2->getRowDimension($dRow)->setRowHeight(28);
            $dRow += 2;

            // Table Headers for Sheet 2
            $headers2 = [
                'A' => 'Factura #',
                'B' => 'Fecha Venta',
                'C' => 'Código / SKU',
                'D' => 'Producto / Descripción',
                'E' => 'Cantidad',
                'F' => 'Precio Unitario',
                'G' => 'IVA / Impuestos',
                'H' => 'Descuento',
                'I' => 'Total Ítem'
            ];

            foreach ($headers2 as $col => $header) {
                $sheet2->setCellValue($col . $dRow, $header);
            }
            $sheet2->getStyle('A' . $dRow . ':I' . $dRow)->applyFromArray($subHeaderStyle);
            $sheet2->getRowDimension($dRow)->setRowHeight(24);
            $startDataRow2 = $dRow + 1;
            $dRow++;

            $hasItems = false;
            foreach ($sales as $sale) {
                $invoiceNum = $sale->codfactura ?: ($sale->codventa ?: $sale->idventa);
                $saleDate = !empty($sale->fechaventa) ? Carbon::parse($sale->fechaventa)->format('d/m/Y h:i A') : '-';

                if ($sale->items && $sale->items->count() > 0) {
                    foreach ($sale->items as $item) {
                        $hasItems = true;
                        $qty = (float) ($item->cantventa ?? ($item->cantidad ?? 1));
                        $unitPrice = (float) ($item->precioventa ?? 0);
                        $tax = (float) ($item->subtotalimpuestos ?? ($item->ivaproducto ?? 0));
                        $discount = (float) ($item->totaldescuentov ?? 0);
                        $itemTotal = (float) ($item->valorneto ?? ($qty * $unitPrice + $tax - $discount));

                        $sheet2->setCellValue('A' . $dRow, $invoiceNum);
                        $sheet2->setCellValue('B' . $dRow, $saleDate);
                        $sheet2->setCellValue('C' . $dRow, $item->codproducto ?? '');
                        $sheet2->setCellValue('D' . $dRow, $item->producto ?? 'Sin nombre');
                        $sheet2->setCellValue('E' . $dRow, $qty);
                        $sheet2->setCellValue('F' . $dRow, $unitPrice);
                        $sheet2->setCellValue('G' . $dRow, $tax);
                        $sheet2->setCellValue('H' . $dRow, $discount);
                        $sheet2->setCellValue('I' . $dRow, $itemTotal);

                        $sheet2->getStyle('A' . $dRow . ':I' . $dRow)->applyFromArray($dataStyle);
                        $sheet2->getStyle('A' . $dRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet2->getStyle('B' . $dRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet2->getStyle('C' . $dRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet2->getStyle('E' . $dRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet2->getStyle('E' . $dRow)->getNumberFormat()->setFormatCode('#,##0.00');
                        $sheet2->getStyle('F' . $dRow . ':I' . $dRow)->getNumberFormat()->setFormatCode('$#,##0.00');

                        $dRow++;
                    }
                }
            }

            if ($hasItems) {
                $endDataRow2 = $dRow - 1;
                $sheet2->setCellValue('A' . $dRow, 'TOTALES');
                $sheet2->mergeCells('A' . $dRow . ':D' . $dRow);
                $sheet2->setCellValue('E' . $dRow, "=SUM(E{$startDataRow2}:E{$endDataRow2})");
                $sheet2->setCellValue('F' . $dRow, '');
                $sheet2->setCellValue('G' . $dRow, "=SUM(G{$startDataRow2}:G{$endDataRow2})");
                $sheet2->setCellValue('H' . $dRow, "=SUM(H{$startDataRow2}:H{$endDataRow2})");
                $sheet2->setCellValue('I' . $dRow, "=SUM(I{$startDataRow2}:I{$endDataRow2})");

                $sheet2->getStyle('A' . $dRow . ':I' . $dRow)->applyFromArray($totalRowStyle);
                $sheet2->getStyle('A' . $dRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet2->getStyle('E' . $dRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet2->getStyle('G' . $dRow . ':I' . $dRow)->getNumberFormat()->setFormatCode('$#,##0.00');
                $sheet2->getRowDimension($dRow)->setRowHeight(22);
            }

            foreach (range('A', 'I') as $col) {
                $sheet2->getColumnDimension($col)->setAutoSize(true);
            }

            // Set active sheet to 1st sheet
            $spreadsheet->setActiveSheetIndex(0);

            $writer = new Xlsx($spreadsheet);
            $filename = 'ventas-historicas-' . now()->format('Y-m-d_His') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_legacy_');
            $writer->save($tempFile);

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error al exportar ventas históricas a Excel: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error al generar el archivo Excel: ' . $e->getMessage());
        }
    }
}
